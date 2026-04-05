<?php

namespace Modules\Training\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GroupParticipant extends Model
{
    protected $fillable = [
        'training_group_id',
        'employee_id',
        'completion_percent',
        'certificate_path',      // <-- новое поле
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

    public function hasCertificate(): bool
    {
        return $this->certificate_path !== null
            && Storage::disk('public')->exists($this->certificate_path);
    }

    public function deleteCertificate(): void
    {
        if ($this->certificate_path) {
            Storage::disk('public')->delete($this->certificate_path);
            $this->update(['certificate_path' => null]);
        }
    }
}