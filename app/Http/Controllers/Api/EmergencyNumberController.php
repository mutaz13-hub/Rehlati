<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmergencyNumberResource;
use App\Models\EmergencyNumber;
use Illuminate\Http\JsonResponse;

class EmergencyNumberController extends Controller
{
    public function index(): JsonResponse
    {
        $numbers = EmergencyNumber::where('is_active', true)
            ->paginate(10);

        $paginator = EmergencyNumberResource::collection($numbers);

        return $this->succeed(__('Emergency numbers fetched successfully'), [
            'emergency_numbers' => $paginator,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
