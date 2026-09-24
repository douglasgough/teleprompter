<?php

namespace Tests\Feature;

use App\Models\Script;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScriptPrompterTest extends TestCase
{
    use RefreshDatabase;

    public function test_prompter_renders_the_script_as_html(): void
    {
        $script = Script::factory()->create([
            'title' => 'My video',
            'body' => "## Intro\n\nHello **there**.\n\n<script>alert(1)</script>",
        ]);

        $this->get(route('scripts.show', $script))
            ->assertOk()
            ->assertSee('<title>My video</title>', false)
            ->assertSee('<h2>Intro</h2>', false)
            ->assertSee('<strong>there</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_missing_scripts_return_not_found(): void
    {
        $this->get('/scripts/999')->assertNotFound();
    }
}
