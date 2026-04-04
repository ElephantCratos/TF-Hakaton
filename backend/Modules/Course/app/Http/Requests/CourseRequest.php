<?php

namespace Modules\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Abstracts\Http\Requests\BaseFormRequest;

class CourseRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'unique:courses', 'max:10', 'min:10'],
            'title' => ['required', 'string'],
            'price' => ['required', 'decimal:2'],
            'description' => ['string'],
            'duration_days' => ['required', 'int'],
        ];
    }
}
