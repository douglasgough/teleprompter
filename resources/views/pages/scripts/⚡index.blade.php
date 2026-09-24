<?php

use App\Models\Script;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Scripts')] class extends Component
{
    use WithFileUploads;

    /** @var array<int, UploadedFile> */
    public array $uploads = [];

    /** @var array<int, string> */
    public array $imported = [];

    /**
     * Import each Markdown file as soon as it finishes uploading.
     */
    public function updatedUploads(): void
    {
        $this->validate([
            'uploads' => 'array|max:50',
            'uploads.*' => 'file|extensions:md,markdown,txt|max:2048',
        ], [
            'uploads.*.extensions' => 'Only Markdown (.md) files can be imported.',
        ]);

        $this->imported = collect($this->uploads)
            ->map(fn (UploadedFile $file): string => Script::importMarkdown(
                $file->getClientOriginalName(),
                $file->get(),
            )->title)
            ->all();

        $this->reset('uploads');
        unset($this->scripts);
    }

    public function delete(Script $script): void
    {
        $script->delete();
        unset($this->scripts);
    }

    /**
     * @return Collection<int, Script>
     */
    #[Computed]
    public function scripts(): Collection
    {
        return Script::latest('updated_at')->get();
    }
};
?>

<div
    class="relative mx-auto max-w-3xl px-6 py-12"
    x-data="{ dragDepth: 0 }"
    x-on:dragenter.window.prevent="if ($event.dataTransfer.types.includes('Files')) dragDepth++"
    x-on:dragleave.window="dragDepth = Math.max(0, dragDepth - 1)"
    x-on:dragover.window.prevent
    x-on:drop.window.prevent="dragDepth = 0; $event.dataTransfer.files.length && $wire.uploadMultiple('uploads', $event.dataTransfer.files)"
>
    <div
        x-show="dragDepth > 0"
        x-cloak
        class="pointer-events-none fixed inset-0 z-10 grid place-items-center bg-neutral-950/90 text-2xl font-semibold text-amber-300"
    >
        Drop Markdown files to import
    </div>

    <header class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Prompter</h1>
            <p class="text-sm text-neutral-400">Export a page from Notion as Markdown, then drop the .md file here.</p>
        </div>

        <label class="shrink-0 cursor-pointer rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-neutral-950 hover:bg-amber-300 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-amber-400">
            Import .md
            <input
                type="file"
                wire:model="uploads"
                x-on:click="$el.value = null"
                accept=".md,.markdown,.txt"
                multiple
                class="sr-only"
            >
        </label>
    </header>

    <div wire:loading wire:target="uploads" class="mt-6 text-sm text-neutral-400">Importing…</div>

    @error('uploads.*')
        <p class="mt-6 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-300">{{ $message }}</p>
    @enderror

    @if ($imported)
        <p wire:key="imported-{{ implode('|', $imported) }}" class="mt-6 rounded-lg bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            Imported {{ implode(', ', $imported) }}.
        </p>
    @endif

    <ul class="mt-8 divide-y divide-neutral-800 rounded-xl border border-neutral-800">
        @forelse ($this->scripts as $script)
            <li wire:key="script-{{ $script->id }}" class="flex items-center gap-4 px-5 py-4">
                <a href="{{ route('scripts.show', $script) }}" class="min-w-0 flex-1 group">
                    <p class="truncate font-semibold group-hover:text-amber-300">{{ $script->title }}</p>
                    <p class="text-sm text-neutral-500">
                        {{ number_format($script->word_count) }} words · ~{{ $script->reading_minutes }} min · updated {{ $script->updated_at->diffForHumans() }}
                    </p>
                </a>

                <a
                    href="{{ route('scripts.show', $script) }}"
                    class="rounded-lg border border-neutral-700 px-3 py-1.5 text-sm font-medium hover:border-amber-400 hover:text-amber-300"
                >
                    Prompt
                </a>

                <button
                    type="button"
                    wire:click="delete({{ $script->id }})"
                    wire:confirm="Delete “{{ $script->title }}”?"
                    class="text-sm text-neutral-500 hover:text-red-400"
                >
                    Delete
                </button>
            </li>
        @empty
            <li class="px-5 py-12 text-center text-neutral-500">
                No scripts yet. Drop a Markdown file anywhere on this page.
            </li>
        @endforelse
    </ul>

    <p class="mt-4 text-xs text-neutral-600">
        Re-importing a file with the same title replaces the existing script.
    </p>
</div>
