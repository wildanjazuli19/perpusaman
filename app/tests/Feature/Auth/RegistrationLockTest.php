<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_stays_locked_after_the_first_admin_deletes_their_account(): void
    {
        $this->post('/register', [
            'name' => 'Admin Pertama',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $admin = User::query()->firstOrFail();

        $this->actingAs($admin)->delete('/profile', [
            'password' => 'password',
        ])->assertRedirect('/');

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('app_settings', [
            'key' => 'registration_locked',
            'value' => '1',
        ]);

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Akun Tidak Sah',
            'email' => 'outsider@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_existing_account_is_migrated_with_registration_locked(): void
    {
        // Simulates an installation whose Admin account existed before this patch.
        $this->assertSame('0', DB::table('app_settings')
            ->where('key', 'registration_locked')
            ->value('value'));

        User::factory()->create();

        $this->get('/register')->assertNotFound();
    }
}
