<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_id',
        'title',
        'content',
        'content_type',
        'content_url',
        'position',
        'duration_seconds',
    ];

    protected $casts = [
        'module_id' => 'integer',
        'position' => 'integer',
        'duration_seconds' => 'integer',
    ];

    /**
     * The module this lesson belongs to
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
