<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasUlids;

    protected $fillable = [
        'persona_id',
        'code',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function displayName(): string
    {
        $name = $this->persona?->displayName() ?? 'Cliente sin persona';

        return filled($this->code) ? "{$name} ({$this->code})" : $name;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
