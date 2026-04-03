<?php

namespace Modules\Core\Abstracts\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseResource extends JsonResource
{
    /**
     * Убираем обёртку "data" на уровне единичного ресурса.
     */
    public static $wrap = null;
}
