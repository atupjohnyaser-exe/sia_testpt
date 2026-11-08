<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Reads places from PSGC Cloud, a free community API over the official Philippine Standard Geographic Code.
 *   GET {base}/provinces/{province}/municipalities       the 47 municipalities of Bohol
 *   GET {base}/provinces/{province}/cities               Tagbilaran City
 *   GET {base}/cities-municipalities/{code}/barangays    the barangays of one of them
 */
class PsgcClient
{
    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('bpd.psgc.base_url'), '/'))
            ->acceptJson()
            ->timeout((int) config('bpd.psgc.timeout'))
            ->retry(3, (int) config('bpd.psgc.retry_ms'), throw: false);
    }

    /** @return array<int,array{code:string,name:string,type:string}> */
    public function municipalities(): array
    {
        $province = rawurlencode((string) config('bpd.psgc.province'));
        $rows = $this->list("/provinces/{$province}/municipalities", 'Municipality');

        if (config('bpd.psgc.include_cities')) {
            $rows = array_merge($rows, $this->list("/provinces/{$province}/cities", 'City'));
        }

        return $rows;
    }

    /** @return array<int,array{code:string,name:string,type:string}> */
    public function barangays(string $code): array
    {
        return $this->list('/cities-municipalities/'.rawurlencode($code).'/barangays', 'Barangay');
    }

    private function list(string $path, string $defaultType): array
    {
        $response = $this->http()->get($path);

        if (! $response->successful()) {
            throw new RuntimeException("PSGC answered {$response->status()} for {$path}");
        }

        $json = $response->json();
        $items = is_array($json) && array_is_list($json) ? $json : ($json['data'] ?? []);

        $rows = [];

        foreach ($items as $item) {
            if (! is_array($item) || empty($item['code']) || empty($item['name'])) {
                continue;
            }

            $rows[] = [
                'code' => (string) $item['code'],
                'name' => trim((string) $item['name']),
                'type' => (string) ($item['type'] ?? $defaultType),
            ];
        }

        return $rows;
    }
}
