@component('mail::message')
# Welcome to {{ config('app.name') }}!

Hello **{{ $user->name }}**,

Thank you for registering with our movie app! To get started, please verify your email address by clicking the button below.

@component('mail::button', ['url' => $url])
Verify Email Address
@endcomponent

If you're having trouble clicking the button, copy and paste the URL below into your web browser:
{{ $url }}

This verification link will expire in 60 minutes.

Thanks,<br>
{{ config('app.name') }} Team

---
If you did not create an account, no further action is required.
@endcomponent