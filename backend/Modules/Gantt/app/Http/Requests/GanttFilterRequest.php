<?php

namespace Modules\Gantt\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GanttFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from'      => ['nullable', 'date', 'date_format:Y-m-d'],
            'to'        => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status'    => ['nullable', 'string'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
        ];
    }

    /**
     * Дата начала периода. По умолчанию — начало текущего месяца.
     */
    public function fromDate(): string
    {
        return $this->input('from', now()->startOfMonth()->toDateString());
    }

    /**
     * Дата конца периода. По умолчанию — конец текущего месяца + 2 месяца вперёд.
     */
    public function toDate(): string
    {
        return $this->input('to', now()->addMonths(2)->endOfMonth()->toDateString());
    }
}