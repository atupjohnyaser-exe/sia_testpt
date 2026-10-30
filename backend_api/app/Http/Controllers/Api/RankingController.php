<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RankingController extends Controller
{
    public function index(Request $request, RankingService $rankings): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['nullable', Rule::in(['single', 'lifetime'])],
            'category' => ['nullable', Rule::in(array_keys(RankingService::CATEGORIES))],
            'limit' => ['nullable', 'integer', 'between:1,25'],
        ]);

        $scope = $data['scope'] ?? 'single';
        $category = $data['category'] ?? 'length';

        return response()->json([
            'meta' => [
                'scope' => $scope,
                'category' => $category,
                'label' => RankingService::CATEGORIES[$category]['label'],
                'categories' => collect(RankingService::CATEGORIES)->map(fn ($c) => $c['label']),
            ],
            'data' => $rankings->top($category, $scope, (int) ($data['limit'] ?? 10)),
        ]);
    }
}
