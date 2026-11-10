<?php

namespace Tests\Feature;

use App\Ai\Agents\DemoAssistant;
use Tests\TestCase;

/**
 * AI demo endpoint.
 *
 * Two honest states, both tested without a real provider key:
 *  - No key configured: the endpoint says so (configured: false)
 *    and never calls the agent — no fake AI answer, ever.
 *  - Key configured (test-only fake key in config) + faked agent:
 *    the endpoint returns the agent's text end to end.
 */
class AiDemoTest extends TestCase
{
    public function test_demo_status_reports_unconfigured_without_a_key(): void
    {
        $response = $this->getJson('/api/ai/demo');

        $response->assertOk();
        $response->assertJson([
            'configured' => false,
            'provider' => null,
        ]);
        $this->assertStringContainsString('OPENAI_API_KEY', $response->json('message'));
    }

    public function test_demo_prompt_without_a_key_says_so_and_calls_no_agent(): void
    {
        $response = $this->postJson('/api/ai/demo', ['prompt' => 'What is Laravel?']);

        $response->assertOk();
        $response->assertJson([
            'configured' => false,
            'provider' => null,
        ]);
        $this->assertStringContainsString('No AI provider key is configured', $response->json('message'));
        $this->assertArrayNotHasKey('text', $response->json());
    }

    public function test_demo_prompt_returns_the_agent_text_when_faked(): void
    {
        // A test-only fake key in config makes the controller pick
        // the openai provider; the faked agent answers instead of
        // any real provider call. No network, no real key.
        config(['ai.providers.openai.key' => 'test-key']);
        DemoAssistant::fake(['Laravel is a PHP framework.']);

        $response = $this->postJson('/api/ai/demo', ['prompt' => 'What is Laravel?']);

        $response->assertOk();
        $response->assertJson([
            'configured' => true,
            'provider' => 'openai',
            'prompt' => 'What is Laravel?',
            'text' => 'Laravel is a PHP framework.',
        ]);
        DemoAssistant::assertPrompted('What is Laravel?');
    }

    public function test_demo_prompt_requires_a_prompt(): void
    {
        $response = $this->postJson('/api/ai/demo', ['prompt' => '']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }
}
