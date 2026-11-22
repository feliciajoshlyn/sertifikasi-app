<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Check guest visitors hitting the home page get redirected to the login screen.
 */
class ExampleTest extends TestCase
{
    /**
     * Check unauthenticated GET / redirects to /login and /login loads with HTTP 200.
     *
     * @return void
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
        $this->get(route('login'))->assertStatus(200);
    }
}
