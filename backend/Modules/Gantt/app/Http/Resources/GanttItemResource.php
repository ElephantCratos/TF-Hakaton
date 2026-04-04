<?php

namespace Modules\Gantt\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Плоская структура для Gantt-библиотек на фронте.
 * Совместима с dhtmlxGantt, frappe-gantt, react-gantt-task и аналогами.
 *
 * @mixin \Modules\Training\Models\TrainingGroup
 */
class GanttItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // --- Идентификация ---
            'id'                => $this->id,
            'text'              => $this->course?->title ?? "Группа #{$this->id}",
            'course_id'         => $this->course_id,
            'course_code'       => $this->course?->code,
            'specification_id'  => $this->specification_id,

            // --- Даты (ISO 8601) ---
            'start_date'        => $this->start_date?->toDateString(),
            'end_date'          => $this->end_date?->toDateString(),
            // duration в днях — удобно для библиотек, которые не считают сами
            'duration'          => $this->start_date && $this->end_date
                                    ? $this->start_date->diffInDays($this->end_date) + 1
                                    : null,

            // --- Статус ---
            'status'            => $this->status?->value,
            'status_label'      => $this->status?->label(),

            // --- Прогресс (0.0 – 1.0 для совместимости с dhtmlx/frappe) ---
            'progress'          => round(($this->average_progress ?? 0) / 100, 4),
            'progress_percent'  => $this->average_progress ?? 0,

            // --- Участники ---
            'participant_count' => $this->participants_count,

            // --- Стоимость ---
            'price_per_person'  => $this->price_per_person,
            'total_cost'        => $this->group_cost,

            // --- Визуализация ---
            'color'             => $this->gantt_color,

            // --- Конфликты: массив id групп, пересекающихся по датам+курсу ---
            'conflict_ids'      => $this->whenLoaded('conflicts', fn () =>
                $this->conflicts->pluck('id')
            ),
        ];
    }
}