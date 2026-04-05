<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;


trait HasCompany
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(\Modules\Company\Models\Company::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
