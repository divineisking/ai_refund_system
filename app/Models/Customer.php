<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'risk_tier',
        'fraud_score',
        'total_orders',
        'return_rate',
        'account_created_at',
    ];

    protected function casts(): array
    {
        return [
            'fraud_score' => 'integer',
            'total_orders' => 'integer',
            'return_rate' => 'float',
            'account_created_at' => 'date',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'customer_id');
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class, 'customer_id', 'customer_id');
    }
}
