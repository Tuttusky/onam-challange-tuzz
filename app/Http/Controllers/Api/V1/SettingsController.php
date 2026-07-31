<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicSettingsResource;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function public(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => PublicSettingsResource::make(null),
        ]);
    }
}
