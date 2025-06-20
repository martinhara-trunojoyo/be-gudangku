<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Your Password</h2>
        <p>Hello,</p>
        <p>We received a request to reset your password. Click the button below to reset your password:</p>
        
        <a href="{{ $resetUrl }}" class="button">Reset Password</a>
        
        <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
        
        <p>This password reset link will expire in 60 minutes.</p>
        
        <div class="footer">
            <p>If you're having trouble clicking the button, copy and paste this URL into your web browser:</p>
            <p>{{ $resetUrl }}</p>
        </div>
    </div>
</body>
</html> 