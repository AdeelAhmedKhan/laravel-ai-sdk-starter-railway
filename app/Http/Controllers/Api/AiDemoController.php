<?php

namespace App\Http\Controllers\Api;

use App\Ai\Agents\DemoAssistant;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Demo AI endpoints.
 *
 * GET  /api/ai/demo — reports whether any provider key is configured.
 * POST /api/ai/demo — runs one prompt through DemoAssistant.
 *
 * Honesty rule: when no provider key is configured, BOTH endpoints
 * say so plainly (configured: false + which variables to set) and
 * NEVER return a fake AI answer. A missing key is a configuration
 * state, not an error to hide behind a canned response.
 */
class AiDemoController extends Controller
{
    /**
     * Providers the demo will try, in order. The first one with a
     * non-empty key in config/ai.php wins. OpenAI first because it
     * is the SDK's default provider.
     */
    private const PROVIDER_KEY_VARS = [
        'openai' => 'OPENAI_API_KEY',
        'anthropic' => 'ANTHROPIC_API_KEY',
        'gemini' => 'GEMINI_API_KEY',
        'deepseek' => 'DEEPSEEK_API_KEY',
        'groq' => 'GROQ_API_KEY',
        'xai' => 'XAI_API_KEY',
        'mistral' => 'MISTRAL_API_KEY',
        'openrouter' => 'OPENROUTER_API_KEY',
    ];

    /**
     * Find the first provider with a key configured, or null.
     */
    public static function configuredProvider(): ?string
    {
        $providers = config('ai.providers', []);

        foreach (self::PROVIDER_KEY_VARS as $name => $var) {
            $key = $providers[$name]['key'] ?? null;

            if (is_string($key) && trim($key) !== '') {
                return $name;
            }
        }

        return null;
    }

    /**
     * GET /api/ai/demo — configuration status, no AI call.
     */
    public function show(): JsonResponse
    {
        $provider = self::configuredProvider();

        if ($provider === null) {
            return response()->json([
                'configured' => false,
                'provider' => null,
                'message' => 'No AI provider key is configured. Set OPENAI_API_KEY (or ANTHROPIC_API_KEY / GEMINI_API_KEY) as a service variable and redeploy, then POST a prompt to this endpoint.',
                'example' => 'curl -X POST '.url('/api/ai/demo').' -H "Content-Type: application/json" -d \'{"prompt": "What is Laravel?"}\'',
            ]);
        }

        return response()->json([
            'configured' => true,
            'provider' => $provider,
            'message' => 'AI is configured. POST a prompt to this endpoint to run the demo agent.',
            'example' => 'curl -X POST '.url('/api/ai/demo').' -H "Content-Type: application/json" -d \'{"prompt": "What is Laravel?"}\'',
        ]);
    }

    /**
     * POST /api/ai/demo — one prompt through DemoAssistant.
     */
    public function prompt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
        ]);

        $provider = self::configuredProvider();

        if ($provider === null) {
            return response()->json([
                'configured' => false,
                'provider' => null,
                'message' => 'No AI provider key is configured. Set OPENAI_API_KEY (or ANTHROPIC_API_KEY / GEMINI_API_KEY) as a service variable and redeploy. No AI call was made.',
            ], 200);
        }

        try {
            $response = (new DemoAssistant)->prompt(
                $validated['prompt'],
                provider: $provider,
            );
        } catch (\Throwable $e) {
            // Provider errors (bad key, quota, outage) are reported,
            // never replaced with a canned answer.
            return response()->json([
                'configured' => true,
                'provider' => $provider,
                'error' => 'The provider request failed: '.$e->getMessage(),
            ], 502);
        }

        return response()->json([
            'configured' => true,
            'provider' => $provider,
            'prompt' => $validated['prompt'],
            'text' => $response->text,
            'usage' => $response->usage ? [
                'input_tokens' => $response->usage->inputTokens ?? null,
                'output_tokens' => $response->usage->outputTokens ?? null,
            ] : null,
        ]);
    }
}
