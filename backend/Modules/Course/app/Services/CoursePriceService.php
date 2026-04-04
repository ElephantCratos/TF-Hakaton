<?php

namespace Modules\Course\Services;

use Modules\Course\Models\CoursePrice;
use Modules\Course\Models\Course;

class CoursePriceService
{
    public function list($courseId)
    {
        $coursePrice = CoursePrice::where('course_id', $courseId)
        ->orderBy('valid_from', 'desc')
        ->get();

        return $coursePrice;
    }

    public function create(array $data, int $courseId)
    {
        $previousPrice = CoursePrice::where('course_id', $courseId)
            ->whereNull('valid_to')
            ->latest('valid_from')
            ->first();
        
        if ($previousPrice) {
            $previousPrice->update([
                'valid_to' => now()->subDay()->toDateString(), 
            ]);
        }
        $coursePrice = CoursePrice::create([
            'course_id'  => $courseId,
            'price'      => $data['price'],
            'valid_from' => now()->toDateString(),
            'valid_to'   => null, 
        ]);

        return $coursePrice;
    }
}
