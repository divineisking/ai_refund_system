<?php

namespace App\Services\Policy;

use App\Models\Customer;
use App\Models\Order;
use App\Services\Ai\PromptInjectionGuard;

class DeterministicPolicyEngine
{
    public const CLAUSE_1_FINAL_SALE = 'Clause 1: Final Sale Non-Refundable';
    public const CLAUSE_2_HIGH_VALUE = 'Clause 2: High Value Item Exceeding $500';
    public const CLAUSE_3_DAMAGED = 'Clause 3: Damaged Goods';
    public const CLAUSE_4_WINDOW_EXPIRED = 'Clause 4: Return Window Expired';
    public const CLAUSE_4_EXTENUATING = 'Clause 4: Return Window Expired (Extenuating Circumstances)';
    public const CLAUSE_5_FRAUD_ABUSE = 'Clause 5: Fraud & Abuse Risk Escalation';
    public const CLAUSE_6_STANDARD_VALID = 'Clause 6: Standard Valid Return';

    public const DECISION_APPROVED = 'APPROVED';
    public const DECISION_DENIED = 'DENIED';
    public const DECISION_ESCALATED = 'ESCALATED';

    /**
     * Keywords that trigger extenuating circumstance review under Clause 4.
     */
    public static array $extenuatingKeywords = [
        'hospital', 'hospitalized', 'hospitalization',
        'surgery', 'emergency', 'medical',
        'natural disaster', 'hurricane', 'flood',
        'carrier delivery discrepancy', 'carrier delay',
    ];

    /**
     * Evaluate refund request using models or associative arrays.
     */
    public function evaluate($customer, $order, string $customerMessage): array
    {
        $customerData = $customer instanceof Customer ? $customer->toArray() : (array)$customer;
        $orderData = $order instanceof Order ? $order->toArray() : (array)$order;

        if ($order instanceof Order) {
            $orderData['days_since_delivery'] = $order->days_since_delivery;
        }

        // 1. Prompt Injection Scanning via Guard
        $injectionResult = PromptInjectionGuard::scan($customerMessage);
        $injectionDetected = $injectionResult['detected'];
        $injectionFlags = $injectionResult['flags'];

        // Extract normalized order variables
        $isFinalSale = (bool)($orderData['is_final_sale'] ?? false);
        $price = (float)($orderData['price'] ?? 0.0);
        $isDamaged = (bool)($orderData['is_damaged'] ?? false);
        $orderAgeDays = (int)($orderData['days_since_delivery'] ?? $orderData['days_ago'] ?? 0);
        $windowDays = (int)($orderData['return_window_days'] ?? 30);
        $orderId = $orderData['order_id'] ?? 'N/A';

        // Extract normalized customer variables
        $riskTier = strtoupper((string)($customerData['risk_tier'] ?? 'LOW'));
        $fraudScore = (int)($customerData['fraud_score'] ?? 0);
        $returnRate = (float)($customerData['return_rate'] ?? 0.0);
        $customerId = $customerData['customer_id'] ?? 'N/A';

        $clause = '';
        $decision = '';
        $explanation = '';
        $reasoning = '';
        $suggestedAction = '';

        // Precedence 1: Clause 1 (Final Sale) -> Absolute Priority
        if ($isFinalSale) {
            $clause = self::CLAUSE_1_FINAL_SALE;
            $decision = self::DECISION_DENIED;
            $explanation = "We cannot process a refund for this item because it was purchased as a final sale / liquidation item and is strictly non-refundable.";
            $reasoning = "Order {$orderId} is marked is_final_sale=true. Clause 1 takes absolute precedence over all other clauses.";
            $suggestedAction = "Inform customer of final sale policy and close ticket.";
        }
        // Precedence 2: Clause 2 (High Value > $500.00) -> Requires Human Review
        elseif ($price > 500.00) {
            $clause = self::CLAUSE_2_HIGH_VALUE;
            $decision = self::DECISION_ESCALATED;
            $explanation = "Your refund request is being reviewed by a senior support supervisor because the item value exceeds $500.00. You will receive an update within 24 hours.";
            $reasoning = "Order {$orderId} price is \${$price}, which strictly exceeds the \$500.00 threshold. Escalated for human authorization.";
            $suggestedAction = "Route to Senior Support / Claims Manager for supervisor approval.";
        }
        // Precedence 3: Clause 5 (Fraud & Abuse Prevention)
        elseif ($riskTier === 'HIGH' || $fraudScore >= 70 || ($returnRate >= 0.40 && $riskTier !== 'LOW')) {
            $clause = self::CLAUSE_5_FRAUD_ABUSE;
            $decision = self::DECISION_ESCALATED;
            $explanation = "Your request has been forwarded to our specialized account review team for verification.";
            $reasoning = "Customer {$customerId} triggered risk threshold (Risk Tier: {$riskTier}, Fraud Score: {$fraudScore}/100, Return Rate: " . ($returnRate * 100) . "%).";
            $suggestedAction = "Route ticket to Loss Prevention / Risk Operations team for account history verification.";
        }
        // Precedence 4: Clause 4 (Return Window Expiry)
        elseif ($orderAgeDays > $windowDays) {
            if ($this->hasExtenuatingCircumstance($customerMessage)) {
                $clause = self::CLAUSE_4_EXTENUATING;
                $decision = self::DECISION_ESCALATED;
                $explanation = "Your purchase is outside the standard 30-day return window, but our team is reviewing your extenuating circumstances.";
                $reasoning = "Order age ({$orderAgeDays} days) exceeds return window ({$windowDays} days), but customer stated verifiable extenuating circumstance. Escalated for human discretion.";
                $suggestedAction = "Assign to Tier 2 Support agent to review customer documentation.";
            } else {
                $clause = self::CLAUSE_4_WINDOW_EXPIRED;
                $decision = self::DECISION_DENIED;
                $explanation = "We are unable to accept returns after the {$windowDays}-day return window has expired. Your order was delivered {$orderAgeDays} days ago.";
                $reasoning = "Order age ({$orderAgeDays} days) exceeds return window ({$windowDays} days). No extenuating circumstances noted.";
                $suggestedAction = "Advise customer of expired return period and offer manufacturer warranty info if applicable.";
            }
        }
        // Precedence 5: Clause 3 (Damaged Goods within return window)
        elseif ($isDamaged) {
            $clause = self::CLAUSE_3_DAMAGED;
            $decision = self::DECISION_APPROVED;
            $explanation = "We are so sorry your item arrived damaged! Your refund has been approved. A prepaid return shipping label has been sent to your email.";
            $reasoning = "Order {$orderId} is verified damaged and delivered within the {$windowDays}-day window. Clause 3 fast-track approved.";
            $suggestedAction = "Issue prepaid damaged-goods return label and dispatch replacement or issue full refund.";
        }
        // Precedence 6: Clause 6 (Standard Valid Return)
        else {
            $clause = self::CLAUSE_6_STANDARD_VALID;
            $decision = self::DECISION_APPROVED;
            $explanation = "Your return request has been approved! The item is within the {$windowDays}-day return window. A shipping label has been dispatched to your email.";
            $reasoning = "Order {$orderId} satisfies all Clause 6 conditions: Delivered {$orderAgeDays} days ago (<= {$windowDays}), price \${$price} (<= \$500.00), not final sale, low customer risk.";
            $suggestedAction = "Generate standard prepaid return label and send return instructions.";
        }

        // Apply Post-Evaluation Hard Invariant Guardrails
        $guarded = PromptInjectionGuard::applyHardInvariants($decision, $clause, $isFinalSale, $price);
        $decision = $guarded['decision'];
        $clause = $guarded['clause'];

        return [
            'decision' => $decision,
            'policy_clause_triggered' => $clause,
            'confidence_score' => 0.98,
            'customer_explanation' => $explanation,
            'internal_reasoning' => $reasoning,
            'suggested_action' => $suggestedAction,
            'prompt_injection_detected' => $injectionDetected,
            'prompt_injection_flags' => $injectionFlags,
            'status' => $decision === self::DECISION_ESCALATED ? 'PENDING_HUMAN_REVIEW' : 'RESOLVED_AUTOMATIC',
            'evaluation_mode' => 'DETERMINISTIC_FALLBACK',
        ];
    }

    /**
     * Check if message indicates demonstrable extenuating circumstances.
     */
    public function hasExtenuatingCircumstance(string $message): bool
    {
        $lower = strtolower($message);
        foreach (self::$extenuatingKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }
        return false;
    }
}
