<?php

namespace App\DTOs;

class RefundDecisionResponseDTO
{
    public function __construct(
        public string $requestId,
        public string $decision,
        public string $policyClauseTriggered,
        public float $confidenceScore,
        public string $customerExplanation,
        public string $internalReasoning,
        public string $suggestedAction,
        public bool $promptInjectionDetected,
        public array $promptInjectionFlags,
        public string $evaluationMode,
        public string $status
    ) {}

    public function toArray(): array
    {
        return [
            'request_id' => $this->requestId,
            'decision' => $this->decision,
            'policy_clause_triggered' => $this->policyClauseTriggered,
            'confidence_score' => $this->confidenceScore,
            'customer_explanation' => $this->customerExplanation,
            'internal_reasoning' => $this->internalReasoning,
            'suggested_action' => $this->suggestedAction,
            'prompt_injection_detected' => $this->promptInjectionDetected,
            'prompt_injection_flags' => $this->promptInjectionFlags,
            'evaluation_mode' => $this->evaluationMode,
            'status' => $this->status,
        ];
    }
}
