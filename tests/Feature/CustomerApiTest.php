<?php

namespace Tests\Feature;

use App\Models\Customer;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\RefundRequestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CustomerSeeder::class);
        $this->seed(OrderSeeder::class);
        $this->seed(RefundRequestSeeder::class);
    }

    public function test_health_check_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'database' => 'connected',
                'version' => '1.0.0',
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'database',
                'version',
            ]);
    }

    public function test_customers_endpoint_returns_all_15_seeded_profiles(): void
    {
        $response = $this->getJson('/api/customers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(15, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'customer_id',
                        'name',
                        'email',
                        'risk_tier',
                        'fraud_score',
                        'total_orders',
                        'return_rate',
                        'account_created_at',
                    ],
                ],
            ]);
    }

    public function test_customers_endpoint_includes_diverse_risk_profiles(): void
    {
        $response = $this->getJson('/api/customers');
        $customers = collect($response->json('data'));

        // Low risk archetype
        $lowRisk = $customers->firstWhere('customer_id', 'CUST-1001');
        $this->assertNotNull($lowRisk);
        $this->assertEquals('Alice Walker', $lowRisk['name']);
        $this->assertEquals('LOW', $lowRisk['risk_tier']);
        $this->assertEquals(5, $lowRisk['fraud_score']);

        // High risk archetype (Felix Sterling)
        $highRisk = $customers->firstWhere('customer_id', 'CUST-1006');
        $this->assertNotNull($highRisk);
        $this->assertEquals('Felix Sterling', $highRisk['name']);
        $this->assertEquals('HIGH', $highRisk['risk_tier']);
        $this->assertEquals(88, $highRisk['fraud_score']);

        // Medium risk archetype (Kevin Patel)
        $medRisk = $customers->firstWhere('customer_id', 'CUST-1011');
        $this->assertNotNull($medRisk);
        $this->assertEquals('Kevin Patel', $medRisk['name']);
        $this->assertEquals('MEDIUM', $medRisk['risk_tier']);
        $this->assertEquals(45, $medRisk['fraud_score']);
    }

    public function test_customer_orders_endpoint_returns_orders_by_customer_id(): void
    {
        $response = $this->getJson('/api/customers/CUST-1001/orders');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'order_id',
                        'customer_id',
                        'item_name',
                        'category',
                        'price',
                        'is_final_sale',
                        'is_damaged',
                        'days_since_delivery',
                        'return_window_days',
                        'status',
                    ],
                ],
            ]);

        $order = $response->json('data.0');
        $this->assertEquals('ORD-8001', $order['order_id']);
        $this->assertEquals('CUST-1001', $order['customer_id']);
        $this->assertEquals('Wireless Noise-Canceling Earbuds', $order['item_name']);
        $this->assertEquals(79.99, $order['price']);
        $this->assertFalse($order['is_final_sale']);
        $this->assertFalse($order['is_damaged']);
    }

    public function test_customer_orders_endpoint_returns_orders_by_numeric_primary_key(): void
    {
        $customer = Customer::where('customer_id', 'CUST-1003')->firstOrFail();

        $response = $this->getJson("/api/customers/{$customer->id}/orders");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $order = $response->json('data.0');
        $this->assertEquals('ORD-8003', $order['order_id']);
        $this->assertEquals(1249.00, $order['price']);
    }

    public function test_customer_orders_endpoint_returns_404_for_nonexistent_customer(): void
    {
        $response = $this->getJson('/api/customers/CUST-NONEXISTENT/orders');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_orders_endpoint_flags_final_sale_and_damage_accurately(): void
    {
        // Final sale order (ORD-8002)
        $response1 = $this->getJson('/api/customers/CUST-1002/orders');
        $response1->assertStatus(200);
        $this->assertTrue($response1->json('data.0.is_final_sale'));
        $this->assertFalse($response1->json('data.0.is_damaged'));

        // Damaged order (ORD-8004)
        $response2 = $this->getJson('/api/customers/CUST-1004/orders');
        $response2->assertStatus(200);
        $this->assertFalse($response2->json('data.0.is_final_sale'));
        $this->assertTrue($response2->json('data.0.is_damaged'));

        // Final sale AND Damaged order (ORD-8008)
        $response3 = $this->getJson('/api/customers/CUST-1008/orders');
        $response3->assertStatus(200);
        $this->assertTrue($response3->json('data.0.is_final_sale'));
        $this->assertTrue($response3->json('data.0.is_damaged'));
    }

    public function test_orders_endpoint_computes_days_since_delivery(): void
    {
        $response = $this->getJson('/api/customers/CUST-1001/orders');
        $response->assertStatus(200);

        $days = $response->json('data.0.days_since_delivery');
        $this->assertIsInt($days);
        $this->assertEquals(4, $days);
    }

    /**
     * Verify that looking up a customer by string customer_id does not query or bind
     * against the integer primary key column (preventing PostgreSQL 22P02 bigint error).
     */
    public function test_customer_lookup_by_string_id_does_not_bind_string_to_numeric_id_column(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson('/api/customers/CUST-1001/orders');
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $customerQuery = collect($queries)->first(function ($q) {
            return str_contains($q['query'], 'customers') && str_contains($q['query'], 'customer_id');
        });

        $this->assertNotNull($customerQuery, 'Expected a query on the customers table.');
        $this->assertStringNotContainsString('or "id" = ?', $customerQuery['query']);
        $this->assertStringNotContainsString('or `id` = ?', $customerQuery['query']);
        $this->assertStringNotContainsString('or id = ?', $customerQuery['query']);
        $this->assertCount(1, $customerQuery['bindings']);
        $this->assertEquals(['CUST-1001'], $customerQuery['bindings']);
    }

    /**
     * Verify that looking up a customer by numeric id correctly binds an integer.
     */
    public function test_customer_lookup_by_numeric_id_binds_integer(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson('/api/customers/1/orders');
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $customerQuery = collect($queries)->first(function ($q) {
            return str_contains($q['query'], 'customers') && str_contains($q['query'], 'customer_id');
        });

        $this->assertNotNull($customerQuery);
        $this->assertCount(2, $customerQuery['bindings']);
        $this->assertIsInt($customerQuery['bindings'][1], 'The id binding must be an integer, not string.');
    }
}
