<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/admin/dashboard/stats",
     *     summary="Get dashboard statistics",
     *     tags={"Dashboard"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         description="Start date for statistics (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         description="End date for statistics (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="total_sales",
     *                     type="object",
     *                     @OA\Property(property="value", type="number", format="float", example=10345.75),
     *                     @OA\Property(property="percent_change", type="number", format="float", example=15.2)
     *                 ),
     *                 @OA\Property(
     *                     property="total_orders",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=127),
     *                     @OA\Property(property="percent_change", type="number", format="float", example=8.5)
     *                 ),
     *                 @OA\Property(
     *                     property="new_customers",
     *                     type="object",
     *                     @OA\Property(property="value", type="integer", example=25),
     *                     @OA\Property(property="percent_change", type="number", format="float", example=12.0)
     *                 ),
     *                 @OA\Property(
     *                     property="order_status_distribution",
     *                     type="object",
     *                     @OA\Property(property="pending", type="integer", example=15),
     *                     @OA\Property(property="processing", type="integer", example=32),
     *                     @OA\Property(property="completed", type="integer", example=75),
     *                     @OA\Property(property="cancelled", type="integer", example=5)
     *                 ),
     *                 @OA\Property(
     *                     property="popular_products",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=12),
     *                         @OA\Property(property="name", type="string", example="Smartphone Pro Max"),
     *                         @OA\Property(property="quantity_sold", type="integer", example=42),
     *                         @OA\Property(property="revenue", type="number", format="float", example=21000.00)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="low_stock_products",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Wireless Earbuds"),
     *                         @OA\Property(property="current_stock", type="integer", example=3),
     *                         @OA\Property(property="threshold", type="integer", example=10)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="recent_orders",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=150),
     *                         @OA\Property(property="customer_name", type="string", example="John Smith"),
     *                         @OA\Property(property="status", type="string", example="processing"),
     *                         @OA\Property(property="total", type="number", format="float", example=329.99),
     *                         @OA\Property(property="date", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="sales_trend",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="date", type="string", format="date", example="2023-05-01"),
     *                         @OA\Property(property="sales", type="number", format="float", example=1250.50)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     )
     * )
     */
    public function getStats(Request $request)
    {
        // Default date range - last 30 days
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        
        // Total sales
        $totalSales = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total');
        
        // Order count
        $orderCount = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        
        // Orders by status
        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->get();
        
        // New customers
        $newCustomers = User::role('customer')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
        
        // Popular products
        $popularProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as total_quantity_sold'))
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_quantity_sold', 'desc')
            ->limit(5)
            ->get();
        
        // Recent orders
        $recentOrders = Order::with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        // Low stock products
        $lowStockProducts = Product::where('stock', '<', 10)
            ->where('is_active', true)
            ->limit(5)
            ->get(['id', 'name', 'stock']);
        
        // Sales by day - for chart
        $salesByDay = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total_sales')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Sales by category
        $salesByCategory = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name as category', DB::raw('SUM(order_items.total) as total_sales'))
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('categories.name')
            ->orderBy('total_sales', 'desc')
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_sales' => $totalSales,
                'total_orders' => $orderCount,
                'order_count' => $orderCount,
                'pending_orders' => $ordersByStatus->where('status', 'pending')->first()->count ?? 0,
                'total_customers' => User::role('customer')->count(),
                'new_customers' => $newCustomers,
                'orders_by_status' => $ordersByStatus,
                'recent_orders' => $recentOrders,
                'popular_products' => $popularProducts,
                'low_stock_products' => $lowStockProducts,
                'sales_by_day' => $salesByDay,
                'daily_sales' => $salesByDay,
                'sales_by_category' => $salesByCategory,
                'date_range' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ],
            ]
        ]);
    }
}
