<?php

namespace Modules\Training\Http\Controllers;

use Modules\Core\Abstracts\Http\Controllers\BaseController;
use Modules\Training\Http\Requests\StoreGroupParticipantRequest;
use Modules\Training\Http\Requests\UpdateGroupParticipantRequest;
use Modules\Training\Http\Resources\GroupParticipantResource;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Models\GroupParticipant;
use Modules\Training\Services\ProgressService;
use Illuminate\Http\JsonResponse;

class GroupParticipantController extends BaseController
{
    public function __construct(
        private readonly ProgressService $progressService,
    ) {}

    public function index(TrainingGroup $trainingGroup): JsonResponse
    {
        $participants = $trainingGroup->participants()->with('employee')->get();

        return $this->success(GroupParticipantResource::collection($participants));
    }

    public function store(StoreGroupParticipantRequest $request, TrainingGroup $trainingGroup): JsonResponse
    {
        $this->authorize('update', $trainingGroup);

        $participant = $trainingGroup->participants()->create([
            'employee_id' => $request->employee_id,
            'completion_percent' => 0,
        ]);

        return $this->created(
            new GroupParticipantResource($participant->load('employee'))
        );
    }

    public function update(
        UpdateGroupParticipantRequest $request,
        TrainingGroup $trainingGroup,
        GroupParticipant $participant,
    ): JsonResponse {
        $this->authorize('updateProgress', $trainingGroup);

        $participant = $this->progressService->updateParticipantProgress(
            $participant,
            $request->completion_percent
        );

        return $this->success(new GroupParticipantResource($participant));
    }

    public function destroy(TrainingGroup $trainingGroup, GroupParticipant $participant): JsonResponse
    {
        $this->authorize('update', $trainingGroup);

        $participant->delete();

        return $this->noContent();
    }
}
