<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VoucherController extends Controller
{
    protected $voucherService;
    
    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }
    
    /**
     * Display a listing of user's vouchers
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $vouchers = $this->voucherService->getUserVouchers($user);
        
        return response()->json([
            'status' => 'success',
            'data' => $vouchers
        ]);
    }
    
    /**
     * Apply a voucher code to the user's current order
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'voucher_code' => 'required|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $user = Auth::user();
        $orderId = $request->input('order_id');
        $voucherCode = $request->input('voucher_code');
        
        // Verify order belongs to the user
        $order = $user->orders()->find($orderId);
        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found or does not belong to you'
            ], 404);
        }
        
        // Apply the voucher
        $result = $this->voucherService->applyVoucherToOrder($order, $voucherCode);
        
        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'discount_amount' => $result['discount_amount'],
                    'new_total' => $result['new_total']
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);
        }
    }
    
    /**
     * Admin: Create a new voucher
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check if user has admin role
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:vouchers,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean',
            'max_usage_per_user' => 'nullable|integer|min:1',
            'max_total_usage' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'qualification_type' => 'nullable|in:manual,automatic,targeted',
            'criteria_json' => 'nullable|json',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $voucher = Voucher::create($request->all());
            
            // Attach categories if provided
            if ($request->has('category_ids')) {
                $voucher->categories()->attach($request->input('category_ids'));
            }
            
            // Attach products if provided
            if ($request->has('product_ids')) {
                $voucher->products()->attach($request->input('product_ids'));
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Voucher created successfully',
                'data' => $voucher
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating voucher: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create voucher',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Admin: Generate bulk vouchers
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateBulk(Request $request)
    {
        // Check if user has admin role
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'prefix' => 'required|string|max:10',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|date|after:now',
            'quantity' => 'required|integer|min:1|max:1000',
            'code_length' => 'nullable|integer|min:4|max:12',
            'max_usage_per_user' => 'nullable|integer|min:1',
            'max_total_usage' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $result = $this->voucherService->generateBulkVouchers(
            $request->all(),
            $request->input('quantity')
        );
        
        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'voucher_codes' => $result['voucher_codes']
                ]
            ], 201);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 500);
        }
    }
    
    /**
     * Admin: Schedule targeted voucher distribution
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function scheduleDistribution(Request $request)
    {
        // Check if user has admin role
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:vouchers,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_spend' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|date|after:now',
            'max_usage_per_user' => 'nullable|integer|min:1',
            'max_total_usage' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'criteria' => 'required|array',
            'assign_immediately' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $result = $this->voucherService->scheduleVoucherDistribution($request->all());
        
        if ($result['success']) {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => [
                    'voucher_id' => $result['voucher_id']
                ]
            ], 201);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 500);
        }
    }
    
    /**
     * Admin: Get voucher usage statistics
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getUsageStats($id)
    {
        // Check if user has admin role
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }
        
        try {
            $voucher = Voucher::findOrFail($id);
            
            $stats = [
                'code' => $voucher->code,
                'total_usage' => $voucher->total_usage,
                'max_total_usage' => $voucher->max_total_usage,
                'is_active' => $voucher->is_active,
                'expires_at' => $voucher->expires_at,
                'total_discount_amount' => $voucher->usages()->sum('amount'),
                'unique_users' => $voucher->usages()->distinct('user_id')->count('user_id'),
                'recent_usages' => $voucher->usages()
                    ->with('user:id,name,email')
                    ->latest()
                    ->take(10)
                    ->get()
            ];
            
            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Voucher not found'
            ], 404);
        }
    }
}
