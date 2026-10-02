<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_id',
        'order_date',
        'delivered_date',
        'item_name',
        'category',
        'price',
        'is_final_sale',
        'is_damaged',
        'return_window_days',
        'tracking_number',
        'status',
    ];

    protected $appends = [
        'days_since_delivery',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'delivered_date' => 'date',
            'price' => 'float',
            'is_final_sale' => 'boolean',
            'is_damaged' => 'boolean',
            'return_window_days' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class, 'order_id', 'order_id');
    }

    public function getDaysSinceDeliveryAttribute(): int
    {
        if (! $this->delivered_date) {
            return 0;
        }

        $delivered = Carbon::parse($this->delivered_date)->startOfDay();

        return (int) $delivered->diffInDays(Carbon::now()->startOfDay());
    }
}
