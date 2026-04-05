<?php

namespace Modules\Analytics\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Analytics\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService
    ) {}

    public function companies(): JsonResponse
    {
        $data = $this->analyticsService->companySummary();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $data,
        ]);
    }

    public function companyDetail(int $id): JsonResponse
    {
        $data = $this->analyticsService->companyDetail($id);

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $data,
        ]);
    }
}
