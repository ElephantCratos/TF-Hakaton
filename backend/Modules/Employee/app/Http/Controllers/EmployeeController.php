<?php

namespace Modules\Employee\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Employee\Services\EmployeeService;
use Modules\Employee\Transformers\EmployeeResource;
use Modules\Employee\Http\Requests\EmployeeRequest;

class EmployeeController extends BaseController
{
    public function __construct(
        private readonly EmployeeService $employeeService,
    ) {
    }

    public function list()
    {
        $result = $this->employeeService->list();

        return response()->json([
            'employees' => $result
        ], 200);
    }

    public function create(EmployeeRequest $request)
    {
        $result = $this->employeeService->create($request->validated());

        return $this->created(
            [
                'employee' => new EmployeeResource($result)
            ],
            'Сотрудник создан'
        );
    }

    public function update(EmployeeRequest $request, int $id)
    {
        $result = $this->employeeService->update(
            $request->validated(),
            $id
        );

        return $this->success(
            [
                'employee' => new EmployeeResource($result)
            ],
            'Сотрудник обновлён'
        );
    }

    public function soft(int $id)
    {
        $result = $this->employeeService->soft($id);

        return $this->success(
            [
                'employee' => $result
            ],
            'Сотрудник удалён (soft)'
        );
    }

    public function hard(int $id)
    {
        $result = $this->employeeService->hard($id);

        return $this->success(
            [
                'employee' => $result
            ],
            'Сотрудник удалён'
        );
    }
}