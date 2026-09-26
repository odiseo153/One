<?php

namespace App\Modules\Complaint\Adapters\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Municipality;
use App\Models\Sector;
use App\Modules\Complaint\Http\Requests\StoreComplaintRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('complaints/create', [
            'municipalities' => $this->activeMunicipalities(),
            'sectors' => $this->sectors(),
            'categories' => $this->categories(),
            'municipality_id' => $request->integer('municipality_id') ?: null,
        ]);
    }

    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $municipalityId = $validated['municipality_id'];
        $sectorId = $validated['sector_id']
            ?? $this->detectSectorId(
                $municipalityId,
                $validated['latitude'] ?? null,
                $validated['longitude'] ?? null,
            );

        $complaint = Complaint::create([
            'municipality_id' => $municipalityId,
            'sector_id' => $sectorId,
            'category' => $validated['category'],
            'description' => $validated['description'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'address_text' => $validated['address_text'] ?? null,
            'photo_url' => $this->storePhoto($request),
            'citizen_name' => $validated['citizen_name'],
            'citizen_phone' => $validated['citizen_phone'],
            'tracking_code' => Complaint::newTrackingCode(),
            'status' => Complaint::STATUS_RECEIVED,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Queja registrada. Guarda tu código de seguimiento.'),
        ]);

        return redirect()->route('complaints.show', $complaint->tracking_code);
    }

    public function show(Request $request, string $trackingCode): Response
    {
        $complaint = Complaint::query()
            ->where('tracking_code', $trackingCode)
            ->with([
                'municipality:id,name',
                'sector:id,name',
                'updates' => fn ($query) => $query->with('user:id,name'),
            ])
            ->firstOrFail();

        return Inertia::render('complaints/show', [
            'complaint' => $this->publicComplaint($complaint),
        ]);
    }

    public function track(): Response
    {
        return Inertia::render('complaints/track');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function categories(): array
    {
        return [
            ['value' => Complaint::CATEGORY_BACHE, 'label' => 'Bache'],
            ['value' => Complaint::CATEGORY_ALUMBRADO, 'label' => 'Alumbrado'],
            ['value' => Complaint::CATEGORY_BASURA, 'label' => 'Basura'],
            ['value' => Complaint::CATEGORY_AGUA, 'label' => 'Agua'],
            ['value' => Complaint::CATEGORY_OTRO, 'label' => 'Otro'],
        ];
    }

    /**
     * @return Collection<int, Municipality>
     */
    private function activeMunicipalities(): Collection
    {
        return Municipality::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, Sector>
     */
    private function sectors(): Collection
    {
        return Sector::query()
            ->whereHas('municipality', fn ($query) => $query->where('status', 'active'))
            ->orderBy('name')
            ->get(['id', 'municipality_id', 'name']);
    }

    /**
     * @return array<string, mixed>
     */
    private function publicComplaint(Complaint $complaint): array
    {
        return [
            'tracking_code' => $complaint->tracking_code,
            'category' => $complaint->category,
            'category_label' => Complaint::categoryLabelFor($complaint->category),
            'description' => $complaint->description,
            'latitude' => $complaint->latitude,
            'longitude' => $complaint->longitude,
            'address_text' => $complaint->address_text,
            'photo_url' => $complaint->photo_url,
            'citizen_name' => $complaint->citizen_name,
            'citizen_phone' => $complaint->citizen_phone,
            'status' => $complaint->status,
            'status_label' => $complaint->statusLabel(),
            'created_at' => $complaint->created_at,
            'municipality' => ['name' => $complaint->municipality->name],
            'sector' => $complaint->sector
                ? ['name' => $complaint->sector->name]
                : null,
            'updates' => $complaint->updates->map(fn ($update) => [
                'status_label' => Complaint::statusLabelFor($update->new_status),
                'note' => $update->note,
                'created_at' => $update->created_at,
                'by_name' => $update->user?->name,
            ])->values(),
        ];
    }

    private function storePhoto(StoreComplaintRequest $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        $path = $request->file('photo')->store('complaints', 'public');

        return $path ? Storage::url($path) : null;
    }

    private function detectSectorId(int $municipalityId, ?float $latitude, ?float $longitude): ?int
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $sectors = Sector::query()
            ->where('municipality_id', $municipalityId)
            ->whereNotNull('geojson_polygon')
            ->get(['id', 'geojson_polygon']);

        foreach ($sectors as $sector) {
            if ($this->pointInPolygon($latitude, $longitude, $sector->geojson_polygon)) {
                return $sector->id;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $polygon
     */
    private function pointInPolygon(float $lat, float $lng, ?array $polygon): bool
    {
        $coordinates = $polygon['coordinates'][0] ?? [];

        if (! is_array($coordinates) || count($coordinates) < 3) {
            return false;
        }

        $inside = false;

        $count = count($coordinates);
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) $coordinates[$i][0];
            $yi = (float) $coordinates[$i][1];
            $xj = (float) $coordinates[$j][0];
            $yj = (float) $coordinates[$j][1];

            $intersects = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
