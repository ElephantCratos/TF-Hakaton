<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    protected $fillable = [
        'employee_code',
        'last_name',
        'first_name',
        'middle_name',
        'full_name',
        'email',
        'company_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            \Modules\Company\Models\Company::class,
            'company_id'
        );
    }

    public function createdSpecifications(): HasMany
    {
        return $this->hasMany(
            \Modules\Specification\Models\Specification::class,
            'created_by'
        );
    }

    public function createdTrainingGroups(): HasMany
    {
        return $this->hasMany(
            \Modules\Training\Models\TrainingGroup::class,
            'created_by'
        );
    }

    public function groupParticipants(): HasMany
    {
        return $this->hasMany(
            \Modules\Training\Models\GroupParticipant::class,
            'employee_id'
        );
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getShortNameAttribute(): string
    {
        $firstInitial = $this->first_name
            ? mb_substr($this->first_name, 0, 1) . '.'
            : '';

        $middleInitial = $this->middle_name
            ? mb_substr($this->middle_name, 0, 1) . '.'
            : '';

        return trim("{$this->last_name} {$firstInitial}{$middleInitial}");
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}