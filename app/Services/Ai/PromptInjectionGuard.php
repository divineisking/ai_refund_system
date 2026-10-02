<?php

namespace App\Services\Ai;

class PromptInjectionGuard
{
    /**
     * Regex patterns for adversarial prompt injection detection.
     */
    public static array $injectionPatterns = [
        'instruction_override' => '/(?:ignore|disregard|forget|bypass|override)\s+(?:all\s+)?(?:previous|prior|above|system|policy)\s*(?:instructions|rules|policies|policy)?/i',
        'system_mode' => '/(?:system|admin|developer|supervisor)\s+(?:override|prompt|mode|directive)/i',
        'jailbreak_roleplay' => '/(?:you are now|pretend to be|act as)\s+(?:DAN|developer mode|unrestricted|sudo|root)/i',
        'forced_approval' => '/(?:always approve|output json with decision approved|set decision to approved)/i',
        'xml_delimiter_escape' => '/<\/?(?:system_instruction|policy_rules|verified_order_context|customer_risk_profile|user_refund_request|customer_message)>/i',
    ];

    /**
     * Scan an incoming user message for prompt injection patterns and delimiter breaking.
     */
    public static function scan(string $message): array
    {
        $detected = false;
        $matchedFlags = [];

        foreach (self::$injectionPatterns as $key => $pattern) {
            if (preg_match($pattern, $message)) {
                $detected = true;
                $matchedFlags[] = $key;
            }
        }

        // XML delimiter checks
        if (str_contains($message, '<') || str_contains($message, '>')) {
            $detected = true;
            $matchedFlags[] = 'raw_xml_delimiters';
        }

        return [
            'detected' => $detected,
            'flags' => array_values(array_unique($matchedFlags)),
        ];
    }

    /**
     * Sanitize user input for safe embedding within XML boundaries.
     */
    public static function sanitizeForXml(string $message): string
    {
        return htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Tier 3 Post-Evaluation Hard Invariant Guardrails:
     * Guarantees that AI hallucinations or adversarial prompt manipulation can never
     * violate core store policy invariants.
     */
    public static function applyHardInvariants(string $decision, string $clause, bool $isFinalSale, float $price): array
    {
        $normalizedDecision = strtoupper(trim($decision));
        $normalizedClause = $clause;

        // Invariant 1: Final sale items must NEVER be approved
        if ($isFinalSale && $normalizedDecision === 'APPROVED') {
            $normalizedDecision = 'DENIED';
            $normalizedClause = 'Clause 1: Final Sale Non-Refundable';
        }

        // Invariant 2: Items over $500 must NEVER be approved automatically without supervisor review
        if ($price > 500.00 && $normalizedDecision === 'APPROVED') {
            $normalizedDecision = 'ESCALATED';
            $normalizedClause = 'Clause 2: High Value Item Exceeding $500';
        }

        return [
            'decision' => $normalizedDecision,
            'clause' => $normalizedClause,
        ];
    }
}
