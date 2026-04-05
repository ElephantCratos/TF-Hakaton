<?php

namespace Modules\Course\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Abstracts\Models\BaseModel;
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'courses';
    protected $fillable = [
        'code',
        'title',
        'description',
        'duration_days',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function price(): HasMany
    {
        return $this->hasMany(CoursePrice::class, 'course_id');
    }

    public function getLastPriceNumeric() : ?string
    {
        return $this->price()->latest()->first()['price'];
    }

    public function lastPrice(): HasOne
    {
        return $this->hasOne(CoursePrice::class, 'course_id')->latestOfMany();
    }

    
}
