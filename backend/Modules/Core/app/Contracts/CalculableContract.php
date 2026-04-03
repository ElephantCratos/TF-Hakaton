<?php

namespace Modules\Core\Contracts;

/**
 * Контракт для объектов с вычисляемой стоимостью.
 * Реализуется в TrainingGroup и Specification.
 */
interface CalculableContract
{
    /**
     * Пересчитать стоимость объекта.
     */
    public function recalculateCost(): void;

    /**
     * Получить итоговую стоимость.
     */
    public function getTotalCost(): float;
}
