<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUlids;

    protected $fillable = [
        'number',
        'status',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'shipping_city',
        'shipping_district',
        'shipping_notes',
        'subtotal',
        'total',
        'currency',
        'payment_provider',
        'payment_reference',
        'payment_url',
        'paid_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPendingPayment(): bool
    {
        return $this->status === OrderStatus::PendingPayment;
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }
}
