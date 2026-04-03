<?php

namespace Modules\Training\Services;

use Modules\Training\Models\TrainingGroup;

/**
 * Сервис расчёта стоимости обучения.
 *
 * Короче говоря, считаем примерно так,чтобы было понятно и быстро.
 *
 * 1. Стоимость группы = Цена курса за человека × Количество участников группы
 *    - Цена берётся из связанного курса (Course.price)
 *    - Количество участников = число записей в group_participants для данной группы
 *
 * 2. При изменении кол-ва участников стоимость пересчитываем автоматически через Observer.
 *
 * 3. При изменении цены курса требуется ручной пересчёт или вызов recalculate().
 *
 * НАДО БУДЕТ СДЕЛАТЬ: реализовать времязависимость цены — использовать цену,
 *       актуальную на дату начала группы (через CoursePricingService).
 */
class CostCalculationService
{
    /**
     * Расчёт стоимости одной группы.
     *
     * Формула: цена за чела × количество челов
     */
    public function calculateGroupCost(TrainingGroup $group): float
    {
        $pricePerPerson = $group->course?->price ?? 0;
        $participantsCount = $group->participants()->count();

        return round($pricePerPerson * $participantsCount, 2);
    }

    /**
     * Пересчёт стоимости группы.
     * Вызывается из Observer при изменении состава участников.
     */
    public function recalculate(TrainingGroup $group): float
    {
        $group->unsetRelation('participants');
        $group->unsetRelation('course');

        return $this->calculateGroupCost($group);
    }
}
