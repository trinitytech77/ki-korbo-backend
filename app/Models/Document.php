<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'task_id',
        'document_type',
        'file_name', 
        'storage_path',
        'mime_type',
        'is_verified',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'expiry_date' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(DocumentAccessGrant::class);
    }

    /**
     * Check if a specific agent user has an active access grant to this document.
     */
    public function hasActiveGrantFor(int|string $agentUserId): bool
    {
        return $this->accessGrants()
            ->where('agent_id', $agentUserId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
