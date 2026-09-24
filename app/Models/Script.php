<?php

namespace App\Models;

use Database\Factories\ScriptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['title', 'body'])]
class Script extends Model
{
    /** @use HasFactory<ScriptFactory> */
    use HasFactory;

    /**
     * Average speaking pace used to estimate how long a script takes to read.
     */
    public const int WORDS_PER_MINUTE = 150;

    /**
     * Create or replace a script from a Markdown file exported from Notion.
     *
     * The leading "# Heading" becomes the title (so it isn't read aloud). Without one,
     * the filename is used, minus the 32-character ID Notion appends to exports.
     * Re-importing a script with the same title replaces its body.
     */
    public static function importMarkdown(string $filename, string $markdown): self
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", Str::chopStart($markdown, "\u{FEFF}"));

        if (preg_match('/\A\s*#[ \t]+(.+?)[ \t]*#*[ \t]*(?:\n|\z)/', $markdown, $matches)) {
            $title = $matches[1];
            $markdown = substr($markdown, strlen($matches[0]));
        } else {
            $title = preg_replace('/\s+[0-9a-f]{32}$/i', '', pathinfo($filename, PATHINFO_FILENAME));
        }

        return static::updateOrCreate(
            ['title' => Str::limit(trim($title), 250, '')],
            ['body' => trim($markdown)],
        );
    }

    /**
     * The script split into paragraph-level blocks, each with its Markdown source
     * and rendered HTML, so the prompter can edit one block at a time.
     *
     * @return Attribute<array<int, array{markdown: string, html: string}>, never>
     */
    protected function blocks(): Attribute
    {
        return Attribute::get(fn (): array => array_map(fn (string $markdown): array => [
            'markdown' => $markdown,
            'html' => Str::markdown($markdown, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        ], $this->markdownBlocks()));
    }

    /**
     * Replace one block of the script, removing it when the new text is empty.
     */
    public function replaceBlock(int $index, string $markdown): void
    {
        $blocks = $this->markdownBlocks();

        if (! array_key_exists($index, $blocks)) {
            return;
        }

        $blocks[$index] = trim(str_replace(["\r\n", "\r"], "\n", $markdown));

        $this->update(['body' => implode("\n\n", array_filter($blocks, fn (string $block): bool => $block !== ''))]);
    }

    /**
     * @return array<int, string>
     */
    private function markdownBlocks(): array
    {
        return preg_split('/\n[ \t]*\n\s*/', trim($this->body), flags: PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @return Attribute<int, never>
     */
    protected function wordCount(): Attribute
    {
        return Attribute::get(fn (): int => str_word_count(strip_tags(Str::markdown($this->body))));
    }

    /**
     * @return Attribute<int, never>
     */
    protected function readingMinutes(): Attribute
    {
        return Attribute::get(fn (): int => max(1, (int) ceil($this->word_count / self::WORDS_PER_MINUTE)));
    }
}
