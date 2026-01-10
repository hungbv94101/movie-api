<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_normal_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
            'needs_password_change' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'needs_password_change' => false,
                ],
            ]);
    }

    public function test_user_login_with_temporary_password_requires_change()
    {
        $user = User::factory()->create([
            'password' => Hash::make('TempPass123!'),
            'needs_password_change' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'TempPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'needs_password_change' => true,
                ],
            ]);
    }

    public function test_user_can_change_password_and_login_normally()
    {
        $user = User::factory()->create([
            'password' => Hash::make('TempPass123!'),
            'needs_password_change' => true,
        ]);

        // Simulate password change
        $newPassword = 'NewPassword456!';
        $user->password = Hash::make($newPassword);
        $user->needs_password_change = false;
        $user->save();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $newPassword,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'needs_password_change' => false,
                ],
            ]);
    }
}
