<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmergencyNumber\StoreEmergencyNumberRequest;
use App\Http\Requests\Admin\EmergencyNumber\UpdateEmergencyNumberRequest;
use App\Http\Resources\EmergencyNumberResource;
use App\Models\EmergencyNumber;
use Illuminate\Http\JsonResponse;

class AdminEmergencyNumberController extends Controller
{
    public function index(): JsonResponse
    {
        $numbers = EmergencyNumber::latest()->paginate(10);

        $data = EmergencyNumberResource::collection($numbers);

        return $this->succeed(__('Emergency numbers fetched successfully'), [
            'emergency_numbers' => $data,
            'meta' => [
                'current_page' => $numbers->currentPage(),
                'last_page' => $numbers->lastPage(),
                'per_page' => $numbers->perPage(),
            ],
        ]);
    }

    public function store(StoreEmergencyNumberRequest $request): JsonResponse
    {
        EmergencyNumber::create($request->validated());

        return $this->succeed(__('Emergency number created successfully'), [], 201);
    }

    public function show(EmergencyNumber $emergencyNumber): JsonResponse
    {
        return $this->succeed(__('Emergency number fetched successfully'), [
            'emergency_number' => new EmergencyNumberResource($emergencyNumber),
        ]);
    }

    public function update(UpdateEmergencyNumberRequest $request, EmergencyNumber $emergencyNumber): JsonResponse
    {
        $emergencyNumber->update($request->validated());

        return $this->succeed(__('Emergency number updated successfully'));
    }

    public function destroy(EmergencyNumber $emergencyNumber): JsonResponse
    {
        $emergencyNumber->delete();

        return $this->succeed(__('Emergency number deleted successfully'));
    }
}
