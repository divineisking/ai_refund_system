<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Models\RefundRequest;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_model_relationships_and_casts(): void
    {
        $customer = Customer::create([
            'customer_id' => 'CUST-TEST-1',
            'name' => 'Unit Test User',
            'email' => 'unit@example.com',
            'risk_tier' => 'LOW',
            'fraud_score' => 15,
            'total_orders' => 3,
            'return_rate' => 0.25,
            'account_created_at' => '2025-01-01',
        ]);

        $order = Order::create([
            'order_id' => 'ORD-TEST-1',
            'customer_id' => 'CUST-TEST-1',
            'item_name' => 'Test Item',
            'category' => 'Testing',
            'price' => 49.99,
            'is_final_sale' => false,
            'is_damaged' => false,
            'return_window_days' => 30,
            'order_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
            'delivered_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'status' => 'DELIVERED',
        ]);

        $refund = RefundRequest::create([
            'request_id' => 'REF-TEST-1',
            'customer_id' => 'CUST-TEST-1',
            'order_id' => 'ORD-TEST-1',
            'customer_message' => 'Test message',
            'decision' => 'APPROVED',
            'policy_clause_triggered' => 'Clause 6: Standard Valid Return',
            'confidence_score' => 0.95,
            'customer_explanation' => 'Test explanation',
            'internal_reasoning' => 'Test reasoning',
            'suggested_action' => 'Test action',
            'prompt_injection_detected' => false,
            'prompt_injection_flags' => ['test_flag'],
            'evaluation_mode' => 'DETERMINISTIC_FALLBACK',
            'raw_model_response' => ['key' => 'value'],
            'status' => 'RESOLVED_AUTOMATIC',
        ]);

        // Customer relationships
        $this->assertTrue($customer->orders->contains($order));
        $this->assertTrue($customer->refundRequests->contains($refund));

        // Customer casts
        $this->assertIsInt($customer->fraud_score);
        $this->assertIsInt($customer->total_orders);
        $this->assertIsFloat($customer->return_rate);
        $this->assertInstanceOf(Carbon::class, $customer->account_created_at);
    }

    public function test_order_model_relationships_and_accessors(): void
    {
        $customer = Customer::create([
            'customer_id' => 'CUST-TEST-2',
            'name' => 'Order Test User',
            'email' => 'ordertest@example.com',
            'risk_tier' => 'MEDIUM',
            'fraud_score' => 40,
            'total_orders' => 1,
            'return_rate' => 0.0,
            'account_created_at' => '2025-02-01',
        ]);

        $order = Order::create([
            'order_id' => 'ORD-TEST-2',
            'customer_id' => 'CUST-TEST-2',
            'item_name' => 'Expensive Item',
            'category' => 'Electronics',
            'price' => 699.99,
            'is_final_sale' => true,
            'is_damaged' => true,
            'return_window_days' => 14,
            'order_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
            'delivered_date' => Carbon::now()->subDays(7)->format('Y-m-d'),
            'status' => 'DELIVERED',
        ]);

        // BelongsTo relationship
        $this->assertEquals($customer->customer_id, $order->customer->customer_id);

        // Accessor days_since_delivery
        $this->assertEquals(7, $order->days_since_delivery);

        // Casts
        $this->assertIsFloat($order->price);
        $this->assertTrue($order->is_final_sale);
        $this->assertTrue($order->is_damaged);
        $this->assertIsInt($order->return_window_days);
    }

    public function test_refund_request_model_relationships_and_casts(): void
    {
        $customer = Customer::create([
            'customer_id' => 'CUST-TEST-3',
            'name' => 'Refund User',
            'email' => 'refund@example.com',
            'risk_tier' => 'HIGH',
            'fraud_score' => 90,
            'total_orders' => 10,
            'return_rate' => 0.50,
            'account_created_at' => '2024-01-01',
        ]);

        $order = Order::create([
            'order_id' => 'ORD-TEST-3',
            'customer_id' => 'CUST-TEST-3',
            'item_name' => 'Refund Item',
            'category' => 'General',
            'price' => 99.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'return_window_days' => 30,
            'order_date' => Carbon::now()->subDays(15)->format('Y-m-d'),
            'delivered_date' => Carbon::now()->subDays(12)->format('Y-m-d'),
            'status' => 'DELIVERED',
        ]);

        $refund = RefundRequest::create([
            'request_id' => 'REF-TEST-3',
            'customer_id' => 'CUST-TEST-3',
            'order_id' => 'ORD-TEST-3',
            'customer_message' => 'Help me refund',
            'decision' => 'ESCALATED',
            'policy_clause_triggered' => 'Clause 5: Fraud',
            'confidence_score' => 0.88,
            'customer_explanation' => 'Escalated explanation',
            'internal_reasoning' => 'Escalated reasoning',
            'suggested_action' => 'Escalate to supervisor',
            'prompt_injection_detected' => true,
            'prompt_injection_flags' => ['jailbreak_pattern'],
            'evaluation_mode' => 'GEMINI_AI',
            'raw_model_response' => ['raw' => 'response'],
            'status' => 'PENDING_HUMAN_REVIEW',
        ]);

        // Relationships
        $this->assertEquals('CUST-TEST-3', $refund->customer->customer_id);
        $this->assertEquals('ORD-TEST-3', $refund->order->order_id);

        // Casts
        $this->assertIsFloat($refund->confidence_score);
        $this->assertTrue($refund->prompt_injection_detected);
        $this->assertIsArray($refund->prompt_injection_flags);
        $this->assertEquals(['jailbreak_pattern'], $refund->prompt_injection_flags);
        $this->assertIsArray($refund->raw_model_response);
    }

    public function test_database_seeder_assertion_guarantees_minimum_15_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $customerCount = Customer::count();
        $orderCount = Order::count();

        $this->assertGreaterThanOrEqual(15, $customerCount);
        $this->assertGreaterThanOrEqual(15, $orderCount);

        // Verify CUST-1001 through CUST-1015 all exist
        for ($i = 1001; $i <= 1015; $i++) {
            $custId = "CUST-{$i}";
            $this->assertTrue(
                Customer::where('customer_id', $custId)->exists(),
                "Customer {$custId} missing from seed data."
            );
        }

        // Verify ORD-8001 through ORD-8015 all exist
        for ($i = 8001; $i <= 8015; $i++) {
            $ordId = "ORD-{$i}";
            $this->assertTrue(
                Order::where('order_id', $ordId)->exists(),
                "Order {$ordId} missing from seed data."
            );
        }
    }
}
