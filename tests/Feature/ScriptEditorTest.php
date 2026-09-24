<?php

namespace Tests\Feature;

use App\Models\Script;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScriptEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_single_block_can_be_edited_in_place(): void
    {
        $script = Script::factory()->create([
            'body' => "## Intro\n\nHelo everyone\n\n- one\n- two",
        ]);

        Livewire::test('pages::scripts.show', ['script' => $script])
            ->call('updateBlock', 1, "Hello everyone,\r\nwelcome back.")
            ->assertSee('welcome back.');

        $this->assertSame("## Intro\n\nHello everyone,\nwelcome back.\n\n- one\n- two", $script->fresh()->body);
    }

    public function test_emptying_a_block_removes_it(): void
    {
        $script = Script::factory()->create(['body' => "First.\n\nSecond.\n\nThird."]);

        Livewire::test('pages::scripts.show', ['script' => $script])->call('updateBlock', 1, '   ');

        $this->assertSame("First.\n\nThird.", $script->fresh()->body);
    }

    public function test_editing_a_block_that_does_not_exist_changes_nothing(): void
    {
        $script = Script::factory()->create(['body' => 'Only one.']);

        Livewire::test('pages::scripts.show', ['script' => $script])->call('updateBlock', 5, 'Sneaky.');

        $this->assertSame('Only one.', $script->fresh()->body);
    }

    public function test_blocks_are_split_on_blank_lines(): void
    {
        $script = Script::factory()->make(['body' => "# Title\n\nPara one\nstill one\n\n\n  \nPara two"]);

        $this->assertSame(
            ['# Title', "Para one\nstill one", 'Para two'],
            array_column($script->blocks, 'markdown'),
        );
    }
}
