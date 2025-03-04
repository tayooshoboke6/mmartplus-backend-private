<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Http\Requests\OrderRequest;
use App\Services\Email\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected $notificationService;
    
    /**
     * Create a new controller instance.
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of the orders.
     *
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/orders",
     *     summary="List user's orders",
     *     description="Returns a list of the authenticated user's orders. Admin users will see all orders.",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="order_number", type="string", example="ORD-ABC123XYZ"),
     *                         @OA\Property(property="total", type="number", format="float", example=1299.99),
     *                         @OA\Property(property="status", type="string", example="pending", enum={"pending", "processing", "completed", "cancelled"}),
     *                         @OA\Property(property="payment_status", type="string", example="pending", enum={"pending", "paid", "failed"}),
     *                         @OA\Property(property="payment_method", type="string", example="credit_card"),
     *                         @OA\Property(property="shipping_address", type="object"),
     *                         @OA\Property(property="billing_address", type="object"),
     *                         @OA\Property(property="notes", type="string", example="Please leave at the front door"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(
     *                             property="items",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="order_id", type="integer", example=1),
     *                                 @OA\Property(property="product_id", type="integer", example=1),
     *                                 @OA\Property(property="product_name", type="string", example="iPhone 14 Pro"),
     *                                 @OA\Property(property="quantity", type="integer", example=1),
     *                                 @OA\Property(property="price", type="number", format="float", example=1299.99),
     *                                 @OA\Property(property="total", type="number", format="float", example=1299.99)
     *                             )
     *                         ),
     *                         @OA\Property(property="items_count", type="integer", example=2)
     *                     )
     *                 ),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::user();
        
        // For admin users, return all orders, otherwise return only user's orders
        $orders = $user->hasRole('admin') 
            ? Order::with('items')->latest()->paginate(10)
            : $user->orders()->with('items')->latest()->paginate(10);
        
        // Add items_count for frontend compatibility
        $orders->getCollection()->transform(function ($order) {
            $order->items_count = $order->items->count();
            return $order;
        });
        
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created order.
     *
     * @param  \App\Http\Requests\OrderRequest  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Post(
     *     path="/api/orders",
     *     summary="Create a new order",
     *     description="Create a new order with the provided items and address information",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"items", "payment_method", "shipping_address", "billing_address"},
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"product_id", "quantity"},
     *                     @OA\Property(property="product_id", type="integer", example=1),
     *                     @OA\Property(property="quantity", type="integer", example=2)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="payment_method",
     *                 type="string",
     *                 example="credit_card",
     *                 enum={"credit_card", "paypal", "cash_on_delivery"}
     *             ),
     *             @OA\Property(
     *                 property="shipping_address",
     *                 type="object",
     *                 required={"first_name", "last_name", "address", "city", "state", "postal_code", "country", "phone"},
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="address_2", type="string", example="Apt 4B"),
     *                 @OA\Property(property="city", type="string", example="New York"),
     *                 @OA\Property(property="state", type="string", example="NY"),
     *                 @OA\Property(property="postal_code", type="string", example="10001"),
     *                 @OA\Property(property="country", type="string", example="USA"),
     *                 @OA\Property(property="phone", type="string", example="555-123-4567")
     *             ),
     *             @OA\Property(
     *                 property="billing_address",
     *                 type="object",
     *                 required={"first_name", "last_name", "address", "city", "state", "postal_code", "country", "phone"},
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="address_2", type="string", example="Apt 4B"),
     *                 @OA\Property(property="city", type="string", example="New York"),
     *                 @OA\Property(property="state", type="string", example="NY"),
     *                 @OA\Property(property="postal_code", type="string", example="10001"),
     *                 @OA\Property(property="country", type="string", example="USA"),
     *                 @OA\Property(property="phone", type="string", example="555-123-4567")
     *             ),
     *             @OA\Property(property="notes", type="string", example="Please leave at the front door")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Order created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="order",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="order_number", type="string", example="ORD-ABC123XYZ"),
     *                     @OA\Property(property="total", type="number", format="float", example=1299.99),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="payment_status", type="string", example="pending"),
     *                     @OA\Property(property="payment_method", type="string", example="credit_card"),
     *                     @OA\Property(property="shipping_address", type="object"),
     *                     @OA\Property(property="billing_address", type="object"),
     *                     @OA\Property(property="notes", type="string", example="Please leave at the front door"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="items",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="order_id", type="integer", example=1),
     *                             @OA\Property(property="product_id", type="integer", example=1),
     *                             @OA\Property(property="product_name", type="string", example="iPhone 14 Pro"),
     *                             @OA\Property(property="quantity", type="integer", example=1),
     *                             @OA\Property(property="price", type="number", format="float", example=1299.99),
     *                             @OA\Property(property="total", type="number", format="float", example=1299.99)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Not enough stock for iPhone 14 Pro. Available: 5")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(type="string", example="The items field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to create order"),
     *             @OA\Property(property="error", type="string", example="Error message")
     *         )
     *     )
     * )
     */
    public function store(OrderRequest $request)
    {
        try {
            DB::beginTransaction();
            
            $orderTotal = 0;
            $items = [];
            
            // Calculate order total and prepare items
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Check if enough stock
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Not enough stock for {$product->name}. Available: {$product->stock}"
                    ], 400);
                }
                
                $itemTotal = $product->price * $item['quantity'];
                $orderTotal += $itemTotal;
                
                $items[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $itemTotal,
                ];
                
                // Reduce product stock
                $product->stock -= $item['quantity'];
                $product->save();
            }
            
            // Create order
            $order = Order::create([
                'user_id' => Auth::id(),
                'order_number' => 'ORD-' . Str::upper(Str::random(10)),
                'total' => $orderTotal,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'shipping_address' => $request->shipping_address,
                'billing_address' => $request->billing_address,
                'notes' => $request->notes,
            ]);
            
            // Create order items
            foreach ($items as $item) {
                $order->items()->create($item);
            }
            
            // Send order confirmation email
            $order->load('items.product', 'user');
            $this->notificationService->sendOrderConfirmation($order);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order->load('items')
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     summary="Get order details",
     *     description="Get detailed information about a specific order. Regular users can only view their own orders.",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="order_number", type="string", example="ORD-ABC123XYZ"),
     *                 @OA\Property(property="total", type="number", format="float", example=1299.99),
     *                 @OA\Property(property="status", type="string", example="pending", enum={"pending", "processing", "completed", "cancelled"}),
     *                 @OA\Property(property="payment_status", type="string", example="pending", enum={"pending", "paid", "failed"}),
     *                 @OA\Property(property="payment_method", type="string", example="credit_card"),
     *                 @OA\Property(property="shipping_address", type="object"),
     *                 @OA\Property(property="billing_address", type="object"),
     *                 @OA\Property(property="notes", type="string", example="Please leave at the front door"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="order_id", type="integer", example=1),
     *                         @OA\Property(property="product_id", type="integer", example=1),
     *                         @OA\Property(property="product_name", type="string", example="iPhone 14 Pro"),
     *                         @OA\Property(property="quantity", type="integer", example=1),
     *                         @OA\Property(property="price", type="number", format="float", example=1299.99),
     *                         @OA\Property(property="total", type="number", format="float", example=1299.99)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::with('items')->findOrFail($id);
        
        // Check if user has permission to view this order
        if (!$user->hasRole('admin') && $order->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
    }

    /**
     * Update the specified order status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Patch(
     *     path="/api/admin/orders/{id}/status",
     *     summary="Update order status",
     *     description="Update the status of an order. Admin only.",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "processing", "completed", "cancelled"},
     *                 example="processing"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Order status updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="status", type="array", @OA\Items(type="string", example="The selected status is invalid."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);
        
        $user = Auth::user();
        
        // Only admin can update order status
        if (!$user->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $order = Order::findOrFail($id);
        $previousStatus = $order->status;
        $order->status = $request->status;
        
        // If order is cancelled, restore product stock
        if ($request->status === 'cancelled' && $order->status !== 'cancelled') {
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->stock += $item->quantity;
                    $product->save();
                }
            }
        }
        
        $order->save();
        
        // Send order status update email
        $order->load('user');
        $this->notificationService->sendOrderStatusUpdate($order, $previousStatus);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Order status updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Update the payment status for an order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Patch(
     *     path="/api/orders/{id}/payment",
     *     summary="Update payment status",
     *     description="Update the payment status and transaction information for an order.",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Order ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_status"},
     *             @OA\Property(
     *                 property="payment_status",
     *                 type="string",
     *                 enum={"pending", "paid", "failed"},
     *                 example="paid"
     *             ),
     *             @OA\Property(property="transaction_id", type="string", example="TXN123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Payment status updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="payment_status",
     *                     type="array",
     *                     @OA\Items(type="string", example="The selected payment status is invalid.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function updatePayment(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed',
            'transaction_id' => 'nullable|string',
        ]);
        
        $user = Auth::user();
        $order = Order::findOrFail($id);
        
        // Check if user has permission to update this order
        if (!$user->hasRole('admin') && $order->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $order->payment_status = $request->payment_status;
        
        if ($request->has('transaction_id')) {
            $order->transaction_id = $request->transaction_id;
        }
        
        $order->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Payment status updated successfully',
            'data' => $order
        ]);
    }

    /**
     * Return a list of all orders for admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/admin/orders",
     *     summary="List all orders (admin)",
     *     description="Returns a list of all orders with filtering capabilities. Admin only.",
     *     tags={"Orders"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by order status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "processing", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="payment_status",
     *         in="query",
     *         description="Filter by payment status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "paid", "failed"})
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter orders from this date (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter orders to this date (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="order_number", type="string", example="ORD-ABC123XYZ"),
     *                         @OA\Property(property="total", type="number", format="float", example=1299.99),
     *                         @OA\Property(property="status", type="string", example="pending"),
     *                         @OA\Property(property="payment_status", type="string", example="pending"),
     *                         @OA\Property(property="payment_method", type="string", example="credit_card"),
     *                         @OA\Property(property="shipping_address", type="object"),
     *                         @OA\Property(property="billing_address", type="object"),
     *                         @OA\Property(property="notes", type="string", example="Please leave at the front door"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(
     *                             property="items",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="order_id", type="integer", example=1),
     *                                 @OA\Property(property="product_id", type="integer", example=1),
     *                                 @OA\Property(property="product_name", type="string", example="iPhone 14 Pro"),
     *                                 @OA\Property(property="quantity", type="integer", example=1),
     *                                 @OA\Property(property="price", type="number", format="float", example=1299.99),
     *                                 @OA\Property(property="total", type="number", format="float", example=1299.99)
     *                             )
     *                         ),
     *                         @OA\Property(
     *                             property="user",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="John Doe"),
     *                             @OA\Property(property="email", type="string", example="john@example.com")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function adminOrders(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $query = Order::with('items', 'user')->latest();
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        
        $orders = $query->paginate($request->per_page ?? 10);
        
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }
}
