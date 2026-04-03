<?php

namespace Modules\Core\Abstracts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * По умолчанию авторизованные мужички ходят
     */
    public function authorize(): bool
    {
        return auth()->check();
    }
}
