<?php

namespace Modules\Core\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Контракт для объектов, привязанных к компании.
 */
interface HasCompanyScope
{
    public function company(): BelongsTo;
}
