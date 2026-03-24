<?php

namespace Tests\Feature;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionExpiryRedirectTest extends TestCase
{
    public function test_non_json_token_mismatch_redirects_to_login(): void
    {
        Route::middleware('web')->post('/testing/expired-session', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $response = $this->from('/dashboard')->post('/testing/expired-session');

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your session expired. Please sign in again.');
    }
}
