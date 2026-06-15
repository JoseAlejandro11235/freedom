<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    use HasUlids;

    protected $fillable = [
        'first_name',
        'last_name',
        'razon_social',
        'document_number',
        'phone',
        'email',
    ];

    public function displayName(): string
    {
        if (filled($this->razon_social)) {
            return $this->razon_social;
        }

        $name = trim(collect([$this->first_name, $this->last_name])
            ->filter(fn ($value): bool => filled($value))
            ->implode(' '));

        return $name !== '' ? $name : 'Sin nombre';
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }
}
