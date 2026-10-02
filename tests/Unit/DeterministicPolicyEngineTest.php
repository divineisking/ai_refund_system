<?php

namespace Tests\Unit;

use App\Services\Policy\DeterministicPolicyEngine;
use PHPUnit\Framework\TestCase;

class DeterministicPolicyEngineTest extends TestCase
{
    protected DeterministicPolicyEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DeterministicPolicyEngine();
    }

    public function test_clause_1_final_sale_is_denied(): void
    {
        $customer = ['customer_id' => 'CUST-1002', 'risk_tier' => 'LOW', 'fraud_score' => 5, 'return_rate' => 0.05];
        $order = [
            'order_id' => 'ORD-8002',
            'price' => 89.50,
            'is_final_sale' => true,
            'is_damaged' => false,
            'days_since_delivery' => 5,
            'return_window_days' => 30,
        ];

        $res = $this->engine->evaluate($customer, $order, 'Please refund.');
        $this->assertEquals(DeterministicPolicyEngine::DECISION_DENIED, $res['decision']);
        $this->assertStringContainsString('Clause 1', $res['policy_clause_triggered']);
        $this->assertEquals('RESOLVED_AUTOMATIC', $res['status']);
    }

    public function test_clause_2_high_value_over_500_is_escalated(): void
    {
        $customer = ['customer_id' => 'CUST-1003', 'risk_tier' => 'LOW', 'fraud_score' => 10, 'return_rate' => 0.02];
        $order = [
            'order_id' => 'ORD-8003',
            'price' => 1249.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'days_since_delivery' => 10,
            'return_window_days' => 30,
        ];

        $res = $this->engine->evaluate($customer, $order, 'Returning monitor.');
        $this->assertEquals(DeterministicPolicyEngine::DECISION_ESCALATED, $res['decision']);
        $this->assertStringContainsString('Clause 2', $res['policy_clause_triggered']);
        $this->assertEquals('PENDING_HUMAN_REVIEW', $res['status']);
    }

    public function test_clause_3_damaged_goods_within_window_is_approved(): void
    {
        $customer = ['customer_id' => 'CUST-1004', 'risk_tier' => 'LOW', 'fraud_score' => 8, 'return_rate' => 0.04];
        $order = [
            'order_id' => 'ORD-8004',
            'price' => 139.99,
            'is_final_sale' => false,
            'is_damaged' => true,
            'days_since_delivery' => 3,
            'return_window_days' => 30,
        ];

        $res = $this->engine->evaluate($customer, $order, 'Item arrived damaged in transit.');
        $this->assertEquals(DeterministicPolicyEngine::DECISION_APPROVED, $res['decision']);
        $this->assertStringContainsString('Clause 3', $res['policy_clause_triggered']);
        $this->assertEquals('RESOLVED_AUTOMATIC', $res['status']);
    }

    public function test_clause_4_expired_window_is_denied(): void
    {
        $customer = ['customer_id' => 'CUST-1005', 'risk_tier' => 'LOW', 'fraud_score' => 12, 'return_rate' => 0.06];
        $order = [
            'order_id' => 'ORD-8005',
            'price' => 165.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'days_since_delivery' => 48,
            'return_window_days' => 30,
        ];

        $res = $this->engine->evaluate($customer, $order, 'Want to return.');
        $this->assertEquals(DeterministicPolicyEngine::DECISION_DENIED, $res['decision']);
        $this->assertStringContainsString('Clause 4', $res['policy_clause_triggered']);
    }

    public function test_clause_4_extenuating_circumstances_is_escalated(): void
    {
        $customer = ['customer_id' => 'CUST-1014', 'risk_tier' => 'LOW', 'fraud_score' => 5, 'return_rate' => 0.02];
        $order = [
            'order_id' => 'ORD-8014',
            'price' => 185.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'days_since_delivery' => 42,
            'return_window_days' => 30,
        ];

        $res = $this->engine->evaluate($customer, $order, 'I was hospitalized for surgery and could not return in 30 days.');
        $this->assertEquals(DeterministicPolicyEngine::DECISION_ESCALATED, $res['decision']);
        $this->assertStringContainsString('Extenuating Circumstances', $res['policy_clause_triggered']);
        $this->assertEquals('PENDING_HUMAN_REVIEW', $res['status']);
    }

    public function test_clause_5_high_fraud_risk_is_escalated(): void
    {
        $customer = ['customer_id' => 'CUST-1006', 'risk_tier' => 'HIGH', 'fraud_score' => 88, 'return_rate' => 0.68];
        $order = [
            'order_id' => 'ORD-8006',
            'price' => 280.00,
            'is_final_sale' => false,
            'is_damaged' => false,
            'days_since_delivery' => 5,
            'return_window_days' => 30,
        ];

        $res = $this->engine->evaluate($customer, $order, 'Just return it.');
        $this->assertEquals(DeterministicPolicyEngine::DECISION_ESCALATED, $res['decision']);
        $this->assertStringContainsString('Clause 5', $res['policy_clause_triggered']);
    }

    public function test_clause_6_standard_valid_return_is_approved(): void
    {
        $customer = ['customer_id' => 'CUST-1001', 'risk_tier' => 'LOW', 'fraud_score' => 5, 'return_rate' => 0.03];
        $order = [
            'order_id' => 'ORD-8001',
            'price' => 79.99,
            'is_final_sale' => false,
            'is_damaged' => false,
            'days_since_delivery' => 4,
            'return_window_days' => 30,
        ];

        $res = $this->engine->evaluate($customer, $order, 'Earbuds do not fit comfortably.');
        $this->assertEquals(DeterministicPolicyEngine::DECISION_APPROVED, $res['decision']);
        $this->assertStringContainsString('Clause 6', $res['policy_clause_triggered']);
    }

    public function test_precedence_clause_1_takes_priority_over_damaged_goods(): void
    {
        // Conflict archetype: final sale AND damaged (ORD-8008)
        $customer = ['customer_id' => 'CUST-1008', 'risk_tier' => 'LOW', 'fraud_score' => 10, 'return_rate' => 0.05];
        $order = [
            'order_id' => 'ORD-8008',
            'price' => 75.00,
            'is_final_sale' => true,
            'is_damaged' => true,
            'days_since_delivery' => 5,
            'return_window_days' => 30,
        ];

        $res = $this->engine->evaluate($customer, $order, 'Item is final sale but arrived damaged.');
        $this->assertEquals(DeterministicPolicyEngine::DECISION_DENIED, $res['decision']);
        $this->assertStringContainsString('Clause 1', $res['policy_clause_triggered']);
    }

    public function test_precedence_clause_2_takes_priority_over_damaged_goods(): void
    {
        // High value >$500 AND damaged (ORD-8007)
        $customer = ['customer_id' => 'CUST-1007', 'risk_tier' => 'LOW', 'fraud_score' => 10, 'return_rate' => 0.05];
        $order = [
            'order_id' => 'ORD-8007',
            'price' => 850.00,
            'is_final_sale' => false,
            'is_damaged' => true,
            'days_since_delivery' => 2,
            'return_window_days' => 30,
        ];

        $res = $this->engine->evaluate($customer, $order, 'High value camera arrived damaged.');
        $this->assertEquals(DeterministicPolicyEngine::DECISION_ESCALATED, $res['decision']);
        $this->assertStringContainsString('Clause 2', $res['policy_clause_triggered']);
    }
}
