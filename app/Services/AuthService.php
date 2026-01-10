<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class AuthService
{
    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            try {
                // Trigger email verification
                event(new Registered($user));
                
                $token = $user->createToken('auth-token')->plainTextToken;

                return [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'message' => 'Registration successful. Please check your email to verify your account.'
                ];
                
            } catch (\Exception $e) {
                // If email sending fails, we can still return success
                // but with different message
                $token = $user->createToken('auth-token')->plainTextToken;

                return [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'message' => 'Registration successful. Email verification is temporarily unavailable.'
                ];
            }
        });
    }

    /**
     * Login user
     */
    public function login(array $credentials): array
    {
        if (!Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::guard('web')->user();
        
        // Check if email is verified (skip in local development if mail is not configured)
        if ($user instanceof MustVerifyEmail && !$user->hasVerifiedEmail()) {
            // Only enforce email verification if mail is properly configured
            if (config('mail.default') !== 'log' && config('app.env') === 'production') {
                Auth::guard('web')->logout();
                throw ValidationException::withMessages([
                    'email' => ['Please verify your email address before logging in.'],
                ]);
            }
        }

        // Revoke all previous tokens
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        if (method_exists($user, 'createToken')) {
            $token = $user->createToken('auth-token')->plainTextToken;
        } else {
            throw new \Exception('User model does not support API tokens');
        }

        return [
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Login successful',
            'needs_password_change' => $user->needs_password_change ?? false
        ];
    }

    /**
     * Logout user
     */
    public function logout(Request $request): array
    {
        if (method_exists($request->user(), 'currentAccessToken')) {
            $request->user()->currentAccessToken()->delete();
        }

        return [
            'message' => 'Logout successful'
        ];
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): User
    {
        return $request->user();
    }

    /**
     * Send temporary password via email
     */
    public function forgotPassword(string $email): array
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['We can\'t find a user with that email address.'],
            ]);
        }

        // Generate temporary password
        $tempPassword = \Illuminate\Support\Str::random(8);
        
        // Update user password and set flag
        $user->update([
            'password' => Hash::make($tempPassword),
            'needs_password_change' => true
        ]);

        // Send email with temporary password
        \Mail::raw("Your temporary password: {$tempPassword}\n\nPlease login and change your password immediately.", function ($message) use ($email) {
            $message->to($email)
                    ->subject('Temporary Password - ' . config('app.name'));
        });

        return [
            'message' => 'Temporary password sent to your email.'
        ];
    }

    /**
     * Reset password
     */
    public function resetPassword(array $data): array
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(\Illuminate\Support\Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return [
            'message' => 'Password reset successful. You can now login with your new password.'
        ];
    }

    /**
     * Change password (authenticated user)
     */
    public function changePassword(Request $request, array $data): array
    {
        $user = $request->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
            'needs_password_change' => false  // Clear the flag
        ]);

        // Revoke all tokens except current one
        if (method_exists($user, 'currentAccessToken') && method_exists($user, 'tokens')) {
            $currentTokenId = $request->user()->currentAccessToken()->id;
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        }

        return [
            'message' => 'Password changed successfully.'
        ];
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request, array $data): array
    {
        $user = $request->user();
        
        // Check if email is being changed
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $data['email_verified_at'] = null; // Reset email verification
        }

        $user->update(array_filter($data));

        // Send verification email if email was changed
        if (isset($data['email']) && $data['email'] !== $user->getOriginal('email')) {
            if ($user instanceof MustVerifyEmail) {
                $user->sendEmailVerificationNotification();
            }
        }

        return [
            'user' => $user->fresh(),
            'message' => 'Profile updated successfully.'
        ];
    }

    /**
     * Verify email
     */
    public function verifyEmail(Request $request): array
    {
        $user = $request->user();
        
        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            return [
                'message' => 'Email already verified.'
            ];
        }

        if ($user instanceof MustVerifyEmail && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return [
            'message' => 'Email verified successfully.'
        ];
    }

    /**
     * Resend email verification
     */
    public function resendVerification(Request $request): array
    {
        $user = $request->user();
        
        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            return [
                'message' => 'Email already verified.'
            ];
        }

        if ($user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }

        return [
            'message' => 'Verification email sent.'
        ];
    }

    /**
     * Validate registration data
     */
    private function validateRegistrationData(array $data): void
    {
        // Check if user already exists
        if (User::where('email', $data['email'])->exists()) {
            throw new \InvalidArgumentException('User with this email already exists');
        }

        // Additional business validation can go here
        if (strlen($data['password']) < 8) {
            throw new \InvalidArgumentException('Password must be at least 8 characters long');
        }
    }
}