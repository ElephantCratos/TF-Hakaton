<?php

namespace Modules\Company\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Company\Services\CompanyService;

class CompanyController extends BaseController
{
    public function __construct(private readonly CompanyService $companyService) {}

    public function list()
    {
        $companies = $this->companyService->list();
        return response()->json(['companies' => $companies], 200);
    }

    public function create(Request $request)
    {
        $company = $this->companyService->create($request->validate([
            'code' => 'required|string|max:10|unique:companies,code',
            'name' => 'required|string|max:255',
        ]));

        return $this->created(['company' => $company], 'Компания создана');
    }

    public function update(Request $request, int $id)
    {
        $company = $this->companyService->update($request->validate([
            'code' => "required|string|max:10|unique:companies,code,{$id}",
            'name' => 'required|string|max:255',
        ]), $id);

        return $this->success(['company' => $company], 'Компания обновлена');
    }

    public function soft(int $id)
    {
        $company = $this->companyService->soft($id);
        return $this->success(['company' => $company], 'Компания удалена (soft)');
    }

    public function hard(int $id)
    {
        $company = $this->companyService->hard($id);
        return $this->success(['company' => $company], 'Компания удалена полностью');
    }
}