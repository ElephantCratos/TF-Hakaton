<?php

namespace Modules\Course\Services;

use Modules\Course\Models\Course;
class CourseService
{
    public function list()
    {
        $course = Course::all();
        return $course;
    }

    public function update(array $data, int $id) 
    {
        $course = Course::findOrFail($id);

        $course->title = $data['title'];
        $course->description = $data['description'];
        $course->duration_days = $data['duration_days'];

        $course->save();
        
        return $course;
    }

    public function create(array $data)
    {

        $cource = Course::create([
            'title' => $data['title'],
            'description' => $data['description'],
            'duration_days' => $data['duration_days'],
        ]);

        return $cource;
    }

    public function soft(int $id)
    {
        $course = Course::findOrFail($id);

        $course->delete();

        return $course;
    }

    public function hard(int $id) 
    {
        $course = Course::findOrFail($id);
        
        $deleted = $course;

        $course->forceDelete();

        return $deleted;
    }
}