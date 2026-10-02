<?php

namespace App\DTOs;

class RefundEvaluationRequestDTO
{
    public function __construct(
        public string $customerId,
        public string $orderId,
        public string $customerMessage
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            customerId: $data['customer_id'] ?? '',
            orderId: $data['order_id'] ?? '',
            customerMessage: $data['customer_message'] ?? ''
        );
    }
}
