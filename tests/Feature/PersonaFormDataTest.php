<?php

namespace Tests\Feature;

use App\Filament\Concerns\ManagesPersonaFormData;
use App\Models\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonaFormDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_persona_is_reused_and_updated_by_document_number(): void
    {
        $existing = Persona::query()->create([
            'first_name' => 'Jose Alejandro',
            'last_name' => 'Contreras Pomez',
            'document_number' => '70549855',
            'phone' => '943235534',
        ]);

        $persona = $this->manager()->persist([
            'first_name' => 'Jose Alejandro',
            'last_name' => 'Contreras Pomez',
            'razon_social' => 'dasdas',
            'document_number' => '70549855',
            'phone' => '943235534',
            'email' => 'contreraspomezjose@example.com',
        ]);

        $this->assertTrue($existing->is($persona));
        $this->assertSame(1, Persona::query()->where('document_number', '70549855')->count());
        $this->assertSame('dasdas', $existing->fresh()->razon_social);
        $this->assertSame('contreraspomezjose@example.com', $existing->fresh()->email);
    }

    private function manager(): object
    {
        return new class
        {
            use ManagesPersonaFormData;

            /**
             * @param  array<string, mixed>  $data
             */
            public function persist(array $data): ?Persona
            {
                return $this->persistPersona($data);
            }
        };
    }
}
