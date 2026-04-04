<?php

namespace Modules\Company\Services;

use Modules\Company\Models\Company;

class CompanyService
{
    public function list()
    {
        return Company::all();
    }

    public function create(array $data): Company
    {
        return Company::create([
            'code' => $data['code'],
            'name' => $data['name'],
        ]);
    }

    public function update(array $data, int $id): Company
    {
        $company = Company::findOrFail($id);
        $company->code = $data['code'];
        $company->name = $data['name'];
        $company->save();

        return $company;
    }

    public function soft(int $id): Company
    {
        $company = Company::findOrFail($id);
        $company->delete();
        return $company;
    }

    public function hard(int $id): Company
    {
        $company = Company::withTrashed()->findOrFail($id);
        $deleted = $company;
        $company->forceDelete();
        return $deleted;
    }
}