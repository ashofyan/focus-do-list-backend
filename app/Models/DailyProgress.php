<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyProgress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'todo_id',
        'progress_date',
        'title',
        'description',
    ];

    protected $casts = [
        'progress_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }
}
