<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\Ai\PromptBuilder;
use App\Services\Ai\PromptInjectionGuard;
use App\Services\Policy\DeterministicPolicyEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RefundEvaluationService
{
    protected DeterministicPolicyEngine $policyEngine;

    public function __construct(DeterministicPolicyEngine $policyEngine = null)
    {
        $this->policyEngine = $policyEngine ?? new DeterministicPolicyEngine();
    }

    /**
     * Evaluate a refund request and persist the decision audit trail.
     */
    public function evaluate(string $orderId, string $customerMessage, ?string $customerId = null): RefundRequest
    {
        // 1. Resolve Order and linked Customer
        $order = Order::with('customer')->where('order_id', $orderId)->firstOrFail();
        $customer = $order->customer;

        if (!$customer && $customerId) {
            $customer = Customer::where('customer_id', $customerId)->first();
        }

        // 2. Pre-filter prompt injection scan
        $scan = PromptInjectionGuard::scan($customerMessage);
        $injectionDetected = $scan['detected'];
        $injectionFlags = $scan['flags'];

        $apiKey = config('services.gemini.key');
        $decision = null;
        $clause = null;
        $confidence = 0.98;
        $explanation = null;
        $reasoning = null;
        $suggestedAction = null;
        $evalMode = 'DETERMINISTIC_FALLBACK';
        $rawModelResponse = null;

        // 3. Attempt Gemini evaluation if API key is present
        if (!empty($apiKey)) {
            try {
                $promptData = PromptBuilder::buildPrompt($customer, $order, $customerMessage);
                
                $payload = [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => $promptData['user_message']]
                            ]
                        ]
                    ],
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => $promptData['system_prompt']]
                        ]
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                    ]
                ];

                $response = Http::timeout(10)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", $payload);

                if ($response->successful()) {
                    $resJson = $response->json();
                    $rawModelResponse = $resJson;
                    $candidateText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    
                    if ($candidateText) {
                        $parsed = json_decode($candidateText, true);
                        if (is_array($parsed) && isset($parsed['decision'])) {
                            $decision = strtoupper(trim($parsed['decision']));
                            $clause = $parsed['policy_clause_triggered'] ?? '';
                            $confidence = (float)($parsed['confidence_score'] ?? 0.95);
                            $explanation = $parsed['customer_explanation'] ?? '';
                            $reasoning = $parsed['internal_reasoning'] ?? '';
                            $suggestedAction = $parsed['suggested_action'] ?? '';
                            $injectionDetected = $injectionDetected || !empty($parsed['prompt_injection_detected']);
                            $evalMode = 'GEMINI_AI';
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Network or API failure -> seamlessly fall back to deterministic policy engine
                $evalMode = 'DETERMINISTIC_FALLBACK';
            }
        }

        // 4. Fall back to Deterministic Policy Engine if no API key or AI call failed
        if ($decision === null) {
            $fallback = $this->policyEngine->evaluate($customer, $order, $customerMessage);
            $decision = $fallback['decision'];
            $clause = $fallback['policy_clause_triggered'];
            $confidence = $fallback['confidence_score'];
            $explanation = $fallback['customer_explanation'];
            $reasoning = $fallback['internal_reasoning'];
            $suggestedAction = $fallback['suggested_action'];
            $injectionDetected = $injectionDetected || $fallback['prompt_injection_detected'];
            $injectionFlags = array_values(array_unique(array_merge($injectionFlags, $fallback['prompt_injection_flags'])));
            $evalMode = 'DETERMINISTIC_FALLBACK';
        }

        // 5. Apply Hard Invariant Guardrails
        $guarded = PromptInjectionGuard::applyHardInvariants(
            $decision,
            $clause,
            (bool)$order->is_final_sale,
            (float)$order->price
        );
        $decision = $guarded['decision'];
        $clause = $guarded['clause'];

        // Normalize status
        $status = ($decision === 'ESCALATED') ? 'PENDING_HUMAN_REVIEW' : 'RESOLVED_AUTOMATIC';

        // 6. Persist audit record
        return RefundRequest::create([
            'request_id' => 'REQ-' . Str::upper(Str::random(8)),
            'customer_id' => $order->customer_id,
            'order_id' => $order->order_id,
            'customer_message' => $customerMessage,
            'decision' => $decision,
            'policy_clause_triggered' => $clause,
            'confidence_score' => $confidence,
            'customer_explanation' => $explanation,
            'internal_reasoning' => $reasoning,
            'suggested_action' => $suggestedAction,
            'prompt_injection_detected' => $injectionDetected,
            'prompt_injection_flags' => $injectionFlags,
            'evaluation_mode' => $evalMode,
            'raw_model_response' => $rawModelResponse,
            'status' => $status,
        ]);
    }
}
