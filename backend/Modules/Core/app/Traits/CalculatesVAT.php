<?php

namespace Modules\Core\Traits;

trait CalculatesVAT
{
    protected static float $vatRate = 0.22;

    public function calculateVAT(float $amount): float
    {
        return round($amount * static::$vatRate, 2);
    }

    public function calculateTotalWithVAT(float $amount): float
    {
        return round($amount + $this->calculateVAT($amount), 2);
    }
}
