<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class UkPoliceService
{
    public function crimes(): array
    {
        try {
            return Cache::remember('rpd.police.crimes', now()->addDay(), fn () => Http::timeout(15)->get('https://data.police.uk/api/crimes-street/all-crime', [
                'lat' => config('seed.crime_lat'), 'lng' => config('seed.crime_lng'),
            ])->throw()->json() ?? []);
        } catch (Throwable $exception) {
            Log::warning('UK Police API unavailable; using bundled anonymous incidents.', ['message' => $exception->getMessage()]);
            return [];
        }
    }

    public function categories(): array
    {
        try {
            return Cache::remember('rpd.police.categories', now()->addDay(), fn () => Http::timeout(12)->get('https://data.police.uk/api/crime-categories')->throw()->json() ?? []);
        } catch (Throwable $exception) {
            Log::warning('UK Police crime categories unavailable.', ['message' => $exception->getMessage()]);
            return [];
        }
    }
}
