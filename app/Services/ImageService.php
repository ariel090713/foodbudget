<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('budgetbite.pexels_api_key', '');
    }

    /**
     * Get a food image URL for a search term.
     * Caches results to avoid hitting API limits.
     */
    public function getFoodImage(string $searchTerm): ?string
    {
        if (empty($this->apiKey) || empty($searchTerm)) {
            return null;
        }

        // Cache for 7 days — same meal name always gets same image
        $cacheKey = 'food_image:' . md5(strtolower(trim($searchTerm)));

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($searchTerm) {
            return $this->searchPexels($searchTerm . ' food');
        });
    }

    /**
     * Add image URLs to all meals in a days array.
     */
    public function enrichDaysWithImages(array $days): array
    {
        foreach ($days as &$day) {
            foreach ($day['meals'] ?? [] as &$meal) {
                if ($meal['isSkipped'] ?? false) {
                    $meal['imageUrl'] = null;
                    continue;
                }

                $searchTerm = $meal['imageSearchTerm'] ?? $meal['description'] ?? $meal['name'] ?? '';
                $meal['imageUrl'] = $this->getFoodImage($searchTerm);
            }
        }

        return $days;
    }

    private function searchPexels(string $query): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->get('https://api.pexels.com/v1/search', [
                'query' => $query,
                'per_page' => 1,
                'orientation' => 'square',
            ]);

            if ($response->successful()) {
                $photos = $response->json('photos', []);
                if (! empty($photos)) {
                    // Use medium size — good for mobile
                    return $photos[0]['src']['medium'] ?? $photos[0]['src']['small'] ?? null;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Pexels image search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
