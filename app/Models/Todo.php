<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Todo extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'group_id',
        'milestone_id',
        'title',
        'description',
        'due_date',
        'priority',
        'status',
        'is_pinned',
        'completed_at',
        'sort_order',
    ];

    protected $casts = [
        'due_date'     => 'datetime',
        'completed_at' => 'datetime',
        'is_pinned'    => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function subTasks(): HasMany
    {
        return $this->hasMany(SubTask::class)->orderBy('sort_order');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'todo_label');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Tandai todo sebagai selesai.
     */
    public function markComplete(): void
    {
        $this->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Persentase sub-task yang sudah selesai.
     */
    public function subTaskProgress(): int
    {
        $total = $this->subTasks()->count();
        if ($total === 0) return 0;

        $done = $this->subTasks()->where('is_completed', true)->count();
        return (int) round(($done / $total) * 100);
    }
}
