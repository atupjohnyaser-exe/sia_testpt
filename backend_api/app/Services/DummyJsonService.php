<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DummyJsonService
{
    public function users(): array
    {
        try {
            return Cache::remember('rpd.dummyjson.users', now()->addDay(), fn () => Http::timeout(12)->get('https://dummyjson.com/users', ['limit' => 20])->throw()->json('users', []));
        } catch (Throwable $exception) {
            Log::warning('DummyJSON unavailable; using bundled demo people.', ['message' => $exception->getMessage()]);
            return [];
        }
    }
}
