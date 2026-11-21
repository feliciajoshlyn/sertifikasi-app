<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifies entry navigation routing redirects unauthenticated visitors to the login screen.
 */
class ExampleTest extends TestCase
{
    /**
     * Verify root path redirects guests to login and the login page renders HTTP 200.
     *
     * @return void
     */
    public function test_unauthenticated_root_redirects_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
        $this->get(route('login'))->assertStatus(200);
    }
}
