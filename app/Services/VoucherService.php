<?php

namespace App\Services;

use App\Models\User;
use App\Models\Voucher;
use App\Models\Order;
use App\Models\VoucherUsage;
use App\Services\Email\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherService
{
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    /**
     * Get active vouchers for a user
     * 
     * @param User $user
     * @return Collection
     */
    public function getUserVouchers(User $user): Collection
    {
        $directVouchers = $user->vouchers()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->get();
            
        $publicVouchers = Voucher::where('qualification_type', 'automatic')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->get();
            
        return $directVouchers->merge($publicVouchers);
    }
    
    /**
     * Apply voucher to an order
     * 
     * @param Order $order
     * @param string $voucherCode
     * @return array
     */
    public function applyVoucherToOrder(Order $order, string $voucherCode): array
    {
        try {
            $voucher = Voucher::where('code', $voucherCode)
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', Carbon::now());
                })
                ->first();
                
            if (!$voucher) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired voucher code.'
                ];
            }
            
            // Check if voucher has been fully redeemed
            if ($voucher->isFullyRedeemed()) {
                return [
                    'success' => false,
                    'message' => 'This voucher has reached its usage limit.'
                ];
            }
            
            // Check if user has already used this voucher
            $timesUsedByUser = VoucherUsage::where('voucher_id', $voucher->id)
                ->where('user_id', $order->user_id)
                ->count();
                
            if ($timesUsedByUser >= $voucher->max_usage_per_user) {
                return [
                    'success' => false,
                    'message' => 'You have already used this voucher the maximum number of times.'
                ];
            }
            
            // Check minimum spend
            if ($order->total < $voucher->min_spend) {
                return [
                    'success' => false,
                    'message' => "This voucher requires a minimum spend of " . number_format($voucher->min_spend, 2) . "."
                ];
            }
            
            // Calculate discount amount
            $discountAmount = 0;
            if ($voucher->type === 'percentage') {
                $discountAmount = $order->total * ($voucher->value / 100);
            } else {
                $discountAmount = $voucher->value;
                // Make sure discount is not more than order total
                if ($discountAmount > $order->total) {
                    $discountAmount = $order->total;
                }
            }
            
            // Update order with discount
            DB::transaction(function () use ($order, $voucher, $discountAmount) {
                // Apply discount to order
                $order->discount = $discountAmount;
                $order->voucher_code = $voucher->code;
                $order->total = $order->total - $discountAmount;
                $order->save();
                
                // Record voucher usage
                VoucherUsage::create([
                    'voucher_id' => $voucher->id,
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'amount' => $discountAmount
                ]);
                
                // Increase voucher usage count
                $voucher->total_usage += 1;
                $voucher->save();
            });
            
            return [
                'success' => true,
                'message' => 'Voucher applied successfully.',
                'discount_amount' => $discountAmount,
                'new_total' => $order->total
            ];
        } catch (\Exception $e) {
            Log::error('Error applying voucher: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while applying the voucher.'
            ];
        }
    }
    
    /**
     * Assign vouchers to qualifying users based on criteria
     * 
     * @param array $criteria
     * @return int Number of users who received vouchers
     */
    public function assignVouchersToQualifyingUsers(Voucher $voucher): int
    {
        if (!$voucher->criteria_json) {
            return 0;
        }
        
        $criteria = $voucher->criteria_json;
        $query = User::query();
        
        // Apply criteria
        if (isset($criteria['min_spend']) && $criteria['time_period']) {
            $startDate = Carbon::now()->subDays($criteria['time_period']);
            
            $query->whereHas('orders', function ($q) use ($startDate, $criteria) {
                $q->where('created_at', '>=', $startDate)
                  ->groupBy('user_id')
                  ->havingRaw('SUM(total) >= ?', [$criteria['min_spend']]);
            });
        }
        
        if (isset($criteria['min_orders'])) {
            $query->whereHas('orders', function ($q) use ($criteria) {
                $q->groupBy('user_id')
                  ->havingRaw('COUNT(*) >= ?', [$criteria['min_orders']]);
            });
        }
        
        if (isset($criteria['product_ids'])) {
            $query->whereHas('orders.items', function ($q) use ($criteria) {
                $q->whereIn('product_id', $criteria['product_ids']);
            });
        }
        
        if (isset($criteria['category_ids'])) {
            $query->whereHas('orders.items.product.categories', function ($q) use ($criteria) {
                $q->whereIn('categories.id', $criteria['category_ids']);
            });
        }
        
        if (isset($criteria['registration_days'])) {
            $query->where('created_at', '>=', Carbon::now()->subDays($criteria['registration_days']));
        }
        
        if (isset($criteria['user_type'])) {
            $query->whereHas('roles', function ($q) use ($criteria) {
                $q->where('name', $criteria['user_type']);
            });
        }
        
        // Get qualifying users
        $users = $query->get();
        $count = 0;
        
        // Assign vouchers to qualifying users
        foreach ($users as $user) {
            try {
                // Check if user already has this voucher
                if (!$user->vouchers()->where('vouchers.id', $voucher->id)->exists()) {
                    $user->vouchers()->attach($voucher->id);
                    $count++;
                    
                    // Send notification email about new voucher
                    if (isset($criteria['send_email']) && $criteria['send_email']) {
                        $this->notificationService->sendVoucherNotification($user, $voucher);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error assigning voucher to user: ' . $e->getMessage());
            }
        }
        
        return $count;
    }
    
    /**
     * Generate bulk vouchers
     * 
     * @param array $data
     * @param int $quantity
     * @return array
     */
    public function generateBulkVouchers(array $data, int $quantity): array
    {
        $voucherCodes = [];
        
        DB::beginTransaction();
        try {
            for ($i = 0; $i < $quantity; $i++) {
                // Generate unique code
                $code = $data['prefix'] . $this->generateRandomCode($data['code_length'] ?? 8);
                
                // Create voucher
                $voucher = Voucher::create([
                    'code' => $code,
                    'type' => $data['type'],
                    'value' => $data['value'],
                    'min_spend' => $data['min_spend'] ?? 0,
                    'expires_at' => isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
                    'is_active' => $data['is_active'] ?? true,
                    'max_usage_per_user' => $data['max_usage_per_user'] ?? 1,
                    'max_total_usage' => $data['max_total_usage'] ?? null,
                    'description' => $data['description'] ?? null,
                    'qualification_type' => $data['qualification_type'] ?? 'manual',
                    'criteria_json' => $data['criteria_json'] ?? null,
                ]);
                
                // Attach categories if specified
                if (isset($data['category_ids']) && is_array($data['category_ids'])) {
                    $voucher->categories()->attach($data['category_ids']);
                }
                
                // Attach products if specified
                if (isset($data['product_ids']) && is_array($data['product_ids'])) {
                    $voucher->products()->attach($data['product_ids']);
                }
                
                $voucherCodes[] = $code;
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Generated ' . $quantity . ' vouchers successfully.',
                'voucher_codes' => $voucherCodes
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error generating bulk vouchers: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to generate vouchers: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate random code for voucher
     * 
     * @param int $length
     * @return string
     */
    private function generateRandomCode(int $length = 8): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
    
    /**
     * Schedule voucher distribution based on user segments
     * 
     * @param array $data
     * @return array
     */
    public function scheduleVoucherDistribution(array $data): array
    {
        try {
            // Create the voucher first
            $voucher = Voucher::create([
                'code' => $data['code'],
                'type' => $data['type'],
                'value' => $data['value'],
                'min_spend' => $data['min_spend'] ?? 0,
                'expires_at' => isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
                'is_active' => true,
                'max_usage_per_user' => $data['max_usage_per_user'] ?? 1,
                'max_total_usage' => $data['max_total_usage'] ?? null,
                'description' => $data['description'] ?? null,
                'qualification_type' => 'targeted',
                'criteria_json' => $data['criteria'] ?? null,
            ]);
            
            // Assign to qualifying users immediately if requested
            $assignCount = 0;
            if (isset($data['assign_immediately']) && $data['assign_immediately']) {
                $assignCount = $this->assignVouchersToQualifyingUsers($voucher);
            }
            
            return [
                'success' => true,
                'message' => 'Voucher created successfully. ' . ($assignCount > 0 ? "Assigned to {$assignCount} users." : ""),
                'voucher_id' => $voucher->id
            ];
        } catch (\Exception $e) {
            Log::error('Error scheduling voucher distribution: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to schedule voucher distribution: ' . $e->getMessage()
            ];
        }
    }
}
