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

    /**
     * GET /api/analytics/companies
     *
     * Сводная аналитика по всем компаниям.
     */
    public function companies(): JsonResponse
    {
        $data = $this->analyticsService->companySummary();

        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/analytics/companies/{id}
     *
     * Детальная аналитика по конкретной компании.
     */
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
