<?php

namespace Modules\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Course\Models\Course;

class CoursePrice extends Model
{
    use HasFactory;

    protected $table = 'course_price';

    protected $fillable = [
        'course_id',
        'price',
        'valid_from',
        'valid_to',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

}
