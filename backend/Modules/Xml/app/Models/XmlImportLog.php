<?php

namespace Modules\Xml\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XmlImportLog extends Model
{
    protected $table = 'xml_import_logs';

    // Только created_at из схемы
    const UPDATED_AT = null;

    // Статусы записи
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR   = 'error';
    const STATUS_SKIPPED = 'skipped';

    // Типы операций
    const OP_CREATE = 'create';
    const OP_UPDATE = 'update';
    const OP_SKIP   = 'skip';

    protected $fillable = [
        'batch_id',
        'entity_name',
        'entity_external_id',
        'operation_type',
        'status',
        'message',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function batch(): BelongsTo
    {
        return $this->belongsTo(XmlImportBatch::class, 'batch_id');
    }
}