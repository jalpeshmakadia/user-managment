<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Welcome</title>
    </head>
    <body>
        <p>Hi {{ trim($user->first_name . ' ' . $user->last_name) }},</p>
        <p>Your account has been created successfully.</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Temporary Password:</strong> {{ $plainPassword }}</p>
        <p>Please log in and change your password as soon as possible.</p>
        <p>Thanks,<br>{{ config('app.name') }}</p>
    </body>
</html>
