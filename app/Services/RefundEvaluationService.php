<?php

namespace App\Services;

use App\Jobs\EvaluateRefundWithAIJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\Ai\PromptInjectionGuard;
use App\Services\Policy\DeterministicPolicyEngine;
use Illuminate\Support\Str;

class RefundEvaluationService
{
    protected DeterministicPolicyEngine $policyEngine;

    public function __construct(DeterministicPolicyEngine $policyEngine = null)
    {
        $this->policyEngine = $policyEngine ?? new DeterministicPolicyEngine();
    }

    /**
     * Evaluate a refund request using the deterministic policy engine immediately,
     * persist the result, then dispatch an async job to enrich it with an AI response.
     *
     * The user always gets an instant response regardless of network conditions.
     * The AI job will upgrade the record in the background once Gemini responds.
     */
    public function evaluate(string $orderId, string $customerMessage, ?string $customerId = null): RefundRequest
    {
        // 1. Resolve Order and linked Customer
        $order    = Order::with('customer')->where('order_id', $orderId)->firstOrFail();
        $customer = $order->customer;

        if (!$customer && $customerId) {
            $customer = Customer::where('customer_id', $customerId)->first();
        }

        // 2. Pre-filter prompt injection scan
        $scan              = PromptInjectionGuard::scan($customerMessage);
        $injectionDetected = $scan['detected'];
        $injectionFlags    = $scan['flags'];

        // 3. Always evaluate deterministically first — instant, zero network dependency
        $fallback          = $this->policyEngine->evaluate($customer, $order, $customerMessage);
        $decision          = $fallback['decision'];
        $clause            = $fallback['policy_clause_triggered'];
        $confidence        = $fallback['confidence_score'];
        $explanation       = $fallback['customer_explanation'];
        $reasoning         = $fallback['internal_reasoning'];
        $suggestedAction   = $fallback['suggested_action'];
        $injectionDetected = $injectionDetected || $fallback['prompt_injection_detected'];
        $injectionFlags    = array_values(array_unique(array_merge($injectionFlags, $fallback['prompt_injection_flags'])));

        // 4. Apply hard invariant guardrails to the deterministic result
        $guarded  = PromptInjectionGuard::applyHardInvariants(
            $decision,
            $clause,
            (bool) $order->is_final_sale,
            (float) $order->price
        );
        $decision = $guarded['decision'];
        $clause   = $guarded['clause'];
        $status   = ($decision === 'ESCALATED') ? 'PENDING_HUMAN_REVIEW' : 'RESOLVED_AUTOMATIC';

        // ai_status signals the frontend whether to poll for an AI upgrade
        $apiKey   = config('services.gemini.key');
        $aiStatus = empty($apiKey) ? 'skipped' : 'pending';

        // 5. Persist the deterministic decision immediately — user gets a response NOW
        $refundRequest = RefundRequest::create([
            'request_id'                => 'REQ-' . Str::upper(Str::random(8)),
            'customer_id'               => $order->customer_id,
            'order_id'                  => $order->order_id,
            'customer_message'          => $customerMessage,
            'decision'                  => $decision,
            'policy_clause_triggered'   => $clause,
            'confidence_score'          => $confidence,
            'customer_explanation'      => $explanation,
            'internal_reasoning'        => $reasoning,
            'suggested_action'          => $suggestedAction,
            'prompt_injection_detected' => $injectionDetected,
            'prompt_injection_flags'    => $injectionFlags,
            'evaluation_mode'           => 'DETERMINISTIC_FALLBACK',
            'raw_model_response'        => null,
            'status'                    => $status,
            'ai_status'                 => $aiStatus,
        ]);

        // 6. Dispatch AI enrichment job — runs asynchronously in queue worker
        //    Retries 3x with 30s backoff. On exhaustion, deterministic result is final.
        if ($aiStatus === 'pending') {
            EvaluateRefundWithAIJob::dispatch($refundRequest);
        }

        return $refundRequest;
    }
}
