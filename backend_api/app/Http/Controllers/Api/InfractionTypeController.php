<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InfractionTypeResource;
use App\Models\InfractionType;

class InfractionTypeController extends Controller
{
    public function index()
    {
        return InfractionTypeResource::collection(
            InfractionType::where('is_active', true)->orderBy('name')->get()
        );
    }
}
