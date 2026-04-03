<?php

namespace Modules\Course\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Abstracts\Models\BaseModel;
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Course\Database\Factories\CourseFactory;

class Course extends Model
{
    use HasFactory, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'duration_days',
    ];

    // protected static function newFactory(): CourseFactory
    // {
    //     // return CourseFactory::new();
    // }
}
