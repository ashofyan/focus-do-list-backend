<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EncryptedNote extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'encrypted_title',
        'encrypted_content',
        'note_iv',
        'note_tag',
        'encryption_version',
        'is_archived',
        'is_deleted',
        'is_pinned',
        'last_synced_at',
    ];

    protected $casts = [
        'note_tag' => 'array',
        'encryption_version' => 'integer',
        'is_archived' => 'boolean',
        'is_deleted' => 'boolean',
        'is_pinned' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
