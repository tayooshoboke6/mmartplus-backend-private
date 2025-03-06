<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_belongs_to_a_user()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }
    
    /** @test */
    public function it_has_many_order_items()
    {
        $order = Order::factory()->create();
        OrderItem::factory()->count(3)->create(['order_id' => $order->id]);
        
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $order->items);
        $this->assertCount(3, $order->items);
        $this->assertInstanceOf(OrderItem::class, $order->items->first());
    }
    
    /** @test */
    public function it_casts_address_to_array()
    {
        $shippingAddress = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'postal_code' => '12345',
            'country' => 'USA',
            'phone' => '555-1234'
        ];
        
        $order = Order::factory()->create([
            'shipping_address' => $shippingAddress
        ]);
        
        $this->assertIsArray($order->shipping_address);
        $this->assertEquals('John', $order->shipping_address['first_name']);
        $this->assertEquals('Doe', $order->shipping_address['last_name']);
    }
    
    /** @test */
    public function it_can_determine_if_order_is_cancelled()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $cancelledOrder = Order::factory()->create(['status' => 'cancelled']);
        
        $this->assertFalse($order->isCancelled());
        $this->assertTrue($cancelledOrder->isCancelled());
    }
    
    /** @test */
    public function it_can_determine_if_order_is_completed()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $completedOrder = Order::factory()->create(['status' => 'completed']);
        
        $this->assertFalse($order->isCompleted());
        $this->assertTrue($completedOrder->isCompleted());
    }
    
    /** @test */
    public function it_can_determine_if_order_is_paid()
    {
        $order = Order::factory()->create(['payment_status' => 'pending']);
        $paidOrder = Order::factory()->create(['payment_status' => 'paid']);
        
        $this->assertFalse($order->isPaid());
        $this->assertTrue($paidOrder->isPaid());
    }
    
    /** @test */
    public function it_can_calculate_total_from_items()
    {
        $order = Order::factory()->create(['total' => 0]);
        
        // Create order items with specific prices and quantities
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 10.00,
            'quantity' => 2,
            'total' => 20.00
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 15.50,
            'quantity' => 1,
            'total' => 15.50
        ]);
        
        // Calculate total
        $calculatedTotal = $order->items->sum('total');
        
        $this->assertEquals(35.50, $calculatedTotal);
    }
    
    /** @test */
    public function it_filters_orders_by_status()
    {
        Order::factory()->create(['status' => 'pending']);
        Order::factory()->create(['status' => 'processing']);
        Order::factory()->create(['status' => 'completed']);
        
        $pendingOrders = Order::whereStatus('pending')->get();
        $processingOrders = Order::whereStatus('processing')->get();
        
        $this->assertCount(1, $pendingOrders);
        $this->assertCount(1, $processingOrders);
        $this->assertEquals('pending', $pendingOrders->first()->status);
    }
}
