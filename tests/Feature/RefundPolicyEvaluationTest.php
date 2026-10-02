<?php

namespace Tests\Feature;

use Database\Seeders\CustomerSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\RefundRequestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundPolicyEvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CustomerSeeder::class);
        $this->seed(OrderSeeder::class);
        $this->seed(RefundRequestSeeder::class);
    }

    public function test_clause_1_final_sale_is_evaluated_and_persisted_as_denied(): void
    {
        $response = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1002',
            'order_id' => 'ORD-8002',
            'customer_message' => 'Please refund this polo shirt.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'decision' => 'DENIED',
                'status' => 'RESOLVED_AUTOMATIC',
                'evaluation_mode' => 'DETERMINISTIC_FALLBACK',
            ]);

        $this->assertStringContainsString('Clause 1', $response->json('policy_clause_triggered'));
        $this->assertDatabaseHas('refund_requests', [
            'order_id' => 'ORD-8002',
            'decision' => 'DENIED',
        ]);
    }

    public function test_clause_2_high_value_is_escalated(): void
    {
        $response = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1003',
            'order_id' => 'ORD-8003',
            'customer_message' => 'Returning ultra-wide monitor.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'decision' => 'ESCALATED',
                'status' => 'PENDING_HUMAN_REVIEW',
            ]);

        $this->assertStringContainsString('Clause 2', $response->json('policy_clause_triggered'));
    }

    public function test_clause_3_damaged_goods_is_approved(): void
    {
        $response = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1004',
            'order_id' => 'ORD-8004',
            'customer_message' => 'Package arrived torn and blenders jar is shattered.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'decision' => 'APPROVED',
                'status' => 'RESOLVED_AUTOMATIC',
            ]);

        $this->assertStringContainsString('Clause 3', $response->json('policy_clause_triggered'));
    }

    public function test_clause_4_expired_window_is_denied(): void
    {
        $response = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1005',
            'order_id' => 'ORD-8005',
            'customer_message' => 'Returning leather boots delivered 48 days ago.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'decision' => 'DENIED',
            ]);

        $this->assertStringContainsString('Clause 4', $response->json('policy_clause_triggered'));
    }

    public function test_clause_4_extenuating_circumstances_escalates_expired_window(): void
    {
        $response = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1014',
            'order_id' => 'ORD-8014',
            'customer_message' => 'I was in the hospital for emergency surgery and could not submit earlier.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'decision' => 'ESCALATED',
                'status' => 'PENDING_HUMAN_REVIEW',
            ]);

        $this->assertStringContainsString('Extenuating Circumstances', $response->json('policy_clause_triggered'));
    }

    public function test_clause_5_high_fraud_score_is_escalated(): void
    {
        $response = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1006',
            'order_id' => 'ORD-8006',
            'customer_message' => 'I would like to return this leather bomber jacket.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'decision' => 'ESCALATED',
            ]);

        $this->assertStringContainsString('Clause 5', $response->json('policy_clause_triggered'));
    }

    public function test_clause_6_standard_valid_return_is_approved(): void
    {
        $response = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1001',
            'order_id' => 'ORD-8001',
            'customer_message' => 'Earbuds fit is slightly uncomfortable.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'decision' => 'APPROVED',
            ]);

        $this->assertStringContainsString('Clause 6', $response->json('policy_clause_triggered'));
    }

    public function test_conflict_final_sale_and_damaged_goods_prioritizes_final_sale(): void
    {
        $response = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1008',
            'order_id' => 'ORD-8008',
            'customer_message' => 'Ceramic dinnerware arrived broken, but was final sale.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'decision' => 'DENIED',
            ]);

        $this->assertStringContainsString('Clause 1', $response->json('policy_clause_triggered'));
    }

    public function test_conflict_high_value_and_damaged_goods_prioritizes_high_value_escalation(): void
    {
        $response = $this->postJson('/api/refunds/evaluate', [
            'customer_id' => 'CUST-1007',
            'order_id' => 'ORD-8007',
            'customer_message' => 'Professional mirrorless camera ($850) arrived damaged.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'decision' => 'ESCALATED',
            ]);

        $this->assertStringContainsString('Clause 2', $response->json('policy_clause_triggered'));
    }
}
