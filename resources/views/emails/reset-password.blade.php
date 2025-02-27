@component('mail::message')
# Reset Your Password

You are receiving this email because we received a password reset request for your account.

Click the button below to reset your password:

@component('mail::button', ['url' => $resetLink])
Reset Password
@endcomponent

If you did not request a password reset, no further action is required.

This password reset link will expire in 24 hours.

Thanks,<br>
{{ config('app.name') }}
@endcomponent 