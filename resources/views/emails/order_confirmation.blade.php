<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Confirmation</title>
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
        .order-details {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
        .item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .item:last-child {
            border-bottom: none;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
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
        .total {
            margin-top: 20px;
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="M-Mart+ Logo" class="logo">
            <h1>Order Confirmation</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $order->user->first_name }},</p>
            
            <p>Thank you for your order from M-Mart+. Your order has been received and is being processed.</p>
            
            <div class="order-details">
                <h3>Order Details</h3>
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Order Date:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>
                <p><strong>Payment Method:</strong> {{ ucfirst($order->payment_method) }}</p>
                
                <h4>Items Ordered</h4>
                @foreach($order->orderItems as $item)
                <div class="item">
                    <p>
                        <strong>{{ $item->product->name }}</strong><br>
                        Quantity: {{ $item->quantity }}<br>
                        Price: ₦{{ number_format($item->price, 2) }}
                    </p>
                </div>
                @endforeach
                
                <div class="total">
                    <p>Subtotal: ₦{{ number_format($order->subtotal, 2) }}</p>
                    <p>Shipping: ₦{{ number_format($order->shipping_fee, 2) }}</p>
                    <p>Discount: -₦{{ number_format($order->discount, 2) }}</p>
                    <p>Total: ₦{{ number_format($order->total, 2) }}</p>
                </div>
            </div>
            
            <a href="{{ url('/account/orders/' . $order->id) }}" class="button">View Order</a>
            
            <p>If you have any questions about your order, please contact our customer support team at support@mmartplus.com or call us at +234 123 456 7890.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} M-Mart+. All rights reserved.</p>
            <p>This email was sent to {{ $order->user->email }}. If you did not place an order with us, please ignore this email.</p>
        </div>
    </div>
</body>
</html>
