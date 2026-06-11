<?php

namespace App\Filament\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

class PersonaFieldsSchema
{
    /**
     * @return array<int, TextInput>
     */
    public static function fields(bool $requireName = false): array
    {
        return [
            TextInput::make('first_name')
                ->label('Nombres')
                ->required(fn (Get $get): bool => $requireName
                    && blank($get('razon_social'))
                    && filled($get('last_name')))
                ->maxLength(255),
            TextInput::make('last_name')
                ->label('Apellidos')
                ->required(fn (Get $get): bool => $requireName
                    && blank($get('razon_social'))
                    && filled($get('first_name')))
                ->maxLength(255),
            TextInput::make('razon_social')
                ->label('Razón social')
                ->required(fn (Get $get): bool => $requireName
                    && blank($get('first_name'))
                    && blank($get('last_name')))
                ->maxLength(255),
            TextInput::make('document_number')
                ->label('Documento')
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Teléfono')
                ->tel()
                ->maxLength(255),
            TextInput::make('email')
                ->label('Correo')
                ->email()
                ->maxLength(255),
        ];
    }
}
