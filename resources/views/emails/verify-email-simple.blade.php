<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Email Address</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #0077C8;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
        .content {
            padding: 20px;
            background-color: #f9f9f9;
        }
        .verification-code {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 4px;
            text-align: center;
            margin: 30px 0;
            color: #0077C8;
        }
        .button {
            display: inline-block;
            background-color: #0077C8;
            color: #fff;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
        }
        .note {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
            font-size: 13px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="M-Mart+ Logo" class="logo">
            <h1>Verify Your Email Address</h1>
        </div>
        
        <div class="content">
            <p>Hello,</p>
            
            <p>Please use the verification code below to verify your email address:</p>
            
            <div class="verification-code">
                {{ $code }}
            </div>
            
            <p>This code will expire in {{ $expires }}. If you did not request this verification, please ignore this email.</p>
            
            <a href="{{ url('/login') }}" class="button">Go to Login</a>
            
            <div class="note">
                <p><strong>Note:</strong> For security reasons, please:</p>
                <ul>
                    <li>Never share your verification code with anyone</li>
                    <li>Make sure you're on the official M-Mart+ website before entering your code</li>
                    <li>Contact our support team if you have any concerns</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} M-Mart+. All rights reserved.</p>
            <p>This email was sent to {{ $email }} because a verification was requested for this address.</p>
        </div>
    </div>
</body>
</html>
