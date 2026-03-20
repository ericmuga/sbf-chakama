<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_challenge_route_is_not_registered(): void
    {
        $this->assertFalse(Route::has('two-factor.login'));
        $this->assertFalse(Route::has('two-factor.login.store'));
    }
}
