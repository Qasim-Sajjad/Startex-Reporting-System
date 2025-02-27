<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Reset Password</h2>
    <p>You can reset your password by clicking the link below:</p>
    <p>
        <a href="{{ config('app.frontend_url') }}/reset-password?token={{ $token }}">
            Reset Password
        </a>
    </p>
    <p>If you didn't request this, please ignore this email.</p>
    <p>This password reset link will expire in 60 minutes.</p>
</body>
</html> 