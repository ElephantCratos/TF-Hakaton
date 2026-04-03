<?php

namespace Modules\Company\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Company\Services\CompanyService;
use Modules\Company\Transformers\CompanyResource;
use Modules\Company\Http\Requests\CompanyRequest;

class CompanyController extends BaseController
{
    public function __construct(private readonly CompanyService $companyService) {}

    public function list()
    {
        $companies = $this->companyService->list();
        return response()->json(['companies' => $companies], 200);
    }

    public function create(CompanyRequest $request)
{
    $result = $this->companyService->create($request->validated());

    return $this->created(
        [
            'company' => new CompanyResource($result)
        ],
        'Компания создана'
    );
}

    public function update(CompanyRequest $request, int $id)
{
    $result = $this->companyService->update(
        $request->validated(),
        $id
    );

    return $this->success(
        [
            'company' => new CompanyResource($result)
        ],
        'Компания обновлена'
    );
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