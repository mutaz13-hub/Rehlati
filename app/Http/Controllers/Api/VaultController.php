<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VaultDocumentResource;
use App\Services\VaultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaultController extends Controller
{
    public function __construct(private readonly VaultService $service) {}

    public function index(Request $request): JsonResponse
    {
        $documents = $this->service->documents($request->user());

        return $this->succeed(__('Vault documents fetched successfully'), [
            'documents' => VaultDocumentResource::collection($documents),
        ]);
    }
}
