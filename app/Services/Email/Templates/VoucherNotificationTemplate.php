<?php

namespace App\Services\Email\Templates;

use App\Models\User;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherNotificationTemplate
{
    public static function generate(User $user, Voucher $voucher): array
    {
        $expiryDate = $voucher->expires_at ? Carbon::parse($voucher->expires_at)->format('M d, Y') : 'No expiry';
        $discountText = $voucher->type === 'percentage' 
            ? "{$voucher->value}% off" 
            : "₦" . number_format($voucher->value, 2) . " off";
            
        $minSpendText = $voucher->min_spend > 0 
            ? " on orders above ₦" . number_format($voucher->min_spend, 2) 
            : "";
        
        // Prepare the body of the email
        $subject = "You've Received a Special Voucher - {$discountText}!";
        
        $content = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #0066cc; color: white; padding: 20px; text-align: center;'>
                    <h1>Special Offer Just for You!</h1>
                </div>
                
                <div style='background-color: #f8f9fa; padding: 20px; border: 1px solid #ddd;'>
                    <p>Hello {$user->name},</p>
                    
                    <p>We're excited to offer you a special discount on your next purchase at M-Mart+!</p>
                    
                    <div style='background-color: white; border: 2px dashed #0066cc; padding: 15px; text-align: center; margin: 20px 0;'>
                        <h2 style='color: #0066cc; margin-bottom: 5px;'>{$discountText}{$minSpendText}</h2>
                        <p style='font-size: 18px; font-weight: bold; letter-spacing: 2px; margin: 10px 0;'>{$voucher->code}</p>
                        <p style='color: #666; margin-top: 5px;'>Valid until: {$expiryDate}</p>
                    </div>
                    
                    <p>To redeem, simply enter this code at checkout.</p>
                    
                    <div style='text-align: center; margin: 25px 0;'>
                        <a href='".env('APP_URL')."' style='background-color: #0066cc; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold;'>Shop Now</a>
                    </div>
                    
                    <p style='font-size: 12px; color: #666;'>
                        Terms & Conditions:<br>
                        - This voucher is valid for one-time use only<br>
                        - Cannot be combined with other offers<br>
                        - Valid until {$expiryDate}<br>
                        " . ($voucher->min_spend > 0 ? "- Minimum spend of ₦" . number_format($voucher->min_spend, 2) . " required<br>" : "") . "
                    </p>
                </div>
                
                <div style='text-align: center; padding: 15px; font-size: 12px; color: #666;'>
                    <p>Thank you for shopping with M-Mart+!</p>
                    <p>If you have any questions, please contact our customer service.</p>
                </div>
            </div>
        ";
        
        return [
            'subject' => $subject,
            'content' => $content
        ];
    }
}
