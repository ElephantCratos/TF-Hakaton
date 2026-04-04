<?php

namespace Modules\Training\Services;

use Modules\Core\Abstracts\Services\BaseService;
use Modules\Training\Models\TrainingGroup;
use Modules\Training\Enums\TrainingStatus;
use Illuminate\Database\Eloquent\Collection;

class TrainingGroupService extends BaseService
{
    protected function modelClass(): string
    {
        return TrainingGroup::class;
    }

    /**
     * Создание группы обучения.
     * По умолчанию считаем, что новая группа запланирована
     */
    public function create(array $data): TrainingGroup
    {
        $data['status'] = $data['status'] ?? TrainingStatus::Planned->value;

        return TrainingGroup::create($data);
    }

    /**
     * Смена статуса с проверкой допустимых переходов.
     */
    public function changeStatus(TrainingGroup $group, TrainingStatus $newStatus): TrainingGroup
    {
        if (! $group->status->canTransitionTo($newStatus)) {
            throw new \DomainException(
                "Недопустимый переход статуса: {$group->status->label()} → {$newStatus->label()}"
            );
        }

        $group->update(['status' => $newStatus]);

        return $group->fresh();
    }

    /**
     * Группы с конфликтами расписания (тот же курс, пересечение дат).
     */
    public function findConflicts(TrainingGroup $group): Collection
    {
        return TrainingGroup::conflictsWith($group)->get();
    }

    /**
     * Удаление группы с каскадным удалением участников.
     */
    public function delete(\Illuminate\Database\Eloquent\Model $group): bool
    {
        $group->participants()->delete();

        return $group->delete();
    }
}
