<?php

namespace Modules\Training\Services;

use Modules\Training\Models\TrainingGroup;

class CostCalculationService
{
    public function calculateGroupCost(TrainingGroup $group): float
    {
        $pricePerPerson = $group->course?->price ?? 0;
        $participantsCount = $group->participants()->count();

        return round($pricePerPerson * $participantsCount, 2);
    }

    public function recalculate(TrainingGroup $group): float
    {
        $group->unsetRelation('participants');
        $group->unsetRelation('course');

        return $this->calculateGroupCost($group);
    }
}
