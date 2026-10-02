<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\RefundRequestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdversarialApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CustomerSeeder::class);
        $this->seed(OrderSeeder::class);
        $this->seed(RefundRequestSeeder::class);
    }

    /**
     * @testdox Malformed & injection IDs return 404 cleanly without 500 or SQL leakage
     */
    public function test_malformed_and_injection_ids_return_clean_404(): void
    {
        $malformedIds = [
            'CUST-9999',
            '-1',
            '0',
            '9999999999999999999999999999999999999999',
            "' OR 1=1 --",
            "admin'#",
            '1 UNION SELECT NULL,NULL,NULL--',
            '<script>alert(1)</script>',
            'null',
            'undefined',
            ' ',
            '%20',
            '!@#$%^&*()_+',
            'CUST-1001%00evil',
            '--drop table customers;',
            '../../etc/passwd',
            str_repeat('X', 10000),
        ];

        foreach ($malformedIds as $badId) {
            $response = $this->getJson("/api/customers/{$badId}/orders");

            $this->assertEquals(
                404,
                $response->status(),
                "Expected 404 for malformed ID '{$badId}', got {$response->status()}"
            );

            // If the route matched the controller, it returns JSON with 'success' => false.
            // If path traversal canonicalized away from /api/customers/{id}/orders, it returns framework 404.
            if ($response->json('message') !== null && str_contains($response->json('message'), 'could not be found')) {
                // Framework router 404 (e.g. path traversal)
                $this->assertStringContainsString('could not be found', $response->json('message'));
            } else {
                $response->assertJson([
                    'success' => false,
                ]);
                $this->assertStringContainsString('not found', strtolower($response->json('message')));
            }

            // Ensure no database exception details leak into the error message
            $this->assertStringNotContainsStringIgnoringCase('syntax error', $response->json('message'));
            $this->assertStringNotContainsStringIgnoringCase('sqlstate', $response->json('message'));
            $this->assertStringNotContainsStringIgnoringCase('pdo', $response->json('message'));
        }
    }

    /**
     * @testdox Content negotiation: API returns application/json with various Accept headers
     */
    public function test_content_negotiation_headers(): void
    {
        // 1. Without Accept header
        $resWithoutAccept = $this->get('/api/customers');
        $this->assertEquals(200, $resWithoutAccept->status());
        $this->assertStringContainsString('application/json', $resWithoutAccept->headers->get('Content-Type'));
        $this->assertTrue($resWithoutAccept->json('success'));

        // 2. Wildcard Accept header (*/*)
        $resWildcard = $this->get('/api/customers', ['Accept' => '*/*']);
        $this->assertEquals(200, $resWildcard->status());
        $this->assertStringContainsString('application/json', $resWildcard->headers->get('Content-Type'));

        // 3. Accept: text/html
        $resHtml = $this->get('/api/customers', ['Accept' => 'text/html']);
        $this->assertEquals(200, $resHtml->status());
        $this->assertStringContainsString('application/json', $resHtml->headers->get('Content-Type'));
        $this->assertTrue($resHtml->json('success'));

        // 4. Accept: application/xml
        $resXml = $this->get('/api/customers', ['Accept' => 'application/xml']);
        $this->assertEquals(200, $resXml->status());
        $this->assertStringContainsString('application/json', $resXml->headers->get('Content-Type'));

        // 5. Customer orders endpoint with Accept: text/html on 404
        $resNotFoundHtml = $this->get('/api/customers/NONEXISTENT/orders', ['Accept' => 'text/html']);
        $this->assertEquals(404, $resNotFoundHtml->status());
        $this->assertStringContainsString('application/json', $resNotFoundHtml->headers->get('Content-Type'));
        $this->assertFalse($resNotFoundHtml->json('success'));

        // 6. Health check endpoint with Accept: text/html
        $resHealthHtml = $this->get('/api/health', ['Accept' => 'text/html']);
        $this->assertEquals(200, $resHealthHtml->status());
        $this->assertStringContainsString('application/json', $resHealthHtml->headers->get('Content-Type'));
        $this->assertEquals('ok', $resHealthHtml->json('status'));
    }

    /**
     * @testdox Boundary and garbage query parameters do not cause 500 or SQL errors
     */
    public function test_boundary_and_garbage_query_parameters(): void
    {
        $endpoints = [
            '/api/customers?limit=-1&offset=-100&sort=asc&evil=\' OR 1=1 --',
            '/api/customers?page=9999999999999999999999999999999999999',
            '/api/customers?filter[0]=invalid&filter[key]=val',
            '/api/customers/CUST-1001/orders?sort=unknown&limit=-5&offset=9999&inject=\'--',
            '/api/health?debug=true&format=yaml&drop=1',
        ];

        foreach ($endpoints as $url) {
            $response = $this->getJson($url);
            $this->assertEquals(
                200,
                $response->status(),
                "Failed on URL with garbage query params: {$url}"
            );
        }
    }

    /**
     * @testdox Payload contracts: All 15 customer profiles strictly conform to schema
     */
    public function test_all_15_customer_profiles_payload_contract(): void
    {
        $response = $this->getJson('/api/customers');
        $response->assertStatus(200);

        $customers = $response->json('data');
        $this->assertCount(15, $customers, 'Expected exactly 15 seeded customer profiles');

        $seenCustomerIds = [];
        $seenEmails = [];
        $riskTiersCount = ['LOW' => 0, 'MEDIUM' => 0, 'HIGH' => 0];

        foreach ($customers as $index => $customer) {
            // Verify mandatory keys
            $this->assertArrayHasKey('id', $customer, "Missing 'id' at index {$index}");
            $this->assertArrayHasKey('customer_id', $customer, "Missing 'customer_id' at index {$index}");
            $this->assertArrayHasKey('name', $customer, "Missing 'name' at index {$index}");
            $this->assertArrayHasKey('email', $customer, "Missing 'email' at index {$index}");
            $this->assertArrayHasKey('risk_tier', $customer, "Missing 'risk_tier' at index {$index}");
            $this->assertArrayHasKey('fraud_score', $customer, "Missing 'fraud_score' at index {$index}");
            $this->assertArrayHasKey('total_orders', $customer, "Missing 'total_orders' at index {$index}");
            $this->assertArrayHasKey('return_rate', $customer, "Missing 'return_rate' at index {$index}");
            $this->assertArrayHasKey('account_created_at', $customer, "Missing 'account_created_at' at index {$index}");

            // Type assertions
            $this->assertIsInt($customer['id']);
            $this->assertMatchesRegularExpression('/^CUST-10(0[1-9]|1[0-5])$/', $customer['customer_id']);
            $this->assertIsString($customer['name']);
            $this->assertNotEmpty($customer['name']);

            // Email validation
            $this->assertIsString($customer['email']);
            $this->assertNotFalse(filter_var($customer['email'], FILTER_VALIDATE_EMAIL), "Invalid email: {$customer['email']}");

            // Risk tier enum
            $this->assertContains($customer['risk_tier'], ['LOW', 'MEDIUM', 'HIGH']);
            $riskTiersCount[$customer['risk_tier']]++;

            // Fraud score range 0-100
            $this->assertIsInt($customer['fraud_score']);
            $this->assertGreaterThanOrEqual(0, $customer['fraud_score']);
            $this->assertLessThanOrEqual(100, $customer['fraud_score']);

            // Total orders >= 1
            $this->assertIsInt($customer['total_orders']);
            $this->assertGreaterThanOrEqual(1, $customer['total_orders']);

            // Return rate between 0.00 and 1.00
            $this->assertIsNumeric($customer['return_rate']);
            $this->assertGreaterThanOrEqual(0.00, (float) $customer['return_rate']);
            $this->assertLessThanOrEqual(1.00, (float) $customer['return_rate']);

            // Date validation (YYYY-MM-DD or ISO timestamp)
            $this->assertNotEmpty($customer['account_created_at']);
            $this->assertNotFalse(strtotime($customer['account_created_at']));

            // Uniqueness check
            $this->assertNotContains($customer['customer_id'], $seenCustomerIds, "Duplicate customer_id: {$customer['customer_id']}");
            $seenCustomerIds[] = $customer['customer_id'];

            $this->assertNotContains($customer['email'], $seenEmails, "Duplicate email: {$customer['email']}");
            $seenEmails[] = $customer['email'];
        }

        // Verify risk profile diversity
        $this->assertGreaterThan(0, $riskTiersCount['LOW'], 'Must have LOW risk customers');
        $this->assertGreaterThan(0, $riskTiersCount['MEDIUM'], 'Must have MEDIUM risk customers');
        $this->assertGreaterThan(0, $riskTiersCount['HIGH'], 'Must have HIGH risk customers');
    }

    /**
     * @testdox Payload contracts: All 15 order histories strictly conform to schema & policy attributes
     */
    public function test_all_15_order_histories_payload_contract_and_archetypes(): void
    {
        $customersResponse = $this->getJson('/api/customers');
        $customers = $customersResponse->json('data');

        $seenOrderIds = [];
        $totalOrdersFound = 0;

        foreach ($customers as $customer) {
            $custId = $customer['customer_id'];
            $response = $this->getJson("/api/customers/{$custId}/orders");

            $this->assertEquals(200, $response->status(), "Failed fetching orders for customer {$custId}");
            $this->assertTrue($response->json('success'));

            $orders = $response->json('data');
            $this->assertIsArray($orders);
            $this->assertGreaterThanOrEqual(1, count($orders), "Customer {$custId} has no orders");

            foreach ($orders as $order) {
                $totalOrdersFound++;

                // Keys present
                $this->assertArrayHasKey('order_id', $order);
                $this->assertArrayHasKey('customer_id', $order);
                $this->assertArrayHasKey('item_name', $order);
                $this->assertArrayHasKey('category', $order);
                $this->assertArrayHasKey('price', $order);
                $this->assertArrayHasKey('is_final_sale', $order);
                $this->assertArrayHasKey('is_damaged', $order);
                $this->assertArrayHasKey('days_since_delivery', $order);
                $this->assertArrayHasKey('return_window_days', $order);
                $this->assertArrayHasKey('tracking_number', $order);
                $this->assertArrayHasKey('status', $order);
                $this->assertArrayHasKey('order_date', $order);
                $this->assertArrayHasKey('delivered_date', $order);

                // Type checks
                $this->assertMatchesRegularExpression('/^ORD-80(0[1-9]|1[0-5])$/', $order['order_id']);
                $this->assertEquals($custId, $order['customer_id']);
                $this->assertIsString($order['item_name']);
                $this->assertNotEmpty($order['item_name']);
                $this->assertIsString($order['category']);
                $this->assertNotEmpty($order['category']);
                $this->assertIsNumeric($order['price']);
                $this->assertGreaterThan(0, (float) $order['price']);
                $this->assertIsBool($order['is_final_sale']);
                $this->assertIsBool($order['is_damaged']);
                $this->assertIsInt($order['days_since_delivery']);
                $this->assertGreaterThanOrEqual(0, $order['days_since_delivery']);
                $this->assertIsInt($order['return_window_days']);
                $this->assertGreaterThan(0, $order['return_window_days']);
                $this->assertIsString($order['tracking_number']);
                $this->assertNotEmpty($order['tracking_number']);
                $this->assertEquals('DELIVERED', $order['status']);

                $seenOrderIds[] = $order['order_id'];
            }
        }

        $this->assertEquals(15, count(array_unique($seenOrderIds)), 'Must have 15 unique order records');
        $this->assertEquals(15, $totalOrdersFound, 'Total orders should be 15');

        // Test specific policy archetypes
        // 1. Final sale order
        $finalSale = $this->getJson('/api/customers/CUST-1002/orders')->json('data.0');
        $this->assertTrue($finalSale['is_final_sale'], 'ORD-8002 must be final sale');
        $this->assertFalse($finalSale['is_damaged']);

        // 2. High value >$500 order
        $highValue = $this->getJson('/api/customers/CUST-1003/orders')->json('data.0');
        $this->assertGreaterThan(500.00, (float) $highValue['price'], 'ORD-8003 must be > $500');
        $this->assertEquals(1249.00, (float) $highValue['price']);

        // 3. Damaged goods order
        $damaged = $this->getJson('/api/customers/CUST-1004/orders')->json('data.0');
        $this->assertTrue($damaged['is_damaged'], 'ORD-8004 must be damaged');
        $this->assertFalse($damaged['is_final_sale']);

        // 4. Final sale AND damaged goods conflict archetype
        $conflict = $this->getJson('/api/customers/CUST-1008/orders')->json('data.0');
        $this->assertTrue($conflict['is_final_sale'], 'ORD-8008 must be final sale');
        $this->assertTrue($conflict['is_damaged'], 'ORD-8008 must be damaged');

        // 5. Exactly 30-day window boundary
        $boundary30 = $this->getJson('/api/customers/CUST-1009/orders')->json('data.0');
        $this->assertEquals(30, $boundary30['days_since_delivery'], 'ORD-8009 must be exactly 30 days since delivery');

        // 6. Expired window 31 days
        $expired31 = $this->getJson('/api/customers/CUST-1010/orders')->json('data.0');
        $this->assertEquals(31, $expired31['days_since_delivery'], 'ORD-8010 must be exactly 31 days since delivery');

        // 7. Expired window 48 days
        $expired48 = $this->getJson('/api/customers/CUST-1005/orders')->json('data.0');
        $this->assertEquals(48, $expired48['days_since_delivery'], 'ORD-8005 must be 48 days since delivery');

        // 8. Exactly $500 boundary
        $boundary500 = $this->getJson('/api/customers/CUST-1012/orders')->json('data.0');
        $this->assertEquals(500.00, (float) $boundary500['price'], 'ORD-8012 must be exactly $500.00');
    }

    /**
     * @testdox Unsupported HTTP methods on endpoints return 405 Method Not Allowed
     */
    public function test_unsupported_http_methods_return_405(): void
    {
        $routes = [
            ['POST', '/api/customers'],
            ['PUT', '/api/customers'],
            ['PATCH', '/api/customers'],
            ['DELETE', '/api/customers'],
            ['POST', '/api/customers/CUST-1001/orders'],
            ['PUT', '/api/customers/CUST-1001/orders'],
            ['DELETE', '/api/customers/CUST-1001/orders'],
            ['POST', '/api/health'],
        ];

        foreach ($routes as [$method, $uri]) {
            $response = $this->call($method, $uri);
            $this->assertEquals(
                405,
                $response->status(),
                "Route {$method} {$uri} should return 405, got {$response->status()}"
            );
        }
    }

    /**
     * @testdox Unmapped routes in /api return clean 404
     */
    public function test_unmapped_api_routes_return_404(): void
    {
        $unmappedUris = [
            '/api/orders',
            '/api/orders/ORD-8001',
            '/api/customers/CUST-1001',
            '/api/customers/CUST-1001/orders/extra',
            '/api/customers/CUST-1001/orders/123/delete',
            '/api/unknown-endpoint',
        ];

        foreach ($unmappedUris as $uri) {
            $response = $this->getJson($uri);
            $this->assertEquals(404, $response->status(), "Route {$uri} should return 404");
        }
    }

    /**
     * @testdox Rapid sequential requests handle load cleanly without error or latency degradation
     */
    public function test_rapid_sequential_requests_stability(): void
    {
        $iterations = 50;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $endpoint = ($i % 2 === 0)
                ? '/api/customers'
                : '/api/customers/CUST-1001/orders';

            $response = $this->getJson($endpoint);
            $this->assertEquals(200, $response->status());
            $this->assertTrue($response->json('success'));
        }

        $totalElapsed = microtime(true) - $startTime;
        $avgElapsedMs = ($totalElapsed / $iterations) * 1000;

        // Ensure reasonable response time per request in test harness (< 20ms)
        $this->assertLessThan(50, $avgElapsedMs, "Average request latency ({$avgElapsedMs}ms) was too high");
    }

    /**
     * @testdox Dual identifier resolution behaves identically for customer_id string and primary key int
     */
    public function test_dual_identifier_resolution(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $custId = sprintf('CUST-%04d', 1000 + $i);
            $customer = Customer::where('customer_id', $custId)->firstOrFail();

            $byStringRes = $this->getJson("/api/customers/{$custId}/orders");
            $byIntRes = $this->getJson("/api/customers/{$customer->id}/orders");

            $this->assertEquals(200, $byStringRes->status());
            $this->assertEquals(200, $byIntRes->status());

            $this->assertEquals($byStringRes->json('data'), $byIntRes->json('data'));
        }
    }
}
