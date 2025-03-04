<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to M-Mart+</title>
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
        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin: 20px 0;
        }
        .feature {
            width: 48%;
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .feature h3 {
            margin-top: 0;
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
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
        }
        .social-links {
            margin-top: 15px;
        }
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #0077C8;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="M-Mart+ Logo" class="logo">
            <h1>Welcome to M-Mart+!</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $user->first_name }},</p>
            
            <p>Thank you for joining M-Mart+, your one-stop shop for all your shopping needs! We're excited to have you as part of our community.</p>
            
            <p>With your M-Mart+ account, you can:</p>
            
            <div class="features">
                <div class="feature">
                    <h3>Shop Easily</h3>
                    <p>Browse thousands of products from top brands with our user-friendly interface.</p>
                </div>
                <div class="feature">
                    <h3>Track Orders</h3>
                    <p>Keep an eye on your purchases with real-time order tracking.</p>
                </div>
                <div class="feature">
                    <h3>Save Favorites</h3>
                    <p>Create wishlists and save your favorite items for later.</p>
                </div>
                <div class="feature">
                    <h3>Get Rewards</h3>
                    <p>Earn points with every purchase and redeem for discounts.</p>
                </div>
            </div>
            
            <p>Ready to start shopping? Click the button below to explore our latest products:</p>
            
            <a href="{{ url('/') }}" class="button">Start Shopping</a>
            
            <p>If you have any questions or need assistance, our customer support team is always ready to help at support@mmartplus.com.</p>
            
            <p>Happy shopping!</p>
            <p>The M-Mart+ Team</p>
            
            <div class="social-links">
                <p>Follow us on social media:</p>
                <a href="https://facebook.com/mmartplus">Facebook</a>
                <a href="https://twitter.com/mmartplus">Twitter</a>
                <a href="https://instagram.com/mmartplus">Instagram</a>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} M-Mart+. All rights reserved.</p>
            <p>This email was sent to {{ $user->email }} because you registered for an account on our platform.</p>
        </div>
    </div>
</body>
</html>
