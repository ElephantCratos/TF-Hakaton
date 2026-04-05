<?php

namespace Modules\Training\Observers;

use Modules\Training\Models\GroupParticipant;

class GroupParticipantObserver
{
    public function created(GroupParticipant $participant): void
    {
        $this->refreshGroup($participant);
    }

    public function updated(GroupParticipant $participant): void
    {
        $this->refreshGroup($participant);
    }

    public function deleted(GroupParticipant $participant): void
    {
        $this->refreshGroup($participant);
    }

    protected function refreshGroup(GroupParticipant $participant): void
    {
        if ($group = $participant->trainingGroup) {
            $group->recalculateCost();
        }
    }
}