<?php

namespace App\Modules\Complaint\Domain\Services;

use App\Models\Complaint;
use App\Modules\Complaint\Adapters\Repositories\ComplaintRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PublicComplaintsService
{
    public function __construct(private readonly ComplaintRepository $repository) {}

    /**
     * @return array<string, mixed>
     */
    public function formData(): array
    {
        return [
            'municipalities' => $this->repository->getActiveMunicipalities(),
            'sectors' => $this->repository->getPublicSectorOptions(),
            'categories' => $this->categories(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $photo): Complaint
    {
        $municipalityId = (int) $data['municipality_id'];
        $data['sector_id'] = $data['sector_id']
            ?? $this->detectSectorId(
                $municipalityId,
                isset($data['latitude']) ? (float) $data['latitude'] : null,
                isset($data['longitude']) ? (float) $data['longitude'] : null,
            );

        if ($photo) {
            $path = $photo->store('complaints', 'public');
            $data['photo_url'] = $path ? Storage::url($path) : null;
        }

        unset($data['photo']);

        return $this->repository->createPublic($data);
    }

    /**
     * @return array<string, mixed>
     */
    public function findPublic(string $trackingCode): array
    {
        $complaint = $this->repository->findPublicByTrackingCode($trackingCode);

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

    private function detectSectorId(int $municipalityId, ?float $latitude, ?float $longitude): ?int
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        foreach ($this->repository->getSectorsWithGeometry($municipalityId) as $sector) {
            if ($this->pointInPolygon($latitude, $longitude, $sector->geojson_polygon)) {
                return $sector->id;
            }
        }

        return null;
    }

    private function pointInPolygon(float $lat, float $lng, mixed $polygon): bool
    {
        if (is_string($polygon)) {
            $polygon = json_decode($polygon, true);
        }

        if (! is_array($polygon)) {
            return false;
        }

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
