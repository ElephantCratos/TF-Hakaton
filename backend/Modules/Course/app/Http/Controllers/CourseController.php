<?php

namespace Modules\Course\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Course\Services\CourseService;
use Modules\Course\Transformers\CourseResource;
use Modules\Course\Http\Requests\CourseRequest;
use Illuminate\Http\Request;

class CourseController extends BaseController
{

    public function __construct(
        private readonly CourseService $couresService,
    ) {
    }

    public function list()
    {
        $result = $this->couresService->list();

        return response()->json([
            'courses' => $result
        ], 200);
    }

    public function create(CourseRequest $request)
    {
        $result = $this->couresService->create($request->validated());

        return $this->created(
            [
                'course' => new CourseResource($result)
            ],
            'Курс создан'
        );
    }

    public function soft(int $courseId)
    {
        $result = $this->couresService->soft($courseId);

        return $this->success(
            [
                'course' => $result
            ],
            'Курс удалён (soft)'
        );
    }

    public function hard(int $courseId)
    {
        $result = $this->couresService->hard($courseId);

        return $this->success(
            [
                'course' => $result
            ],
            'Курс удалён'
        );
    }

    public function store(Request $request)
    {
    }

    public function show($courseId)
    {
        return view('course::show');
    }

    public function edit($courseId)
    {
        return view('course::edit');
    }

    public function update(CourseRequest $request, int $courseId)
    {
        $result = $this->couresService->update($request->validated(), $courseId);
        
        return $this->success([
            'updated course' => $result],
            'Курс обновлён'
        );
    }

    public function destroy($courseId)
    {
    }
}
