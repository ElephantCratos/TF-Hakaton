<?php

namespace Modules\Specification\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Specification\Http\Requests\StoreSpecificationRequest;
use Modules\Specification\Http\Requests\UpdateSpecificationRequest;
use Modules\Specification\Http\Resources\SpecificationResource;
use Modules\Specification\Models\Specification;
use Modules\Specification\Services\SpecificationService;
use Modules\Training\Models\TrainingGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpecificationController extends BaseController
{
    public function __construct(
        private readonly SpecificationService $specificationService,
    ) {}

    /**
     * GET /api/specifications
     */
    public function index(Request $request): JsonResponse
    {
        $specifications = Specification::with(['company', 'trainingGroups.course', 'trainingGroups.participants'])
            ->when($request->company_id, fn ($q, $id) => $q->forCompany($id))
            ->paginate($request->per_page ?? 15);

        return $this->success(
            SpecificationResource::collection($specifications)->response()->getData()
        );
    }

    /**
     * GET /api/specifications/{specification}
     */
    public function show(Specification $specification): JsonResponse
    {
        $specification->load(['company', 'trainingGroups.course', 'trainingGroups.participants']);

        return $this->success(new SpecificationResource($specification));
    }

    /**
     * POST /api/specifications
     */
    public function store(StoreSpecificationRequest $request): JsonResponse
    {
        $this->authorize('create', Specification::class);

        $specification = $this->specificationService->create($request->validated());

        return $this->created(
            new SpecificationResource($specification->load('company'))
        );
    }

    /**
     * PUT /api/specifications/{specification}
     */
    public function update(UpdateSpecificationRequest $request, Specification $specification): JsonResponse
    {
        $this->authorize('update', $specification);

        $specification = $this->specificationService->update($specification, $request->validated());

        return $this->success(
            new SpecificationResource($specification->load('company'))
        );
    }

    /**
     * DELETE /api/specifications/{specification}
     */
    public function destroy(Specification $specification): JsonResponse
    {
        $this->authorize('delete', $specification);

        $this->specificationService->delete($specification);

        return $this->noContent();
    }

    /**
     * POST /api/specifications/{specification}/groups/{training_group}
     */
    public function attachGroup(Specification $specification, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('attachGroup', $specification);

        $this->specificationService->attachGroup($specification, $trainingGroup);

        return $this->success(
            new SpecificationResource($specification->load(['company', 'trainingGroups.course', 'trainingGroups.participants']))
        );
    }

    /**
     * DELETE /api/specifications/{specification}/groups/{training_group}
     */
    public function detachGroup(Specification $specification, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('detachGroup', $specification);

        $this->specificationService->detachGroup($trainingGroup);

        return $this->success(
            new SpecificationResource($specification->fresh()->load(['company', 'trainingGroups.course', 'trainingGroups.participants']))
        );
    }
}
