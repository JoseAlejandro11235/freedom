<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseExpense extends Model
{
    protected $fillable = [
        'purchase_record_id',
        'purchase_expense_concept_id',
        'description',
        'amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_record_id');
    }

    public function concept(): BelongsTo
    {
        return $this->belongsTo(PurchaseExpenseConcept::class, 'purchase_expense_concept_id');
    }
}
