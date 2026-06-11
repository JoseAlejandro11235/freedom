<?php

namespace App\Filament\Resources\Providers\Pages;

use App\Filament\Concerns\ManagesPersonaFormData;
use App\Filament\Resources\Providers\ProviderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProvider extends CreateRecord
{
    use ManagesPersonaFormData;

    protected static string $resource = ProviderResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $personaData = $this->extractPersonaData($data);
        $persona = $this->createPersona($personaData);

        $data['persona_id'] = $persona?->id;

        return static::getModel()::query()->create($data);
    }
}
