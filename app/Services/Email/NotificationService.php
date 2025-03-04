<?php

namespace App\Services\Email;

use App\Models\Order;
use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $emailService;

    /**
     * Create a new NotificationService instance.
     *
     * @param EmailServiceInterface $emailService
     */
    public function __construct(EmailServiceInterface $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Send an order confirmation email.
     *
     * @param Order $order
     * @return bool
     */
    public function sendOrderConfirmation(Order $order): bool
    {
        $user = $order->user;
        
        $subject = 'Order Confirmation - ' . config('app.name');
        $content = $this->getOrderConfirmationTemplate($order);
        
        return $this->emailService->send(
            $user->email,
            $subject,
            $content,
            config('app.name'),
            null
        );
    }
    
    /**
     * Send an order status update email.
     *
     * @param Order $order
     * @param string $previousStatus
     * @return bool
     */
    public function sendOrderStatusUpdate(Order $order, string $previousStatus): bool
    {
        $user = $order->user;
        
        $subject = 'Order Status Update - ' . config('app.name');
        $content = $this->getOrderStatusUpdateTemplate($order, $previousStatus);
        
        return $this->emailService->send(
            $user->email,
            $subject,
            $content,
            config('app.name'),
            null
        );
    }
    
    /**
     * Send a welcome email to a new user.
     *
     * @param User $user
     * @return bool
     */
    public function sendWelcomeEmail(User $user): bool
    {
        $subject = 'Welcome to ' . config('app.name');
        $content = $this->getWelcomeEmailTemplate($user);
        
        return $this->emailService->send(
            $user->email,
            $subject,
            $content,
            config('app.name'),
            null
        );
    }
    
    /**
     * Send a promotional email.
     *
     * @param User $user
     * @param string $campaignName
     * @param string $subject
     * @param string $content
     * @return bool
     */
    public function sendPromotionalEmail(User $user, string $campaignName, string $subject, string $content): bool
    {
        // Log campaign details for tracking
        Log::info("Sending promotional email '{$campaignName}' to {$user->email}");
        
        return $this->emailService->send(
            $user->email,
            $subject,
            $content,
            config('app.name'),
            null
        );
    }
    
    /**
     * Send a bulk campaign email to multiple users.
     *
     * @param array $users Array of User objects
     * @param string $campaignName
     * @param string $subject
     * @param string $content
     * @return array Array of successes and failures
     */
    public function sendBulkCampaignEmail(array $users, string $campaignName, string $subject, string $content): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'failures' => []
        ];
        
        foreach ($users as $user) {
            $sent = $this->sendPromotionalEmail($user, $campaignName, $subject, $content);
            
            if ($sent) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['failures'][] = $user->email;
            }
        }
        
        return $results;
    }
    
    /**
     * Send voucher notification email to user
     *
     * @param User $user
     * @param Voucher $voucher
     * @return bool
     */
    public function sendVoucherNotification(User $user, Voucher $voucher): bool
    {
        try {
            $emailContent = VoucherNotificationTemplate::generate($user, $voucher);
            
            return $this->emailService->send(
                $user->email,
                $emailContent['subject'],
                $emailContent['content'],
                config('app.name'),
                null
            );
        } catch (\Exception $e) {
            Log::error('Failed to send voucher notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the order confirmation email template.
     *
     * @param Order $order
     * @return string
     */
    private function getOrderConfirmationTemplate(Order $order): string
    {
        $appName = config('app.name');
        $itemsHtml = '';
        
        foreach ($order->items as $item) {
            $itemsHtml .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>".$item->product->name."</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>".$item->quantity."</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>".$item->product->currency." ".$item->price."</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>".$item->product->currency." ".($item->price * $item->quantity)."</td>
            </tr>";
        }
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .email-container { padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
                .content { padding: 20px 0; }
                .order-details { margin-bottom: 20px; }
                .order-table { width: 100%; border-collapse: collapse; }
                .order-table th { text-align: left; padding: 10px; background-color: #f8f8f8; border-bottom: 2px solid #ddd; }
                .order-total { text-align: right; margin-top: 20px; font-weight: bold; }
                .footer { padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h2>".$appName."</h2>
                </div>
                <div class='content'>
                    <p>Hello ".$order->user->name.",</p>
                    <p>Thank you for your order! We're pleased to confirm that we've received your order and it's being processed.</p>
                    
                    <div class='order-details'>
                        <h3>Order Details</h3>
                        <p><strong>Order Number:</strong> ".$order->order_number."</p>
                        <p><strong>Order Date:</strong> ".$order->created_at->format('F j, Y')."</p>
                        <p><strong>Payment Method:</strong> ".$order->payment_method."</p>
                        <p><strong>Order Status:</strong> ".$order->status."</p>
                    </div>
                    
                    <h3>Order Items</h3>
                    <table class='order-table'>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ".$itemsHtml."
                        </tbody>
                    </table>
                    
                    <div class='order-total'>
                        <p>Subtotal: ".$order->currency." ".$order->subtotal."</p>
                        <p>Tax: ".$order->currency." ".$order->tax."</p>
                        <p>Shipping: ".$order->currency." ".$order->shipping_fee."</p>
                        <p>Total: ".$order->currency." ".$order->total."</p>
                    </div>
                </div>
                <div class='footer'>
                    <p>If you have any questions or concerns, please contact our customer support team.</p>
                    <p>&copy; ".date('Y')." ".$appName.". All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get the order status update email template.
     *
     * @param Order $order
     * @param string $previousStatus
     * @return string
     */
    private function getOrderStatusUpdateTemplate(Order $order, string $previousStatus): string
    {
        $appName = config('app.name');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .email-container { padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
                .content { padding: 20px 0; }
                .status { font-size: 18px; font-weight: bold; text-align: center; padding: 10px; margin: 20px 0; background-color: #f8f8f8; border-radius: 5px; }
                .footer { padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h2>".$appName."</h2>
                </div>
                <div class='content'>
                    <p>Hello ".$order->user->name.",</p>
                    <p>We're writing to let you know that your order status has been updated.</p>
                    
                    <div class='status'>
                        Order Status Changed: ".$previousStatus." → ".$order->status."
                    </div>
                    
                    <h3>Order Details</h3>
                    <p><strong>Order Number:</strong> ".$order->order_number."</p>
                    <p><strong>Order Date:</strong> ".$order->created_at->format('F j, Y')."</p>
                    <p><strong>Last Updated:</strong> ".$order->updated_at->format('F j, Y h:i A')."</p>
                    
                    <p>You can check the details of your order by logging into your account.</p>
                </div>
                <div class='footer'>
                    <p>If you have any questions or concerns, please contact our customer support team.</p>
                    <p>&copy; ".date('Y')." ".$appName.". All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Get the welcome email template.
     *
     * @param User $user
     * @return string
     */
    private function getWelcomeEmailTemplate(User $user): string
    {
        $appName = config('app.name');
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .email-container { padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
                .content { padding: 20px 0; }
                .welcome { font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 20px; }
                .footer { padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h2>".$appName."</h2>
                </div>
                <div class='content'>
                    <div class='welcome'>Welcome to ".$appName."!</div>
                    
                    <p>Hello ".$user->name.",</p>
                    <p>Thank you for joining ".$appName."! We're thrilled to have you as a member of our community.</p>
                    
                    <p>With your new account, you can:</p>
                    <ul>
                        <li>Browse our extensive product catalog</li>
                        <li>Make purchases with ease</li>
                        <li>Track your orders</li>
                        <li>Receive exclusive promotions and offers</li>
                    </ul>
                    
                    <p>If you have any questions or need assistance, our customer service team is always ready to help!</p>
                    
                    <p>Happy shopping!</p>
                    <p>The ".$appName." Team</p>
                </div>
                <div class='footer'>
                    <p>&copy; ".date('Y')." ".$appName.". All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
