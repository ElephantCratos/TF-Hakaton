<?php

namespace Modules\Xml\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Xml\Http\Requests\XmlImportRequest;
use Modules\Xml\Models\XmlImportBatch;
use Modules\Xml\Services\XmlImportService;

class XmlImportController extends Controller
{
    public function __construct(
        private readonly XmlImportService $importService,
    ) {}

    public function import(XmlImportRequest $request): JsonResponse
    {
        $processedBy = $request->user()?->id;
        $results     = [];

        $file = $request->file('file');
            $batch     = $this->importService->importFile($file, $processedBy);
            $results[] = $this->formatBatch($batch, withLogs: true);

        return response()->json([
            'message' => 'Импорт завершён. Обработано файлов: ' . count($results),
            'batches' => $results,
        ]);
    }

    public function batches(Request $request): JsonResponse
    {
        $batches = XmlImportBatch::query()
            ->orderByDesc('imported_at')
            ->paginate($request->integer('per_page', 20));

        $batches->getCollection()->transform(fn($b) => $this->formatBatch($b));

        return response()->json($batches);
    }

    public function batchShow(XmlImportBatch $batch): JsonResponse
    {
        return response()->json([
            'batch' => $this->formatBatch($batch, withLogs: true),
        ]);
    }

    public function batchLogs(XmlImportBatch $batch, Request $request): JsonResponse
    {
        $logs = $batch->logs()
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('entity'), fn($q) => $q->where('entity_name', $request->entity))
            ->orderBy('id')
            ->paginate($request->integer('per_page', 50));

        return response()->json($logs);
    }

    private function formatBatch(XmlImportBatch $batch, bool $withLogs = false): array
    {
        $data = [
            'id'            => $batch->id,
            'source_system' => $batch->source_system,
            'file_name'     => $batch->file_name,
            'imported_at'   => $batch->imported_at,
            'success_count' => $batch->success_count,
            'error_count'   => $batch->error_count,
            'skipped_count' => $batch->skipped_count,
        ];

        if ($withLogs) {
            $data['logs'] = $batch->logs->map(fn($log) => [
                'id'                 => $log->id,
                'entity_name'        => $log->entity_name,
                'entity_external_id' => $log->entity_external_id,
                'operation_type'     => $log->operation_type,
                'status'             => $log->status,
                'message'            => $log->message,
                'created_at'         => $log->created_at,
            ]);
        }

        return $data;
    }
}