<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * DemoAssistant — the one demo agent in this starter.
 *
 * A plain text agent with no tools and no conversation storage: it
 * answers a single prompt and returns. It exists so the template has
 * exactly one working AI call to copy from, and so the test suite can
 * fake it (DemoAssistant::fake([...])) without touching a provider.
 *
 * The provider is picked per request by AiDemoController (first
 * provider with a key configured); this class declares no provider
 * attribute on purpose, so the controller's choice wins.
 */
class DemoAssistant implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are a helpful assistant answering in one or two short sentences. Be direct and plain.';
    }
}
