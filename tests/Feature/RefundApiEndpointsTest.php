<?php

namespace Tests\Feature;

use App\Models\RefundRequest;
use Database\Seeders\CustomerSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\RefundRequestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CustomerSeeder::class);
        $this->seed(OrderSeeder::class);
        $this->seed(RefundRequestSeeder::class);
    }

    public function test_post_refunds_evaluates_and_returns_200(): void
    {
        $response = $this->postJson('/api/refunds', [
            'order_id' => 'ORD-8001',
            'customer_message' => 'Returning item as it is no longer required.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'decision' => 'APPROVED',
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'request_id',
                    'order_id',
                    'decision',
                    'policy_clause_triggered',
                    'customer_explanation',
                    'internal_reasoning',
                    'suggested_action',
                ],
            ]);
    }

    public function test_post_refunds_evaluate_validates_required_fields(): void
    {
        // 1. Missing customer_id
        $res1 = $this->postJson('/api/refunds/evaluate', [
            'order_id' => 'ORD-8001',
            'customer_message' => 'Valid message text.',
        ]);
        $res1->assertStatus(422)->assertJsonValidationErrors(['customer_id']);

        // 2. Missing order_id
        $res2 = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1001',
            'customer_message' => 'Valid message text.',
        ]);
        $res2->assertStatus(422)->assertJsonValidationErrors(['order_id']);

        // 3. Message too short (< 3 chars)
        $res3 = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1001',
            'order_id' => 'ORD-8001',
            'customer_message' => 'hi',
        ]);
        $res3->assertStatus(422)->assertJsonValidationErrors(['customer_message']);
    }

    public function test_post_refunds_returns_404_for_invalid_order_id(): void
    {
        $response = $this->postJson('/api/refunds', [
            'order_id' => 'ORD-NONEXISTENT',
            'customer_message' => 'Test message on non-existent order.',
        ]);

        $response->assertStatus(404);
    }

    public function test_get_refund_requests_returns_metrics_and_data(): void
    {
        $response = $this->getJson('/api/refund-requests');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'metrics' => [
                    'total',
                    'total_requests',
                    'approved',
                    'denied',
                    'escalated',
                ],
                'data' => [
                    '*' => [
                        'id',
                        'request_id',
                        'customer_id',
                        'order_id',
                        'decision',
                        'status',
                    ],
                ],
            ]);

        $metrics = $response->json('metrics');
        $this->assertEquals(5, $metrics['total']);
        $this->assertEquals(5, $metrics['total_requests']);
        $this->assertEquals($metrics['approved'] + $metrics['denied'] + $metrics['escalated'], $metrics['total']);
    }

    public function test_get_refund_requests_search_filter(): void
    {
        $response = $this->getJson('/api/refund-requests?search=Alice');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $items = $response->json('data');
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $matched = str_contains($item['customer']['name'] ?? '', 'Alice') ||
                       str_contains($item['customer_id'] ?? '', 'Alice') ||
                       str_contains($item['customer_message'] ?? '', 'Alice');
            $this->assertTrue($matched, 'Item must match search query');
        }
    }

    public function test_get_refund_requests_decision_filter(): void
    {
        $response = $this->getJson('/api/refund-requests?decision=APPROVED');

        $response->assertStatus(200);
        $items = $response->json('data');
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertEquals('APPROVED', $item['decision']);
        }
    }

    public function test_get_refund_request_details_by_id_or_request_id(): void
    {
        $first = RefundRequest::firstOrFail();

        // 1. By request_id
        $resByReqId = $this->getJson("/api/refund-requests/{$first->request_id}");
        $resByReqId->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'request_id' => $first->request_id,
                ],
            ]);

        // 2. By numeric id
        $resByNumericId = $this->getJson("/api/refund-requests/{$first->id}");
        $resByNumericId->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $first->id,
                ],
            ]);
    }

    public function test_supervisor_manual_override(): void
    {
        // Find an escalated request
        $escalated = RefundRequest::where('decision', 'ESCALATED')->firstOrFail();

        $response = $this->postJson("/api/refund-requests/{$escalated->request_id}/override", [
            'manual_decision' => 'APPROVED',
            'admin_notes' => 'Supervisor reviewed proof of carrier delay. Exception granted.',
            'reviewed_by' => 'supervisor_patel@store.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'manual_decision' => 'APPROVED',
                    'status' => 'MANUALLY_OVERRIDDEN',
                    'admin_notes' => 'Supervisor reviewed proof of carrier delay. Exception granted.',
                    'reviewed_by' => 'supervisor_patel@store.com',
                ],
            ]);

        $this->assertDatabaseHas('refund_requests', [
            'request_id' => $escalated->request_id,
            'manual_decision' => 'APPROVED',
            'status' => 'MANUALLY_OVERRIDDEN',
        ]);
    }

    public function test_supervisor_manual_override_validations(): void
    {
        $first = RefundRequest::firstOrFail();

        // 1. Missing admin_notes
        $res1 = $this->postJson("/api/refund-requests/{$first->request_id}/override", [
            'manual_decision' => 'APPROVED',
            'admin_notes' => '',
        ]);
        $res1->assertStatus(422)->assertJsonValidationErrors(['admin_notes']);

        // 2. Disallow ESCALATED as manual override verdict
        $res2 = $this->postJson("/api/refund-requests/{$first->request_id}/override", [
            'manual_decision' => 'ESCALATED',
            'admin_notes' => 'Invalid attempt to re-escalate.',
        ]);
        $res2->assertStatus(422)->assertJsonValidationErrors(['manual_decision']);
    }
}
