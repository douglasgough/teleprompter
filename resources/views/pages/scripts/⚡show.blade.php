<?php

use App\Models\Script;
use Illuminate\View\View;
use Livewire\Component;

new class extends Component
{
    public Script $script;

    public function updateBlock(int $index, string $markdown): void
    {
        $this->script->replaceBlock($index, $markdown);
    }

    public function render(): View
    {
        return $this->view()->title($this->script->title);
    }
};
?>

<div
    x-data="prompter"
    x-on:keydown.window="onKeydown"
    x-on:mousemove.window="revealControls"
    class="fixed inset-0 overflow-hidden bg-black select-none"
    x-bind:class="{ 'cursor-none': playing && ! controlsVisible }"
>
    {{-- Script --}}
    <div
        x-ref="viewport"
        x-on:click="editMode || toggle()"
        x-on:wheel.prevent="onWheel"
        class="absolute inset-0 overflow-hidden"
    >
        <div
            x-ref="script"
            x-bind:style="scriptStyle"
            class="prompter-script mx-auto pt-[35vh] pb-[65vh] text-white will-change-transform"
            style="font-size: 64px; width: 80%"
            wire:ignore.self
        >
            @foreach ($script->blocks as $index => $block)
                <div
                    wire:key="block-{{ $index }}-{{ md5($block['markdown']) }}"
                    x-data="{ original: @js($block['markdown']), draft: @js($block['markdown']) }"
                >
                    <div
                        x-show="editingBlock !== {{ $index }}"
                        x-on:click="editMode && editBlock({{ $index }}, $el.nextElementSibling)"
                        x-bind:class="editMode && 'cursor-text rounded-lg outline-amber-400/0 hover:bg-white/5 hover:outline-2 hover:outline-offset-8 hover:outline-amber-400/50'"
                        class="prompter-block"
                    >
                        {!! $block['html'] !!}
                    </div>

                    <textarea
                        x-show="editingBlock === {{ $index }}"
                        x-cloak
                        x-model="draft"
                        x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                        x-on:blur="saveBlock({{ $index }}, original, draft)"
                        x-on:keydown.escape.prevent="draft = original; $el.blur()"
                        x-on:keydown.enter="if ($event.metaKey || $event.ctrlKey) $el.blur()"
                        spellcheck="true"
                        class="block w-full resize-none overflow-hidden rounded-lg bg-neutral-900 p-0 [font:inherit] text-white outline-2 outline-offset-8 outline-amber-400 select-text"
                    ></textarea>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Reading guide, at the line the eye should rest on --}}
    <div
        x-show="showGuide"
        x-bind:style="{ fontSize: fontSize + 'px' }"
        class="pointer-events-none absolute inset-x-0 top-[35vh] h-[1.35em] border-y border-amber-400/40 bg-amber-400/5"
    >
        <div class="absolute top-1/2 left-2 size-0 -translate-y-1/2 border-y-[12px] border-l-[18px] border-y-transparent border-l-amber-400"></div>
    </div>

    {{-- Countdown --}}
    <div
        x-show="countdown > 0"
        x-text="countdown"
        class="pointer-events-none absolute inset-0 grid place-items-center bg-black/60 text-[30vh] font-bold text-amber-400"
        x-cloak
    ></div>

    {{-- Progress --}}
    <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-neutral-900">
        <div class="h-full bg-amber-400/70" x-bind:style="{ width: (progress * 100) + '%' }"></div>
    </div>

    {{-- Controls --}}
    <div
        x-show="controlsVisible"
        x-transition.opacity.duration.300ms
        class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black via-black/90 to-transparent px-6 pt-10 pb-5 text-sm text-neutral-300"
    >
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-6 gap-y-3">
            <a href="{{ route('scripts.index') }}" class="text-neutral-400 hover:text-white">&larr; Scripts</a>

            <button
                type="button"
                x-on:click="toggle"
                class="w-24 rounded-lg bg-amber-400 px-4 py-2 font-semibold text-neutral-950 hover:bg-amber-300"
                x-text="playing || countdown ? 'Pause' : 'Play'"
            >Play</button>

            <button type="button" x-on:click="restart" class="hover:text-white">Restart</button>

            <button
                type="button"
                x-on:click="toggleEditMode"
                x-bind:class="editMode ? 'text-amber-300' : 'hover:text-white'"
                x-text="editMode ? 'Done editing' : 'Edit'"
            >Edit</button>

            <label class="flex items-center gap-2">
                Speed
                <input type="range" min="5" max="600" step="1" x-model.number="speed" class="w-28 accent-amber-400">
                <span class="w-8 tabular-nums" x-text="speed"></span>
            </label>

            <label class="flex items-center gap-2">
                Size
                <input type="range" min="24" max="200" step="2" x-model.number="fontSize" class="w-24 accent-amber-400">
                <span class="w-8 tabular-nums" x-text="fontSize"></span>
            </label>

            <label class="flex items-center gap-2">
                Width
                <input type="range" min="30" max="100" step="1" x-model.number="width" class="w-24 accent-amber-400">
                <span class="w-10 tabular-nums" x-text="width + '%'"></span>
            </label>

            <label class="flex items-center gap-2"><input type="checkbox" x-model="mirrored" class="accent-amber-400"> Mirror</label>
            <label class="flex items-center gap-2"><input type="checkbox" x-model="showGuide" class="accent-amber-400"> Guide</label>
            <label class="flex items-center gap-2"><input type="checkbox" x-model="useCountdown" class="accent-amber-400"> Countdown</label>

            <button type="button" x-on:click="toggleFullscreen" class="hover:text-white">Fullscreen</button>

            <span class="ml-auto tabular-nums text-neutral-500" x-text="remaining + ' left'"></span>
        </div>

        <p class="mx-auto mt-3 max-w-6xl text-xs text-neutral-600">
            Space play/pause · ↑↓ speed (⇧ fine) · ←→ line back/forward · PgUp/PgDn jump · +/− size · [ ] width · M mirror · G guide · C countdown · E edit · F fullscreen · R restart · click text to play/pause · scroll to reposition
        </p>
    </div>
</div>
