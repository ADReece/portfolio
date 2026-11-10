<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_listed_email_becomes_admin_on_login(): void
    {
        config(['app.admin_emails' => ['owner@example.com']]);
        $user = User::factory()->create(['email' => 'Owner@Example.com']);

        $this->post('/login', ['email' => 'Owner@Example.com', 'password' => 'password'])
            ->assertRedirect();

        $this->assertTrue((bool) $user->fresh()->is_admin);
    }

    public function test_other_emails_are_not_made_admin(): void
    {
        config(['app.admin_emails' => ['owner@example.com']]);
        $user = User::factory()->create(['email' => 'someone@example.com']);

        $this->post('/login', ['email' => 'someone@example.com', 'password' => 'password']);

        $this->assertFalse((bool) $user->fresh()->is_admin);
    }
}
