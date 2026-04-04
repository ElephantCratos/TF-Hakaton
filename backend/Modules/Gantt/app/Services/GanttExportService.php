<?php

namespace Modules\Gantt\Services;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GanttExportService
{
    /**
     * Отдаёт CSV-файл стримом — не грузит всё в память.
     */
    public function exportCsv(Collection $groups, string $from, string $to): StreamedResponse
    {
        $filename = "gantt_{$from}_{$to}.csv";

        return response()->streamDownload(function () use ($groups) {
            $handle = fopen('php://output', 'w');

            // BOM для корректного открытия в Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Заголовки
            fputcsv($handle, [
                'ID',
                'Курс',
                'Дата начала',
                'Дата окончания',
                'Длительность (дней)',
                'Статус',
                'Участников',
                'Средний прогресс (%)',
                'Цена за человека (руб.)',
                'Стоимость группы (руб.)',
            ], ';');

            foreach ($groups as $group) {
                fputcsv($handle, [
                    $group->id,
                    $group->course?->title ?? '',
                    $group->start_date?->toDateString(),
                    $group->end_date?->toDateString(),
                    $group->start_date && $group->end_date
                        ? $group->start_date->diffInDays($group->end_date) + 1
                        : '',
                    $group->status?->label() ?? $group->status?->value,
                    $group->participants_count,
                    $group->average_progress,
                    $group->price_per_person,
                    $group->group_cost,
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Отдаёт JSON-файл для дальнейшей обработки.
     */
    public function exportJson(Collection $groups, string $from, string $to): StreamedResponse
    {
        $filename = "gantt_{$from}_{$to}.json";

        $data = [
            'exported_at' => now()->toIso8601String(),
            'period'      => ['from' => $from, 'to' => $to],
            'total'       => $groups->count(),
            'items'       => $groups->map(fn ($group) => [
                'id'               => $group->id,
                'course'           => $group->course?->title,
                'course_code'      => $group->course?->code,
                'start_date'       => $group->start_date?->toDateString(),
                'end_date'         => $group->end_date?->toDateString(),
                'duration_days'    => $group->start_date && $group->end_date
                    ? $group->start_date->diffInDays($group->end_date) + 1
                    : null,
                'status'           => $group->status?->value,
                'status_label'     => $group->status?->label(),
                'participant_count'=> $group->participants_count,
                'progress_percent' => $group->average_progress,
                'price_per_person' => $group->price_per_person,
                'total_cost'       => $group->group_cost,
                'color'            => $group->gantt_color,
            ])->values(),
        ];

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type'        => 'application/json; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}