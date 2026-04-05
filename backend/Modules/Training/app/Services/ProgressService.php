<?php

namespace Modules\Training\Services;

use Modules\Training\Models\TrainingGroup;
use Modules\Training\Models\GroupParticipant;

class ProgressService
{
   
    public function getGroupProgress(TrainingGroup $group): float
    {
        $avg = $group->participants()->avg('completion_percent');

        return round($avg ?? 0, 2);
    }

    public function updateParticipantProgress(GroupParticipant $participant, float $percent): GroupParticipant
    {
        $percent = max(0, min(100, $percent));

        $participant->update(['completion_percent' => $percent]);

        return $participant->fresh();
    }

    public function setGroupProgress(TrainingGroup $group, float $percent): void
    {
        $percent = max(0, min(100, $percent));

        $group->participants()->update(['completion_percent' => $percent]);
    }
}