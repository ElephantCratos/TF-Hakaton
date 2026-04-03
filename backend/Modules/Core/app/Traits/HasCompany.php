<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Трейт для моделей, привязанных к компании.
 * Подключает связь и scope для фильтрации.
 */
trait HasCompany
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Company\Models\Company::class);
    }

    /**
     * Scope: фильтрация по компании.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
