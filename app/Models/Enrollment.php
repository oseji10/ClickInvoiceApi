<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'started_at',
        'completed_at',
        'payment_reference'
    ];

    /**
     * The user that owns this enrollment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The course that this enrollment belongs to
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    /**
     * Progress records for this enrollment
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class, 'enrollment_id');
    }
}
