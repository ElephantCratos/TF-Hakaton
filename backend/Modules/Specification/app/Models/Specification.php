<?php

namespace Modules\Specification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Contracts\CalculableContract;
use Modules\Core\Contracts\HasCompanyScope;
use Modules\Core\Traits\HasCompany;
use Modules\Core\Traits\CalculatesVAT;

class Specification extends Model implements CalculableContract, HasCompanyScope
{
    use HasCompany, CalculatesVAT;

    protected $fillable = [
        'number',
        'date',
        'company_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function trainingGroups(): HasMany
    {
        return $this->hasMany(\Modules\Training\Models\TrainingGroup::class);
    }

    public function getTotalWithoutVatAttribute(): float
    {
        return $this->trainingGroups->sum(function ($group) {
            return $group->group_cost;
        });
    }

    public function getVatAmountAttribute(): float
    {
        return $this->calculateVAT($this->total_without_vat);
    }

    public function getTotalWithVatAttribute(): float
    {
        return $this->calculateTotalWithVAT($this->total_without_vat);
    }

    public function getGroupsCountAttribute(): int
    {
        return $this->trainingGroups()->count();
    }

    public function recalculateCost(): void
    {
        $this->unsetRelation('trainingGroups');
    }

    public function getTotalCost(): float
    {
        return $this->total_with_vat;
    }
}
