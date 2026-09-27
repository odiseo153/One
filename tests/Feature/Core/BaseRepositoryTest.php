<?php

namespace Tests\Feature\Core;

use App\Models\Municipality;
use App\Models\Sector;
use App\Modules\Sector\Adapters\Repositories\SectorRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_spatie_filters_and_sorts_flat_and_nested_parameters(): void
    {
        $municipality = Municipality::query()->create(['name' => 'Distrito Nacional']);
        $otherMunicipality = Municipality::query()->create(['name' => 'Santiago']);

        Sector::query()->create(['municipality_id' => $municipality->id, 'name' => 'Norte']);
        Sector::query()->create(['municipality_id' => $municipality->id, 'name' => 'Norte Central']);
        Sector::query()->create(['municipality_id' => $otherMunicipality->id, 'name' => 'Norte']);

        $repository = app(SectorRepository::class);
        $flat = $repository->getAll(15, null, [], [
            'search' => 'Norte',
            'municipality_id' => $municipality->id,
            'sort' => '-name',
        ]);

        $this->assertSame(['Norte Central', 'Norte'], $flat->pluck('name')->all());

        $nested = $repository->getAll(15, null, [], [
            'filter' => ['municipality_id' => $otherMunicipality->id],
        ]);

        $this->assertCount(1, $nested);
        $this->assertSame('Norte', $nested->first()->name);
    }

    public function test_crud_restore_and_force_delete_are_centralized(): void
    {
        $municipality = Municipality::query()->create(['name' => 'Distrito Nacional']);
        $repository = app(SectorRepository::class);

        $sector = $repository->create([
            'municipality_id' => $municipality->id,
            'name' => 'Centro',
        ]);
        $repository->update($sector->id, ['name' => 'Centro Histórico']);

        $this->assertSame('Centro Histórico', $repository->findById($sector->id)->name);
        $this->assertTrue($repository->delete($sector->id));
        $this->assertSoftDeleted('sectors', ['id' => $sector->id]);

        $this->assertCount(0, $repository->getAll(15, null, [], ['status' => 'active']));
        $this->assertCount(1, $repository->getAll(15, null, [], ['status' => 'inactive']));

        $this->assertTrue($repository->restore($sector->id));
        $this->assertDatabaseHas('sectors', ['id' => $sector->id, 'deleted_at' => null]);
        $this->assertTrue($repository->forceDelete($sector->id));
        $this->assertDatabaseMissing('sectors', ['id' => $sector->id]);
    }
}
