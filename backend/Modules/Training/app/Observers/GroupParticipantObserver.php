<?php

namespace Modules\Training\Observers;

use Modules\Training\Models\GroupParticipant;

/**
 * Observer для автоматического пересчёта при изменении состава участников.
 *
 * Суть максимально простая: при любой операции над группой скидываем кэш связей и пересчитываем
 */
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