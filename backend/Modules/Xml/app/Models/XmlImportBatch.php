<?php

namespace Modules\Xml\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XmlImportBatch extends Model
{
    protected $table = 'xml_import_batches';

    public $timestamps = false;

    protected $fillable = [
        'source_system',
        'file_name',
        'imported_at',
        'processed_by',
        'raw_payload',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(XmlImportLog::class, 'batch_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Auth\Models\User::class, 'processed_by');
    }

    public function getSuccessCountAttribute(): int
    {
        return $this->logs()->where('status', 'success')->count();
    }

    public function getErrorCountAttribute(): int
    {
        return $this->logs()->where('status', 'error')->count();
    }

    public function getSkippedCountAttribute(): int
    {
        return $this->logs()->where('status', 'skipped')->count();
    }
}