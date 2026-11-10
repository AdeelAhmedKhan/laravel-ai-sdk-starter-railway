<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Security headers are applied to every response by
 * App\Http\Middleware\SecurityHeaders. Spot-check the landing
 * page: if the middleware is ever unregistered, this fails.
 */
class SecurityHeadersTest extends TestCase
{
    public function test_landing_page_carries_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }
}
