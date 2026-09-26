<?php

namespace App\Modules\Business\Adapters\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SerpApiMapsRepository
{
    /**
     * @return array<string, mixed>
     */
    public function search(float $latitude, float $longitude, string $query, int $zoom = 15): array
    {
        $apiKey = config('services.serpapi.key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('SERPAPI_API_KEY is not configured.');
        }

        $params = [
            'engine' => 'google_maps',
            'type' => 'search',
            'q' => $query,
            'll' => '@'.$latitude.','.$longitude.','.$zoom.'z',
            'hl' => 'es',
            'gl' => 'do',
            'api_key' => $apiKey,
        ];

        $cacheKey = 'serpapi:google_maps:'.sha1(json_encode($params, JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, now()->addHour(), function () use ($params): array {
            $response = Http::timeout(20)->get('https://serpapi.com/search', $params);

            if (! $response->successful()) {
                throw new RuntimeException('SerpApi request failed.');
            }

            /** @var array<string, mixed> $payload */
            $payload = $response->json();

            return $payload;
        });
    }
}
