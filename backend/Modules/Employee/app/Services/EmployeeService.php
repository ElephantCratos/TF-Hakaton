<?php

namespace Modules\Employee\Services;

use Modules\Employee\Models\Employee;

class EmployeeService
{
    public function list()
    {
        return Employee::with('company')->get();
    }

    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    public function update(array $data, int $id): Employee
    {
        $employee = Employee::findOrFail($id);

        $employee->update($data);

        return $employee;
    }

    public function soft(int $id): Employee
    {
        $employee = Employee::findOrFail($id);

        $employee->delete();

        return $employee;
    }

    public function hard(int $id): Employee
    {
        $employee = Employee::withTrashed()->findOrFail($id);

        $deleted = clone $employee;

        $employee->forceDelete();

        return $deleted;
    }
}