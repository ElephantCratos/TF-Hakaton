<?php

namespace Modules\Gantt\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Gantt\Http\Requests\GanttFilterRequest;
use Modules\Gantt\Http\Requests\GanttExportRequest;
use Modules\Gantt\Http\Requests\UpdateColorRequest;
use Modules\Gantt\Http\Requests\UpdateDatesRequest;
use Modules\Gantt\Http\Resources\GanttItemResource;
use Modules\Gantt\Services\GanttColorService;
use Modules\Gantt\Services\GanttExportService;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Enums\TrainingStatus;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GanttController extends BaseController
{
    public function __construct(
        private readonly GanttColorService  $colorService,
        private readonly GanttExportService $exportService,
    ) {}

    public function index(GanttFilterRequest $request): JsonResponse
    {
        $from = $request->fromDate();
        $to   = $request->toDate();

        $groups = TrainingGroup::query()
            ->with(['course', 'participants'])
            ->inPeriod($from, $to)
            ->when(
                $request->filled('status'),
                function ($q) use ($request) {
                    $status = TrainingStatus::tryFrom($request->status);
                    if ($status === null) {
                        abort(422, "Недопустимое значение статуса: {$request->status}");
                    }
                    return $q->withStatus($status);
                }
            )
            ->when(
                $request->filled('course_id'),
                fn ($q) => $q->where('course_id', $request->course_id)
            )
            ->orderBy('start_date')
            ->get();

        $this->loadConflicts($groups);

        $groups->each(function ($group) {
            if (empty($group->gantt_color)) {
                $this->colorService->assignColor($group);
            }
        });

        return $this->success([
            'period'  => ['from' => $from, 'to' => $to],
            'total'   => $groups->count(),
            'palette' => GanttColorService::PALETTE,
            'items'   => GanttItemResource::collection($groups),
        ]);
    }

    public function updateDates(UpdateDatesRequest $request, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('update', $trainingGroup);

        $trainingGroup->update([
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ]);

        $trainingGroup->load(['course', 'participants']);

        // Ищем конфликты с новыми датами
        $conflicts = TrainingGroup::query()
            ->conflictsWith($trainingGroup)
            ->get(['id', 'start_date', 'end_date']);

        $this->loadConflicts(collect([$trainingGroup]));

        return $this->success([
            'item'       => new GanttItemResource($trainingGroup),
            'conflicts'  => $conflicts->map(fn ($c) => [
                'id'         => $c->id,
                'start_date' => $c->start_date?->toDateString(),
                'end_date'   => $c->end_date?->toDateString(),
            ]),
        ]);
    }

    public function export(GanttExportRequest $request): StreamedResponse
    {
        $from = $request->fromDate();
        $to   = $request->toDate();

        $groups = TrainingGroup::query()
            ->with(['course', 'participants'])
            ->inPeriod($from, $to)
            ->when(
                $request->filled('status'),
                function ($q) use ($request) {
                    $status = TrainingStatus::tryFrom($request->status);
                    if ($status === null) {
                        abort(422, "Недопустимое значение статуса: {$request->status}");
                    }
                    return $q->withStatus($status);
                }
            )
            ->when(
                $request->filled('course_id'),
                fn ($q) => $q->where('course_id', $request->course_id)
            )
            ->orderBy('start_date')
            ->get();

        return match ($request->exportFormat()) {
            'json'  => $this->exportService->exportJson($groups, $from, $to),
            default => $this->exportService->exportCsv($groups, $from, $to),
        };
    }

    public function updateColor(UpdateColorRequest $request, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('update', $trainingGroup);

        $color = $this->colorService->assignColor($trainingGroup, $request->color);

        return $this->success([
            'id'    => $trainingGroup->id,
            'color' => $color,
        ]);
    }

private function loadConflicts($groups): void
{
    $byCourse = $groups->groupBy('course_id');

    foreach ($groups as $group) {
        $siblings  = $byCourse->get($group->course_id, collect());
        $conflicts = $siblings->filter(
            fn ($other) => $other->id !== $group->id
                && (string) $other->start_date === (string) $group->start_date
        );
        $group->setRelation('conflicts', $conflicts);
    }
}
}