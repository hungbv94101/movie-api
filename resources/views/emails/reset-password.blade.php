@component('mail::message')
# Reset Your Password

Hello **{{ $user->name ?? 'there' }}**,

You are receiving this email because we received a password reset request for your account.

@component('mail::button', ['url' => $url])
Reset Password
@endcomponent

This password reset link will expire in 60 minutes.

If you did not request a password reset, no further action is required.

Thanks,<br>
{{ config('app.name') }} Team

---
If you're having trouble clicking the button, copy and paste the URL below into your web browser:
{{ $url }}
@endcomponent