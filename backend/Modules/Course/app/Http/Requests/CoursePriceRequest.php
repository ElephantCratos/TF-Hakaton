<?php

namespace Modules\Course\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CoursePriceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'price' => ['required', 'decimal:2'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
