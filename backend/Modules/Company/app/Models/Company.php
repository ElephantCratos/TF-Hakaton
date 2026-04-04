<?php

namespace Modules\Company\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(\Modules\Employee\Models\Employee::class);
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(\Modules\Specification\Models\Specification::class);
    }
}
