<?php

namespace Modules\Training\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Training\Http\Requests\StoreTrainingGroupRequest;
use Modules\Training\Http\Requests\UpdateTrainingGroupRequest;
use Modules\Training\Http\Resources\TrainingGroupResource;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Services\TrainingGroupService;
use Modules\Training\Enums\TrainingStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingGroupController extends BaseController
{
    public function __construct(
        private readonly TrainingGroupService $service,
    ) {}

    /**
     * GET /api/training-groups
     */
    public function index(Request $request): JsonResponse
    {
        $groups = TrainingGroup::with(['course', 'participants'])
            ->when($request->status, fn ($q, $s) => $q->withStatus(TrainingStatus::from($s)))
            ->paginate($request->per_page ?? 15);

        return $this->success(
            TrainingGroupResource::collection($groups)->response()->getData()
        );
    }

    /**
     * GET /api/training-groups/{trainingGroup}
     */
    public function show(TrainingGroup $trainingGroup): JsonResponse
    {
        $trainingGroup->load(['course', 'participants.employee']);

        return $this->success(new TrainingGroupResource($trainingGroup));
    }

    /**
     * POST /api/training-groups
     */
    public function store(StoreTrainingGroupRequest $request): JsonResponse
    {
        $this->authorize('create', TrainingGroup::class);

        $group = $this->service->create($request->validated());

        return $this->created(new TrainingGroupResource($group->load('course')));
    }

    /**
     * PUT /api/training-groups/{trainingGroup}
     */
    public function update(UpdateTrainingGroupRequest $request, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('update', $trainingGroup);

        $group = $this->service->update($trainingGroup, $request->validated());

        return $this->success(new TrainingGroupResource($group->load('course')));
    }

    /**
     * DELETE /api/training-groups/{trainingGroup}
     */
    public function destroy(TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('delete', $trainingGroup);

        $this->service->delete($trainingGroup);

        return $this->noContent();
    }

    /**
     * PATCH /api/training-groups/{trainingGroup}/status
     */
    public function changeStatus(Request $request, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('changeStatus', $trainingGroup);

        $request->validate(['status' => ['required', 'string']]);

        $group = $this->service->changeStatus(
            $trainingGroup,
            TrainingStatus::from($request->status)
        );

        return $this->success(new TrainingGroupResource($group));
    }

    /**
     * GET /api/training-groups/{trainingGroup}/conflicts
     */
    public function conflicts(TrainingGroup $trainingGroup): JsonResponse
    {
        $conflicts = $this->service->findConflicts($trainingGroup);

        return $this->success(TrainingGroupResource::collection($conflicts));
    }
}
