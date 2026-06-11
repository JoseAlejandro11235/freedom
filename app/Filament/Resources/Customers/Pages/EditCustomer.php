<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Concerns\ManagesPersonaFormData;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCustomer extends EditRecord
{
    use ManagesPersonaFormData;

    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillPersonaData($data, $this->getRecord()->persona);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $personaData = $this->extractPersonaData($data);
        $persona = $this->persistPersona($personaData, $record->persona);
        $data['persona_id'] = $persona?->id;

        $record->update($data);

        return $record;
    }
}
