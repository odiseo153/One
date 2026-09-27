<?php

namespace Tests\Feature\Business;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Municipality;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_registration_migration_can_resume_after_partial_execution(): void
    {
        Schema::table('business_employees', function (Blueprint $table): void {
            $table->dropIndex('business_employee_docs_idx');
        });
        $migration = require database_path('migrations/2026_09_26_000000_add_business_registration_details.php');

        $migration->up();

        $this->assertTrue(Schema::hasIndex('business_employees', 'business_employee_docs_idx'));
    }

    public function test_registered_business_requires_rnc_and_primary_ciiu(): void
    {
        [$user, $municipality, $sector] = $this->context();

        $response = $this->actingAs($user)->post('/admin/sector-map/businesses', [
            ...$this->payload($municipality, $sector),
            'is_registered' => true,
            'rnc' => null,
            'primary_activity' => null,
        ]);

        $response->assertSessionHasErrors(['rnc', 'primary_activity']);
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_business_categories_are_returned_by_the_api(): void
    {
        [$user] = $this->context();
        BusinessCategory::query()->create([
            'code' => '6201',
            'name' => 'Programación informática',
        ]);

        $this->actingAs($user)
            ->getJson('/admin/business-categories')
            ->assertOk()
            ->assertJsonPath('data.0.code', '6201')
            ->assertJsonPath('data.0.name', 'Programación informática');
    }

    public function test_document_and_property_values_are_validated(): void
    {
        [$user, $municipality, $sector] = $this->context();

        foreach ([
            [1, '0011234567'],
            [3, '12345678'],
            [2, 'ABC-12'],
        ] as [$type, $number]) {
            $response = $this->actingAs($user)->post('/admin/sector-map/businesses', [
                ...$this->payload($municipality, $sector),
                'document_type' => $type,
                'document_number' => $number,
            ]);

            $response->assertSessionHasErrors('document_number');
        }

        $response = $this->actingAs($user)->post('/admin/sector-map/businesses', [
            ...$this->payload($municipality, $sector),
            'property_status' => 99,
        ]);

        $response->assertSessionHasErrors('property_status');
        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_business_is_created_and_updated_with_photo_categories_and_employees(): void
    {
        Storage::fake('public');
        [$user, $municipality, $sector] = $this->context();
        $response = $this->actingAs($user)->post('/admin/sector-map/businesses', [
            ...$this->payload($municipality, $sector),
            'is_registered' => true,
            'rnc' => '123456789',
            'primary_activity' => 'Desarrollo de software',
            'secondary_activity' => 'Consultoría tecnológica',
            'photo' => UploadedFile::fake()->image('local.jpg'),
            'employees' => [
                [
                    'first_name' => 'Ana',
                    'last_name' => 'Pérez',
                    'document_type' => 1,
                    'document_number' => '001-1234567-8',
                    'salary' => '35000.00',
                ],
                [
                    'first_name' => 'Luis',
                    'last_name' => 'Gómez',
                    'document_type' => 2,
                    'document_number' => 'RD1234567',
                    'salary' => '42000',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $business = Business::query()->firstOrFail();
        $this->assertSame('registered', $business->registration_status);
        $this->assertSame(2, $business->getRawOriginal('property_status'));
        $this->assertSame(1, $business->getRawOriginal('document_type'));
        $this->assertSame('00112345678', $business->document_number);
        $this->assertSame('Desarrollo de software', $business->primary_activity);
        $this->assertCount(2, $business->employees);
        $this->assertSame([1, 2], DB::table('business_employees')->orderBy('id')->pluck('document_type')->all());
        $originalPhoto = str_replace('/storage/', '', $business->photo_url);
        Storage::disk('public')->assertExists($originalPhoto);

        $update = $this->actingAs($user)->post("/admin/sector-map/businesses/{$business->id}", [
            ...$this->payload($municipality, $sector),
            '_method' => 'patch',
            'name' => 'Negocio actualizado',
            'is_registered' => false,
            'rnc' => null,
            'primary_activity' => null,
            'secondary_activity' => 'Servicios generales',
            'photo' => UploadedFile::fake()->image('local-actualizado.png'),
            'employees' => [[
                'first_name' => 'Ana',
                'last_name' => 'Pérez',
                'document_type' => 3,
                'document_number' => '123456789',
                'salary' => '39000',
            ]],
        ]);

        $update->assertSessionHasNoErrors();
        $business->refresh();
        $this->assertSame('Negocio actualizado', $business->name);
        $this->assertSame('unregistered', $business->registration_status);
        $this->assertCount(1, $business->employees);
        $this->assertDatabaseMissing('business_employees', ['first_name' => 'Luis']);
        Storage::disk('public')->assertMissing($originalPhoto);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $business->photo_url));
    }

    /**
     * @return array{User, Municipality, Sector}
     */
    private function context(): array
    {
        $municipality = Municipality::query()->create(['name' => 'Distrito Nacional']);
        $sector = Sector::query()->create([
            'municipality_id' => $municipality->id,
            'name' => 'Naco',
        ]);
        $user = User::factory()->create(['municipality_id' => $municipality->id]);

        return [$user, $municipality, $sector];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Municipality $municipality, Sector $sector): array
    {
        return [
            'municipality_id' => $municipality->id,
            'sector_id' => $sector->id,
            'name' => 'Negocio de prueba',
            'category' => 'Servicios',
            'latitude' => 18.4861,
            'longitude' => -69.9312,
            'address_text' => 'Avenida principal',
            'is_registered' => false,
            'registration_status' => 'pending_verification',
            'property_status' => 2,
            'document_type' => 1,
            'document_number' => '001-1234567-8',
            'rnc' => null,
            'primary_activity' => null,
            'secondary_activity' => null,
            'detected_at' => '2026-09-26',
            'last_verified_at' => null,
            'inspector_id' => null,
            'employees' => [],
        ];
    }
}
