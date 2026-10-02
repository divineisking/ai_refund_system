<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'customer_id',
        'order_id',
        'customer_message',
        'decision',
        'policy_clause_triggered',
        'confidence_score',
        'customer_explanation',
        'internal_reasoning',
        'suggested_action',
        'prompt_injection_detected',
        'prompt_injection_flags',
        'evaluation_mode',
        'raw_model_response',
        'status',
        'ai_status',
        'admin_notes',
        'manual_decision',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence_score' => 'float',
            'prompt_injection_detected' => 'boolean',
            'prompt_injection_flags' => 'array',
            'raw_model_response' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}
