<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
        'course_id' => 'integer',
    ];

    /**
     * The course this module belongs to
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Lessons under this module
     */
    public function lessons(): HasMany
    {
        // ✅ Use a query scope for ordering instead of chaining orderBy directly
        return $this->hasMany(Lesson::class)->orderBy('position', 'asc');
    }
}
