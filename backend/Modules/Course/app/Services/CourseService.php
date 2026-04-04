<?php

namespace Modules\Course\Services;

use Modules\Course\Models\Course;
use Modules\Course\Models\CoursePrice;

use Modules\Course\Services\CoursePriceService;

class CourseService

{
    public function __construct(
        private readonly CoursePriceService $coursePriceService,
    ) {
    }

    public function list()
    {
        $course = Course::all();
        return $course;
    }

    public function update(array $data, int $courseId) 
    {

        $course = Course::findOrFail($courseId);

        $course->code = $data['code'];
        $course->title = $data['title'];

        $this->coursePriceService->create($data, $courseId);
      
        $course->description = $data['description'];
        $course->duration_days = $data['duration_days'];

        $course->save();
        
        return $course;
    }

    public function create(array $data)
    {

        $course = Course::create([
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'],
            'duration_days' => $data['duration_days'],
        ]);

        $coursePrice = CoursePrice::create([
            'course_id' => $course->id,
            'price' => $data['price'],
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
        ]);

        return $course;
    }

    public function soft(int $id)
    {
        $course = Course::findOrFail($id);

        $course->delete();

        return $course;
    }

    public function hard(int $id) 
    {
        $course = Course::withTrashed()->findOrFail($id);
        
        $deleted = $course;

        $course->forceDelete();

        return $deleted;
    }
}