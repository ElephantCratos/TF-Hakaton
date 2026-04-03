<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Contracts\CalculableContract;
use Modules\Training\Enums\TrainingStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingGroup extends Model implements CalculableContract
{
    protected $fillable = [
        'course_id',
        'specification_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => TrainingStatus::class,
        ];
    }


    public function course(): BelongsTo
    {
        return $this->belongsTo(\Modules\Course\Models\Course::class);
    }

  //  public function specification(): BelongsTo
  //  {
  //      return $this->belongsTo(\Modules\Specification\Models\Specification::class);
  //  }

    public function participants(): HasMany
    {
        return $this->hasMany(GroupParticipant::class);
    }


    /**
     * Количество участников обучения.
     */
    public function getParticipantsCountAttribute(): int
    {
        return $this->participants()->count();
    }

    /**
     * Цена курса за человека (из связанного курса).
     * TODO: учесть времязависимость цены через CoursePricingService.
     */
    public function getPricePerPersonAttribute(): float
    {
        return $this->course?->price ?? 0;
    }

    /**
     * Стоимость за группу = цена за человека * кол-во участников.
     */
    public function getGroupCostAttribute(): float
    {
        return $this->price_per_person * $this->participants_count;
    }

    /**
     * Средний прогресс по группе, в %.
     */
    public function getAverageProgressAttribute(): float
    {
        $avg = $this->participants()->avg('completion_percent');

        return round($avg ?? 0, 2);
    }

    // --- CalculableContract ---

    /**
     * Пересчёт стоимости группы.
     * Логика: цена курса за человека * количество участников группы.
     * вычисляем все это добро через аксессоры.
     */
    public function recalculateCost(): void
    {
        $this->unsetRelation('participants');
    }

    public function getTotalCost(): float
    {
        return $this->group_cost;
    }

    public function scopeWithStatus($query, TrainingStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Группы, пересекающиеся по датам с указанным периодом.
     * Закладка под диаграммы Ганта
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where('start_date', '<=', $endDate)
                     ->where('end_date', '>=', $startDate);
    }

    /**
     * Конфликты — группы по тому же курсу с пересекающимися датами.
     */
    public function scopeConflictsWith($query, self $group)
    {
        return $query->where('course_id', $group->course_id)
                     ->where('id', '!=', $group->id)
                     ->inPeriod($group->start_date, $group->end_date);
    }
}
