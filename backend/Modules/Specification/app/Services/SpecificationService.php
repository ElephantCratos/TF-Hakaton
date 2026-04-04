<?php

namespace Modules\Specification\Services;

use Modules\Specification\Models\Specification;
use Modules\Training\Models\TrainingGroup;
use Illuminate\Database\Eloquent\Collection;

class SpecificationService
{
    public function create(array $data): Specification
    {
        return Specification::create($data);
    }

    public function update(Specification $specification, array $data): Specification
    {
        $specification->update($data);
        return $specification->fresh();
    }

    public function delete(Specification $specification): bool
    {
        // Открепляем группы перед удалением
        $specification->trainingGroups()->update(['specification_id' => null]);

        return $specification->delete();
    }

    /**
     * Привязать группу обучения к спецификации.
     */
    public function attachGroup(Specification $specification, TrainingGroup $group): void
    {
        if ($group->specification_id !== null && $group->specification_id !== $specification->id) {
            throw new \DomainException(
                "Группа #{$group->id} уже привязана к спецификации #{$group->specification_id}"
            );
        }

        $group->update(['specification_id' => $specification->id]);
    }

    /**
     * Открепить группу от спецификации.
     */
    public function detachGroup(TrainingGroup $group): void
    {
        $group->update(['specification_id' => null]);
    }

    /**
     * Получить все спецификации по компании.
     */
    public function getByCompany(int $companyId): Collection
    {
        return Specification::forCompany($companyId)
            ->with('trainingGroups')
            ->get();
    }
}
