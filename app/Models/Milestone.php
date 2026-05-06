<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class)->orderBy('sort_order')->orderBy('due_date');
    }

    public function calculateTaskProgress(): int
    {
        $total = $this->todos()->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->todos()->where('status', 'completed')->count();

        return (int) round(($done / $total) * 100);
    }

    public function taskProgressStats(): array
    {
        $total = $this->todos()->count();
        $done = $this->todos()->where('status', 'completed')->count();

        return [
            'total' => $total,
            'completed' => $done,
            'pending' => max($total - $done, 0),
            'progress' => $total === 0 ? 0 : (int) round(($done / $total) * 100),
        ];
    }

    public function refreshProgressFromTasks(): void
    {
        $this->update(['progress' => $this->calculateTaskProgress()]);
    }
}
