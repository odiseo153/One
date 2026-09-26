<?php

namespace App\Modules\Business\Domain\Services;

use App\Models\Business;
use Illuminate\Support\Collection;

class BusinessGeometryService
{
    /**
     * @param  Collection<int, Business>  $businesses
     * @param  array<string, mixed>|null  $polygon
     * @return Collection<int, Business>
     */
    public function filterInsidePolygon(Collection $businesses, ?array $polygon): Collection
    {
        if (! $this->isPolygon($polygon)) {
            return $businesses;
        }

        return $businesses
            ->filter(fn (Business $business): bool => $this->pointInPolygon(
                (float) $business->latitude,
                (float) $business->longitude,
                $polygon['coordinates'][0],
            ))
            ->values();
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    private function isPolygon(?array $value): bool
    {
        return is_array($value)
            && ($value['type'] ?? null) === 'Polygon'
            && isset($value['coordinates'][0])
            && is_array($value['coordinates'][0])
            && count($value['coordinates'][0]) >= 4;
    }

    /**
     * @param  array<int, mixed>  $ring
     */
    private function pointInPolygon(float $lat, float $lng, array $ring): bool
    {
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $current = $ring[$i];
            $previous = $ring[$j];

            if (! is_array($current) || ! is_array($previous) || count($current) < 2 || count($previous) < 2) {
                continue;
            }

            $xi = (float) $current[0];
            $yi = (float) $current[1];
            $xj = (float) $previous[0];
            $yj = (float) $previous[1];

            $intersects = (($yi > $lat) !== ($yj > $lat))
                && ($lng < (($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: PHP_FLOAT_EPSILON)) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
