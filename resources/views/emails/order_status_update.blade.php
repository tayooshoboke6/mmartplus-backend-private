<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Status Update</title>
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
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            margin: 10px 0;
        }
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }
        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .order-details {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo.png') }}" alt="M-Mart+ Logo" class="logo">
            <h1>Order Status Update</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $order->user->first_name }},</p>
            
            <p>There has been an update to your order from M-Mart+.</p>
            
            @php
                $statusClass = '';
                switch($order->status) {
                    case 'processing':
                        $statusClass = 'status-processing';
                        break;
                    case 'shipped':
                        $statusClass = 'status-shipped';
                        break;
                    case 'delivered':
                        $statusClass = 'status-delivered';
                        break;
                    case 'cancelled':
                        $statusClass = 'status-cancelled';
                        break;
                    default:
                        $statusClass = 'status-processing';
                }
            @endphp
            
            <div class="status-badge {{ $statusClass }}">
                {{ ucfirst($order->status) }}
            </div>
            
            @if($order->status == 'processing')
                <p>Your order is currently being processed. We'll notify you when it's shipped.</p>
            @elseif($order->status == 'shipped')
                <p>Good news! Your order has been shipped and is on its way to you.</p>
                @if($order->tracking_number)
                    <p><strong>Tracking Number:</strong> {{ $order->tracking_number }}</p>
                @endif
            @elseif($order->status == 'delivered')
                <p>Your order has been delivered. We hope you enjoy your purchase!</p>
            @elseif($order->status == 'cancelled')
                <p>Your order has been cancelled. If you did not request this cancellation, please contact our customer support immediately.</p>
            @endif
            
            <div class="order-details">
                <h3>Order Details</h3>
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Order Date:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>
            </div>
            
            <a href="{{ url('/account/orders/' . $order->id) }}" class="button">View Order Details</a>
            
            <p>If you have any questions about your order, please contact our customer support team at support@mmartplus.com or call us at +234 123 456 7890.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} M-Mart+. All rights reserved.</p>
            <p>This email was sent to {{ $order->user->email }}.</p>
        </div>
    </div>
</body>
</html>
