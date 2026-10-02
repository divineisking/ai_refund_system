<?php

namespace Database\Seeders;

use App\Models\RefundRequest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RefundRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requests = [
            [
                'request_id' => 'REF-20261002-8F91',
                'customer_id' => 'CUST-1001',
                'order_id' => 'ORD-8001',
                'customer_message' => 'The earbuds do not fit my ears comfortably. I would like to return them.',
                'decision' => 'APPROVED',
                'policy_clause_triggered' => 'Clause 6: Standard Valid Return',
                'confidence_score' => 0.98,
                'customer_explanation' => 'Your refund request has been approved! The item was delivered within our 30-day return window and meets all eligibility criteria. A prepaid shipping label has been dispatched to your email.',
                'internal_reasoning' => 'Order ORD-8001 ($79.99) delivered within 4 days. Item is not final sale. Customer CUST-1001 has Low risk tier (fraud score 5, return rate 7%). Clause 6 criteria satisfied.',
                'suggested_action' => 'Issue prepaid return label and process refund upon receipt.',
                'prompt_injection_detected' => false,
                'prompt_injection_flags' => null,
                'evaluation_mode' => 'DETERMINISTIC_FALLBACK',
                'raw_model_response' => [
                    'decision' => 'APPROVED',
                    'policy_clause_triggered' => 'Clause 6: Standard Valid Return',
                    'confidence_score' => 0.98,
                ],
                'status' => 'RESOLVED_AUTOMATIC',
                'admin_notes' => null,
                'manual_decision' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_at' => Carbon::now()->subHours(6),
                'updated_at' => Carbon::now()->subHours(6),
            ],
            [
                'request_id' => 'REF-20261002-8F92',
                'customer_id' => 'CUST-1002',
                'order_id' => 'ORD-8002',
                'customer_message' => 'I would like to return the merino wool sweater as the color is not what I expected.',
                'decision' => 'DENIED',
                'policy_clause_triggered' => 'Clause 1: Final Sale Non-Refundable',
                'confidence_score' => 1.00,
                'customer_explanation' => 'We are unable to process a return for this item. As noted at purchase, clearance and final sale items are strictly non-refundable.',
                'internal_reasoning' => 'Order ORD-8002 is marked is_final_sale=true. Under Clause 1, final sale items cannot be refunded regardless of order age or customer tier.',
                'suggested_action' => 'Close ticket. Advise customer on policy regarding liquidation merchandise.',
                'prompt_injection_detected' => false,
                'prompt_injection_flags' => null,
                'evaluation_mode' => 'DETERMINISTIC_FALLBACK',
                'raw_model_response' => [
                    'decision' => 'DENIED',
                    'policy_clause_triggered' => 'Clause 1: Final Sale Non-Refundable',
                    'confidence_score' => 1.00,
                ],
                'status' => 'RESOLVED_AUTOMATIC',
                'admin_notes' => null,
                'manual_decision' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_at' => Carbon::now()->subHours(5),
                'updated_at' => Carbon::now()->subHours(5),
            ],
            [
                'request_id' => 'REF-20261002-8F93',
                'customer_id' => 'CUST-1003',
                'order_id' => 'ORD-8003',
                'customer_message' => 'The monitor has noticeable backlight bleeding. I want a full refund of $1,249.',
                'decision' => 'ESCALATED',
                'policy_clause_triggered' => 'Clause 2: High Value Threshold Exceeded (>$500.00)',
                'confidence_score' => 0.95,
                'customer_explanation' => 'Thank you for reaching out. Because this order exceeds $500.00, your refund request has been escalated to our senior support team for priority review. A supervisor will contact you within 24 hours.',
                'internal_reasoning' => 'Order price is $1,249.00 (> $500.00 threshold). Under Clause 2, high value orders require mandatory human supervisor sign-off.',
                'suggested_action' => 'Assign to Senior Hardware Support Supervisor for visual inspection review.',
                'prompt_injection_detected' => false,
                'prompt_injection_flags' => null,
                'evaluation_mode' => 'DETERMINISTIC_FALLBACK',
                'raw_model_response' => [
                    'decision' => 'ESCALATED',
                    'policy_clause_triggered' => 'Clause 2: High Value Threshold Exceeded (>$500.00)',
                    'confidence_score' => 0.95,
                ],
                'status' => 'PENDING_HUMAN_REVIEW',
                'admin_notes' => null,
                'manual_decision' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_at' => Carbon::now()->subHours(4),
                'updated_at' => Carbon::now()->subHours(4),
            ],
            [
                'request_id' => 'REF-20261002-8F94',
                'customer_id' => 'CUST-1004',
                'order_id' => 'ORD-8004',
                'customer_message' => 'The blender arrived with a cracked glass pitcher and shattered base. Requesting immediate replacement or refund.',
                'decision' => 'APPROVED',
                'policy_clause_triggered' => 'Clause 3: Damaged Goods In Transit',
                'confidence_score' => 0.99,
                'customer_explanation' => 'We are so sorry your blender arrived damaged! Your refund has been approved under our transit damage protection guarantee. No return of the broken glass is required.',
                'internal_reasoning' => 'Order ORD-8004 delivered 3 days ago. Verified is_damaged=true. Price $139.99 is within standard approval threshold. Fast-track approval under Clause 3.',
                'suggested_action' => 'Issue immediate payment refund. Log carrier damage claim with carrier tracking TRK-8004-US.',
                'prompt_injection_detected' => false,
                'prompt_injection_flags' => null,
                'evaluation_mode' => 'DETERMINISTIC_FALLBACK',
                'raw_model_response' => [
                    'decision' => 'APPROVED',
                    'policy_clause_triggered' => 'Clause 3: Damaged Goods In Transit',
                    'confidence_score' => 0.99,
                ],
                'status' => 'RESOLVED_AUTOMATIC',
                'admin_notes' => null,
                'manual_decision' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_at' => Carbon::now()->subHours(3),
                'updated_at' => Carbon::now()->subHours(3),
            ],
            [
                'request_id' => 'REF-20261002-8F95',
                'customer_id' => 'CUST-1006',
                'order_id' => 'ORD-8006',
                'customer_message' => 'I changed my mind on the bomber jacket. Send money back.',
                'decision' => 'ESCALATED',
                'policy_clause_triggered' => 'Clause 5: Elevated Fraud & Abuse Risk Profile',
                'confidence_score' => 0.92,
                'customer_explanation' => 'Your refund request has been received and routed to our account operations team for further verification. We will follow up shortly.',
                'internal_reasoning' => 'Customer CUST-1006 flagged with High Risk Tier (fraud score 88, 68% return rate across 25 orders). Under Clause 5, automated refunds are restricted to protect against return abuse.',
                'suggested_action' => 'Route to Loss Prevention team. Require warehouse return inspection before funds disbursement.',
                'prompt_injection_detected' => false,
                'prompt_injection_flags' => null,
                'evaluation_mode' => 'DETERMINISTIC_FALLBACK',
                'raw_model_response' => [
                    'decision' => 'ESCALATED',
                    'policy_clause_triggered' => 'Clause 5: Elevated Fraud & Abuse Risk Profile',
                    'confidence_score' => 0.92,
                ],
                'status' => 'PENDING_HUMAN_REVIEW',
                'admin_notes' => null,
                'manual_decision' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'created_at' => Carbon::now()->subHours(2),
                'updated_at' => Carbon::now()->subHours(2),
            ],
        ];

        foreach ($requests as $data) {
            RefundRequest::updateOrCreate(
                ['request_id' => $data['request_id']],
                $data
            );
        }
    }
}
