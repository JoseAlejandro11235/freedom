<?php

namespace App\Models;

use App\Enums\SellingLineStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellingLine extends Model
{
    protected $fillable = [
        'selling_record_id',
        'product_id',
        'size_id',
        'lot_id',
        'lot_line_id',
        'state',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'state' => SellingLineStatus::class,
        ];
    }

    public function selling(): BelongsTo
    {
        return $this->belongsTo(Selling::class, 'selling_record_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function lotLine(): BelongsTo
    {
        return $this->belongsTo(LotLine::class);
    }
}
