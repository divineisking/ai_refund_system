<?php

namespace App\DTOs;

class CustomerOrderContextDTO
{
    public function __construct(
        public string $customerId,
        public string $customerName,
        public string $riskTier,
        public int $fraudScore,
        public float $returnRate,
        public string $orderId,
        public string $itemName,
        public float $price,
        public bool $isFinalSale,
        public bool $isDamaged,
        public int $daysSinceDelivery,
        public int $returnWindowDays
    ) {}
}
