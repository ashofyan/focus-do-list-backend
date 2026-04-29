<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubTask extends Model
{
    protected $fillable = ['todo_id', 'title', 'is_completed', 'sort_order', 'completed_at'];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }
}
