<?php

namespace Modules\Core\Traits;

/**
 * Трейт для расчёта НДС.
 * Ставка НДС — 22% (по ТЗ).
 */
trait CalculatesVAT
{
    protected static float $vatRate = 0.22;

    /**
     * Рассчитать сумму НДС от базовой суммы.
     */
    public function calculateVAT(float $amount): float
    {
        return round($amount * static::$vatRate, 2);
    }

    /**
     * Рассчитать итого с НДС.
     */
    public function calculateTotalWithVAT(float $amount): float
    {
        return round($amount + $this->calculateVAT($amount), 2);
    }
}
