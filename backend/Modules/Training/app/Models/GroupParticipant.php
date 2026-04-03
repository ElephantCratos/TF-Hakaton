<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupParticipant extends Model
{
    protected $fillable = [
        'training_group_id',
        'employee_id',
        'completion_percent',
    ];

    protected function casts(): array
    {
        return [
            'completion_percent' => 'float',
        ];
    }

    public function trainingGroup(): BelongsTo
    {
        return $this->belongsTo(TrainingGroup::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\Modules\Employee\Models\Employee::class);
    }
}
