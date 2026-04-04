<?php

namespace Modules\Course\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Course\Services\CoursePriceService;
use Illuminate\Http\Request;
use Modules\Course\Http\Requests\CoursePriceRequest;
use Modules\Course\Transformers\CoursePriceResource;

class CoursePriceController extends BaseController
{
    /**
     * Display a listing of the resource.
     */

    public function __construct(
        private readonly CoursePriceService $coursePriceService,
    ) {
    }

    public function list(int $courseId) 
    {   
        $result = $this->coursePriceService->list($courseId);
        return $this->success(
            [
                'course_price' => $result
            ]
        );
    }
    public function index()
    {
        return view('course::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(CoursePriceRequest $request, int $courseId)
    {
        $result = $this->coursePriceService->create($request->validated(), $courseId);

        return $this->created(
            [
                'course_price' => new CoursePriceResource($result)
            ],
            'Установлена новая цена курса'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('course::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('course::edit');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
