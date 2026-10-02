<?php

namespace App\Jobs;

use App\Models\RefundRequest;
use App\Services\Ai\PromptBuilder;
use App\Services\Ai\PromptInjectionGuard;
use App\Services\Policy\DeterministicPolicyEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvaluateRefundWithAIJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry up to 3 times with exponential backoff (30s, 60s, 120s).
     * If all retries are exhausted the job moves to failed_jobs and the
     * deterministic decision already written to the DB stands as final.
     */
    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        protected RefundRequest $refundRequest
    ) {}

    public function handle(): void
    {
        // Guard: if already resolved by AI (e.g. duplicate dispatch), bail out
        if ($this->refundRequest->ai_status === 'completed') {
            return;
        }

        $apiKey = config('services.gemini.key');
        if (empty($apiKey)) {
            $this->refundRequest->update(['ai_status' => 'skipped']);
            return;
        }

        // Load relationships needed to build the prompt
        $this->refundRequest->loadMissing(['order', 'customer']);
        $order    = $this->refundRequest->order;
        $customer = $this->refundRequest->customer;

        try {
            $promptData = PromptBuilder::buildPrompt($customer, $order, $this->refundRequest->customer_message);

            $payload = [
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => [['text' => $promptData['user_message']]],
                    ],
                ],
                'systemInstruction' => [
                    'parts' => [['text' => $promptData['system_prompt']]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ];

            $response = Http::timeout(20)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
                $payload
            );

            if (!$response->successful()) {
                // Non-2xx — will retry via queue backoff
                $this->fail(new \RuntimeException("Gemini returned HTTP {$response->status()}"));
                return;
            }

            $resJson       = $response->json();
            $candidateText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$candidateText) {
                $this->fail(new \RuntimeException('Gemini returned empty candidate text'));
                return;
            }

            $parsed = json_decode($candidateText, true);
            if (!is_array($parsed) || !isset($parsed['decision'])) {
                $this->fail(new \RuntimeException('Gemini returned malformed JSON'));
                return;
            }

            $decision = strtoupper(trim($parsed['decision']));
            $clause   = $parsed['policy_clause_triggered'] ?? $this->refundRequest->policy_clause_triggered;

            // Apply hard invariants — AI cannot override immutable policy rules
            $guarded  = PromptInjectionGuard::applyHardInvariants(
                $decision,
                $clause,
                (bool) $order->is_final_sale,
                (float) $order->price
            );

            $injectionDetected = $this->refundRequest->prompt_injection_detected
                || !empty($parsed['prompt_injection_detected']);

            $status = ($guarded['decision'] === 'ESCALATED')
                ? 'PENDING_HUMAN_REVIEW'
                : 'RESOLVED_AUTOMATIC';

            // Atomically upgrade the record with the AI decision
            $this->refundRequest->update([
                'decision'                => $guarded['decision'],
                'policy_clause_triggered' => $guarded['clause'],
                'confidence_score'        => (float) ($parsed['confidence_score'] ?? 0.95),
                'customer_explanation'    => $parsed['customer_explanation'] ?? '',
                'internal_reasoning'      => $parsed['internal_reasoning'] ?? '',
                'suggested_action'        => $parsed['suggested_action'] ?? '',
                'prompt_injection_detected' => $injectionDetected,
                'evaluation_mode'         => 'GEMINI_AI',
                'raw_model_response'      => $resJson,
                'status'                  => $status,
                'ai_status'               => 'completed',
            ]);

            Log::info("[EvaluateRefundWithAIJob] AI evaluation completed for {$this->refundRequest->request_id}", [
                'decision' => $guarded['decision'],
                'attempts' => $this->attempts(),
            ]);

        } catch (\Throwable $e) {
            Log::warning("[EvaluateRefundWithAIJob] Attempt {$this->attempts()} failed for {$this->refundRequest->request_id}: {$e->getMessage()}");
            throw $e; // Re-throw so Laravel queues retry with backoff
        }
    }

    /**
     * All retries exhausted — mark as failed so the deterministic result stands.
     */
    public function failed(\Throwable $exception): void
    {
        $this->refundRequest->update(['ai_status' => 'failed']);

        Log::error("[EvaluateRefundWithAIJob] All retries exhausted for {$this->refundRequest->request_id}: {$exception->getMessage()}");
    }
}
