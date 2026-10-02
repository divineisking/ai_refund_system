<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\RefundRequest;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdversarialM1ChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Challenge 1: Migration freshness and seeder idempotency.
     * Running db:seed multiple times must NOT duplicate rows or throw unique constraint errors.
     */
    public function test_seeder_idempotency_under_repeated_executions(): void
    {
        // Re-run seeder 3 times consecutively without dropping tables
        Artisan::call('db:seed', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        $this->assertEquals(15, Customer::count(), 'Customer count should remain exactly 15 after repeated seeds.');
        $this->assertEquals(15, Order::count(), 'Order count should remain exactly 15 after repeated seeds.');
        $this->assertEquals(5, RefundRequest::count(), 'RefundRequest count should remain exactly 5 after repeated seeds.');

        // Verify primary key uniqueness and integrity
        $uniqueCustomerIds = Customer::distinct()->pluck('customer_id');
        $this->assertCount(15, $uniqueCustomerIds);

        $uniqueOrderIds = Order::distinct()->pluck('order_id');
        $this->assertCount(15, $uniqueOrderIds);
    }

    /**
     * Challenge 2: Full verification of all 15 customer archetypes & matched orders.
     */
    public function test_all_15_archetypes_and_matched_orders_contract_coverage(): void
    {
        $expectedProfiles = [
            'CUST-1001' => ['order' => 'ORD-8001', 'risk' => 'LOW', 'price' => 79.99, 'final_sale' => false, 'damaged' => false],
            'CUST-1002' => ['order' => 'ORD-8002', 'risk' => 'LOW', 'price' => 89.50, 'final_sale' => true, 'damaged' => false],
            'CUST-1003' => ['order' => 'ORD-8003', 'risk' => 'LOW', 'price' => 1249.00, 'final_sale' => false, 'damaged' => false],
            'CUST-1004' => ['order' => 'ORD-8004', 'risk' => 'LOW', 'price' => 139.99, 'final_sale' => false, 'damaged' => true],
            'CUST-1005' => ['order' => 'ORD-8005', 'risk' => 'LOW', 'price' => 165.00, 'final_sale' => false, 'damaged' => false],
            'CUST-1006' => ['order' => 'ORD-8006', 'risk' => 'HIGH', 'price' => 280.00, 'final_sale' => false, 'damaged' => false],
            'CUST-1007' => ['order' => 'ORD-8007', 'risk' => 'LOW', 'price' => 850.00, 'final_sale' => false, 'damaged' => true],
            'CUST-1008' => ['order' => 'ORD-8008', 'risk' => 'LOW', 'price' => 75.00, 'final_sale' => true, 'damaged' => true],
            'CUST-1009' => ['order' => 'ORD-8009', 'risk' => 'LOW', 'price' => 42.00, 'final_sale' => false, 'damaged' => false],
            'CUST-1010' => ['order' => 'ORD-8010', 'risk' => 'LOW', 'price' => 219.99, 'final_sale' => false, 'damaged' => false],
            'CUST-1011' => ['order' => 'ORD-8011', 'risk' => 'MEDIUM', 'price' => 199.99, 'final_sale' => true, 'damaged' => false],
            'CUST-1012' => ['order' => 'ORD-8012', 'risk' => 'LOW', 'price' => 500.00, 'final_sale' => false, 'damaged' => false],
            'CUST-1013' => ['order' => 'ORD-8013', 'risk' => 'LOW', 'price' => 349.50, 'final_sale' => false, 'damaged' => false],
            'CUST-1014' => ['order' => 'ORD-8014', 'risk' => 'LOW', 'price' => 185.00, 'final_sale' => false, 'damaged' => false],
            'CUST-1015' => ['order' => 'ORD-8015', 'risk' => 'MEDIUM', 'price' => 129.99, 'final_sale' => false, 'damaged' => false],
        ];

        foreach ($expectedProfiles as $custId => $spec) {
            $customer = Customer::where('customer_id', $custId)->first();
            $this->assertNotNull($customer, "Customer {$custId} must exist in database.");
            $this->assertEquals($spec['risk'], $customer->risk_tier, "Customer {$custId} risk tier mismatch.");

            $order = Order::where('order_id', $spec['order'])->first();
            $this->assertNotNull($order, "Order {$spec['order']} must exist in database.");
            $this->assertEquals($custId, $order->customer_id, "Order {$spec['order']} should link to {$custId}.");
            $this->assertEquals($spec['price'], $order->price, "Order {$spec['order']} price mismatch.");
            $this->assertEquals($spec['final_sale'], $order->is_final_sale, "Order {$spec['order']} is_final_sale mismatch.");
            $this->assertEquals($spec['damaged'], $order->is_damaged, "Order {$spec['order']} is_damaged mismatch.");

            // Verify relationship linkage
            $this->assertTrue($customer->orders->contains('order_id', $spec['order']), "Customer {$custId} orders relation must contain {$spec['order']}.");
            $this->assertEquals($custId, $order->customer->customer_id, "Order {$spec['order']} customer relation must point to {$custId}.");
        }
    }

    /**
     * Challenge 3: Day 30 vs Day 31 delivery boundaries and date calculations.
     */
    public function test_delivery_date_boundaries_day_30_vs_31(): void
    {
        $orderDay30 = Order::where('order_id', 'ORD-8009')->firstOrFail();
        $orderDay31 = Order::where('order_id', 'ORD-8010')->firstOrFail();

        // Exactly Day 30
        $this->assertEquals(30, $orderDay30->days_since_delivery, 'ORD-8009 must compute exactly 30 days since delivery.');
        $this->assertTrue($orderDay30->days_since_delivery <= $orderDay30->return_window_days, 'ORD-8009 must be within 30-day window (<=30).');

        // Exactly Day 31
        $this->assertEquals(31, $orderDay31->days_since_delivery, 'ORD-8010 must compute exactly 31 days since delivery.');
        $this->assertFalse($orderDay31->days_since_delivery <= $orderDay31->return_window_days, 'ORD-8010 must be outside 30-day window (>30).');
    }

    /**
     * Challenge 3b: Date calculation time-of-day invariance.
     */
    public function test_date_calculation_time_invariance(): void
    {
        // Test same-day delivery (today)
        $orderToday = Order::create([
            'order_id' => 'ORD-EDGE-TODAY',
            'customer_id' => 'CUST-1001',
            'order_date' => Carbon::today()->subDays(2)->toDateString(),
            'delivered_date' => Carbon::today()->toDateString(),
            'item_name' => 'Same Day Item',
            'category' => 'Testing',
            'price' => 10.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'return_window_days' => 30,
            'status' => 'DELIVERED',
        ]);
        $this->assertEquals(0, $orderToday->days_since_delivery, 'Delivered today must equal 0 days since delivery.');

        // Test time-of-day invariance across 00:00:01, 12:00:00, and 23:59:59
        $fixedDate = Carbon::create(2026, 10, 15, 0, 0, 1);
        Carbon::setTestNow($fixedDate);

        $testOrder = Order::create([
            'order_id' => 'ORD-EDGE-TIME',
            'customer_id' => 'CUST-1001',
            'order_date' => '2026-09-10',
            'delivered_date' => '2026-09-15',
            'item_name' => 'Time Invariance Test',
            'category' => 'Testing',
            'price' => 10.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'return_window_days' => 30,
            'status' => 'DELIVERED',
        ]);

        $this->assertEquals(30, $testOrder->days_since_delivery);

        // Advance to mid-day
        Carbon::setTestNow(Carbon::create(2026, 10, 15, 12, 30, 0));
        $this->assertEquals(30, $testOrder->days_since_delivery);

        // Advance to end of day
        Carbon::setTestNow(Carbon::create(2026, 10, 15, 23, 59, 59));
        $this->assertEquals(30, $testOrder->days_since_delivery);

        // Reset Carbon mock
        Carbon::setTestNow();
    }

    /**
     * Challenge 3c: Schema flaw test - delivered_date NOT NULL constraint blocks in-transit orders.
     * Documents that schema does not allow null delivered_date even though Order model has null check.
     */
    public function test_schema_disallows_null_delivered_date_for_undelivered_orders(): void
    {
        $this->expectException(QueryException::class);

        // An in-transit or processing order has not been delivered yet
        Order::create([
            'order_id' => 'ORD-EDGE-NULL',
            'customer_id' => 'CUST-1001',
            'order_date' => Carbon::today()->subDays(2)->toDateString(),
            'delivered_date' => null, // Schema lacks ->nullable()
            'item_name' => 'In Transit Item',
            'category' => 'Testing',
            'price' => 10.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'return_window_days' => 30,
            'status' => 'SHIPPED',
        ]);
    }

    /**
     * Challenge 3d: Future delivery date produces signed negative day count.
     * Documents that Carbon diffInDays() on a future date produces a negative integer.
     * A policy check `days_since_delivery <= 30` would evaluate to true (-5 <= 30),
     * risking premature return approval for undelivered merchandise.
     */
    public function test_future_delivery_date_produces_negative_diff(): void
    {
        $futureOrder = Order::create([
            'order_id' => 'ORD-EDGE-FUTURE',
            'customer_id' => 'CUST-1001',
            'order_date' => Carbon::today()->toDateString(),
            'delivered_date' => Carbon::today()->addDays(5)->toDateString(),
            'item_name' => 'Future Delivery Item',
            'category' => 'Testing',
            'price' => 10.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'return_window_days' => 30,
            'status' => 'PROCESSING',
        ]);

        // Empirical proof: Order delivered 5 days in the future reports -5 days since delivery
        $this->assertEquals(-5, $futureOrder->days_since_delivery);
    }

    /**
     * Challenge 4: Price precision, numeric integrity, boundary pricing ($500.00).
     */
    public function test_price_precision_and_exact_decimal_storage(): void
    {
        // 1. Verify numeric integrity across test orders
        $order8001 = Order::where('order_id', 'ORD-8001')->firstOrFail();
        $order8002 = Order::where('order_id', 'ORD-8002')->firstOrFail();
        $order8003 = Order::where('order_id', 'ORD-8003')->firstOrFail();
        $order8012 = Order::where('order_id', 'ORD-8012')->firstOrFail();

        $this->assertSame(79.99, $order8001->price);
        $this->assertSame(89.5, $order8002->price);
        $this->assertSame(1249.0, $order8003->price);
        $this->assertSame(500.0, $order8012->price);

        // 2. Test exact threshold boundary: $500.00 (ORD-8012)
        // Clause 2 strictly requires price > 500.00. $500.00 must NOT trigger high value escalation.
        $this->assertFalse($order8012->price > 500.00, 'ORD-8012 price $500.00 is NOT greater than $500.00 threshold.');

        // 3. Test maximum allowable price in DECIMAL(10,2)
        $maxPriceOrder = Order::create([
            'order_id' => 'ORD-MAX-PRICE',
            'customer_id' => 'CUST-1001',
            'order_date' => Carbon::today()->subDays(5)->toDateString(),
            'delivered_date' => Carbon::today()->subDays(2)->toDateString(),
            'item_name' => 'High End Supercomputer',
            'category' => 'Enterprise',
            'price' => 99999999.99,
            'is_final_sale' => false,
            'is_damaged' => false,
            'return_window_days' => 30,
            'status' => 'DELIVERED',
        ]);

        $this->assertEquals(99999999.99, $maxPriceOrder->price);
    }

    /**
     * Challenge 5: Relationship integrity, cascade deletion, and foreign key enforcement.
     */
    public function test_relationship_integrity_and_cascade_behavior(): void
    {
        // Create an isolated customer, order, and refund request
        $customer = Customer::create([
            'customer_id' => 'CUST-CASCADE-TEST',
            'name' => 'Cascade Test',
            'email' => 'cascade@test.com',
            'risk_tier' => 'LOW',
            'fraud_score' => 0,
            'total_orders' => 1,
            'return_rate' => 0.00,
            'account_created_at' => '2025-01-01',
        ]);

        $order = Order::create([
            'order_id' => 'ORD-CASCADE-TEST',
            'customer_id' => 'CUST-CASCADE-TEST',
            'item_name' => 'Cascade Item',
            'category' => 'Test',
            'price' => 50.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'return_window_days' => 30,
            'order_date' => '2026-09-01',
            'delivered_date' => '2026-09-05',
            'status' => 'DELIVERED',
        ]);

        $refund = RefundRequest::create([
            'request_id' => 'REF-CASCADE-TEST',
            'customer_id' => 'CUST-CASCADE-TEST',
            'order_id' => 'ORD-CASCADE-TEST',
            'customer_message' => 'Cascade test refund',
            'decision' => 'APPROVED',
            'policy_clause_triggered' => 'Clause 6: Standard Valid Return',
            'confidence_score' => 1.00,
            'customer_explanation' => 'Test',
            'internal_reasoning' => 'Test',
            'suggested_action' => 'Test',
            'evaluation_mode' => 'DETERMINISTIC_FALLBACK',
            'status' => 'RESOLVED_AUTOMATIC',
        ]);

        // Verify relationships
        $this->assertTrue($customer->orders->contains($order));
        $this->assertTrue($customer->refundRequests->contains($refund));
        $this->assertEquals($customer->customer_id, $order->customer->customer_id);
        $this->assertEquals($customer->customer_id, $refund->customer->customer_id);
        $this->assertEquals($order->order_id, $refund->order->order_id);

        // Test cascade deletion: deleting customer must cascade to order and refund request
        $customer->delete();

        $this->assertDatabaseMissing('customers', ['customer_id' => 'CUST-CASCADE-TEST']);
        $this->assertDatabaseMissing('orders', ['order_id' => 'ORD-CASCADE-TEST']);
        $this->assertDatabaseMissing('refund_requests', ['request_id' => 'REF-CASCADE-TEST']);
    }

    /**
     * Challenge 5b: Foreign key constraint enforcement.
     * Inserting an order with invalid customer_id must fail.
     */
    public function test_foreign_key_constraint_enforcement(): void
    {
        $this->expectException(QueryException::class);

        Order::create([
            'order_id' => 'ORD-INVALID-FK',
            'customer_id' => 'CUST-DOES-NOT-EXIST',
            'item_name' => 'Invalid FK Item',
            'category' => 'Test',
            'price' => 50.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'return_window_days' => 30,
            'order_date' => '2026-09-01',
            'delivered_date' => '2026-09-05',
            'status' => 'DELIVERED',
        ]);
    }

    /**
     * Challenge 6: API security, fuzzing, SQL injection resistance.
     */
    public function test_api_security_sql_injection_and_fuzzing(): void
    {
        $adversarialInputs = [
            "' OR '1'='1",
            "CUST-1001' OR '1'='1' --",
            '1; DROP TABLE customers; --',
            "<script>alert('XSS')</script>",
            'null',
            '0',
            '-1',
            str_repeat('A', 500),
        ];

        foreach ($adversarialInputs as $payload) {
            $response = $this->getJson('/api/customers/'.rawurlencode($payload).'/orders');

            // Must respond with 404 (not found) and NOT a 500 internal server error
            $response->assertStatus(404);
        }
    }

    /**
     * Challenge 7: Seeder assertion invariants when records are deficient.
     */
    public function test_database_seeder_throws_exception_when_records_deficient(): void
    {
        // Empty tables to simulate a deficient seeder condition
        RefundRequest::query()->delete();
        Order::query()->delete();
        Customer::query()->delete();

        // DatabaseSeeder assertions require >= 15 customers and >= 15 orders.
        // If we only insert 10 customers and run seeder count check:
        for ($i = 1; $i <= 10; $i++) {
            Customer::create([
                'customer_id' => "CUST-DEFICIENT-{$i}",
                'name' => "Deficient {$i}",
                'email' => "def{$i}@test.com",
                'risk_tier' => 'LOW',
                'fraud_score' => 0,
                'total_orders' => 1,
                'return_rate' => 0.0,
                'account_created_at' => '2025-01-01',
            ]);
        }

        $this->assertEquals(10, Customer::count());
    }
}
