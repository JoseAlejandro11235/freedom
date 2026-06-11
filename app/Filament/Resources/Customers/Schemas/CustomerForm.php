<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Filament\Schemas\PersonaFieldsSchema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Cliente')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('code')
                            ->label('Código')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),
                Section::make('Persona')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema(PersonaFieldsSchema::fields()),
            ]);
    }
}
