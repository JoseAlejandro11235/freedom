<?php

namespace App\Filament\Concerns;

use App\Models\Persona;

trait ManagesPersonaFormData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractPersonaData(array &$data): array
    {
        $personaData = [];

        foreach (['first_name', 'last_name', 'razon_social', 'document_number', 'phone', 'email'] as $field) {
            $personaData[$field] = $data[$field] ?? null;
            unset($data[$field]);
        }

        return $personaData;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function hasPersonaData(array $data): bool
    {
        foreach ($data as $value) {
            if (filled($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createPersona(array $data): ?Persona
    {
        return $this->persistPersona($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function persistPersona(array $data, ?Persona $currentPersona = null): ?Persona
    {
        if (! $this->hasPersonaData($data)) {
            return $currentPersona;
        }

        $documentNumber = $data['document_number'] ?? null;

        if (filled($documentNumber)) {
            $persona = Persona::query()
                ->where('document_number', $documentNumber)
                ->first();

            if ($persona !== null) {
                $persona->update($this->filledPersonaData($data));

                return $persona;
            }
        }

        if ($currentPersona !== null) {
            $currentPersona->update($data);

            return $currentPersona;
        }

        return Persona::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function fillPersonaData(array $data, ?Persona $persona): array
    {
        foreach (['first_name', 'last_name', 'razon_social', 'document_number', 'phone', 'email'] as $field) {
            $data[$field] = $persona?->{$field};
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function filledPersonaData(array $data): array
    {
        return collect($data)
            ->filter(fn ($value): bool => filled($value))
            ->all();
    }
}
