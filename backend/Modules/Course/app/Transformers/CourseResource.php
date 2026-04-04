<?php

namespace Modules\Course\Transformers;
use Modules\Core\Abstracts\Http\Resources\BaseResource;

class CourseResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'duration_days' => $this->duration_days,
        ];
    }
}
