<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Milestone extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'title', 'category', 'due_date', 'progress', 'notes', 'color'];

    protected $casts = [
        'due_date' => 'date',
        'progress' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
