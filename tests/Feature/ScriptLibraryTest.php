<?php

namespace Tests\Feature;

use App\Models\Script;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ScriptLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_lists_scripts(): void
    {
        $script = Script::factory()->create(['title' => 'Why I switched to Livewire']);

        $this->get(route('scripts.index'))
            ->assertOk()
            ->assertSee('Why I switched to Livewire')
            ->assertSee(route('scripts.show', $script));
    }

    public function test_importing_markdown_uses_the_leading_heading_as_the_title(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'Episode 12 0123456789abcdef0123456789abcdef.md',
            "\u{FEFF}# Episode 12: Teleprompters\r\n\r\nHey everyone, **welcome back**.\r\n",
        );

        Livewire::test('pages::scripts.index')
            ->set('uploads', [$file])
            ->assertHasNoErrors()
            ->assertSet('imported', ['Episode 12: Teleprompters'])
            ->assertSee('Episode 12: Teleprompters');

        $script = Script::sole();
        $this->assertSame('Episode 12: Teleprompters', $script->title);
        $this->assertSame('Hey everyone, **welcome back**.', $script->body);
    }

    public function test_importing_markdown_without_a_heading_uses_the_filename_minus_the_notion_id(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'Intro script 0123456789abcdef0123456789abcdef.md',
            'Just the words.',
        );

        Livewire::test('pages::scripts.index')->set('uploads', [$file]);

        $this->assertSame('Intro script', Script::sole()->title);
    }

    public function test_reimporting_a_script_with_the_same_title_replaces_it(): void
    {
        Script::factory()->create(['title' => 'Draft', 'body' => 'Old words.']);

        Livewire::test('pages::scripts.index')
            ->set('uploads', [UploadedFile::fake()->createWithContent('Draft.md', "# Draft\n\nNew words.")]);

        $this->assertSame('New words.', Script::sole()->body);
    }

    public function test_non_markdown_files_are_rejected(): void
    {
        Livewire::test('pages::scripts.index')
            ->set('uploads', [UploadedFile::fake()->create('thumbnail.png', 10, 'image/png')])
            ->assertHasErrors('uploads.0');

        $this->assertDatabaseEmpty('scripts');
    }

    public function test_scripts_can_be_deleted(): void
    {
        $script = Script::factory()->create();

        Livewire::test('pages::scripts.index')->call('delete', $script->id);

        $this->assertModelMissing($script);
    }
}
