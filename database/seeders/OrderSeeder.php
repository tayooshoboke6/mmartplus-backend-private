<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Database\Seeders\CategorySeeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get some users with 'customer' role or create them if needed
        $customers = User::query()->take(3)->get();
        
        // Filter to only users with the customer role or those without any role
        $validCustomers = collect();
        foreach ($customers as $customer) {
            if ($customer->hasRole('customer') || !$customer->roles->count()) {
                if (!$customer->hasRole('customer')) {
                    try {
                        $customer->assignRole('customer');
                    } catch (\Exception $e) {
                        // Role might not exist yet
                        continue;
                    }
                }
                $validCustomers->push($customer);
            }
        }
        
        // If we don't have 3 customers, create them
        if ($validCustomers->count() < 3) {
            for ($i = $validCustomers->count(); $i < 3; $i++) {
                $newCustomer = User::factory()->create([
                    'name' => 'Test Customer ' . ($i + 1),
                    'email' => 'customer' . ($i + 1) . '@example.com',
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]);
                
                try {
                    $newCustomer->assignRole('customer');
                } catch (\Exception $e) {
                    // Role might not exist yet
                }
                
                $validCustomers->push($newCustomer);
            }
        }
        
        // Get some products
        $products = Product::take(5)->get();
        
        // Get categories
        $categories = \App\Models\Category::all();
        if ($categories->isEmpty()) {
            // Run the category seeder if no categories exist
            $this->call(CategorySeeder::class);
            $categories = \App\Models\Category::all();
        }
        
        // If we don't have enough products, create them
        if ($products->count() < 5) {
            for ($i = $products->count(); $i < 5; $i++) {
                try {
                    $products->push(Product::create([
                        'name' => 'Test Product ' . ($i + 1),
                        'description' => 'This is a test product',
                        'price' => rand(1000, 10000) / 100,
                        'stock' => rand(10, 100),
                        'sku' => 'TP' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                        'category_id' => $categories->random()->id,
                        'is_active' => true
                    ]));
                } catch (\Exception $e) {
                    // Log the error but continue
                    \Log::error('Error creating test product: ' . $e->getMessage());
                }
            }
        }
        
        // Order statuses
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
        
        // Create 15 orders with random items
        for ($i = 0; $i < 15; $i++) {
            $customer = $validCustomers->random();
            $orderItems = [];
            $total = 0;
            
            // Add 1-3 random products to the order
            $orderProductCount = rand(1, 3);
            $selectedProducts = $products->random($orderProductCount);
            
            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 3);
                $itemTotal = $product->price * $quantity;
                $total += $itemTotal;
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'price' => $product->price,
                    'total' => $itemTotal
                ];
            }
            
            // Create order
            $order = Order::create([
                'user_id' => $customer->id,
                'order_number' => 'ORD-' . Str::upper(Str::random(10)),
                'total' => $total,
                'status' => $statuses[array_rand($statuses)],
                'payment_status' => array_rand(['pending' => 'pending', 'paid' => 'paid']),
                'payment_method' => array_rand(['credit_card' => 'credit_card', 'paypal' => 'paypal', 'cash_on_delivery' => 'cash_on_delivery']),
                'shipping_address' => [
                    'first_name' => $customer->name,
                    'last_name' => 'Customer',
                    'address' => '123 Test Street',
                    'city' => 'Test City',
                    'state' => 'TS',
                    'postal_code' => '12345',
                    'country' => 'Test Country',
                    'phone' => '555-123-4567'
                ],
                'billing_address' => [
                    'first_name' => $customer->name,
                    'last_name' => 'Customer',
                    'address' => '123 Test Street',
                    'city' => 'Test City',
                    'state' => 'TS',
                    'postal_code' => '12345',
                    'country' => 'Test Country',
                    'phone' => '555-123-4567'
                ],
                'notes' => $i % 3 === 0 ? 'Please deliver ASAP' : null,
                'created_at' => now()->subDays(rand(1, 30))
            ]);
            
            // Create order items
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }
        }
    }
}
