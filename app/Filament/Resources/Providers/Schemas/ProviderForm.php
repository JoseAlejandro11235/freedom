<?php

namespace App\Filament\Resources\Providers\Schemas;

use App\Filament\Schemas\PersonaFieldsSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Proveedor')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema(PersonaFieldsSchema::fields(requireName: true)),
            ]);
    }
}
