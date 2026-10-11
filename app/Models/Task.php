<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'agent_id',
        'status', 
        'government_fee',
        'service_fee',
        'agent_fee',
        'total_amount', 
        'location',
        'appointment_date',
        'notes',
        'completed_at',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Determine if this task is assigned to a specific agent user ID.
     */
    public function isAssignedTo(int|string $agentUserId): bool
    {
        if ((int) $this->agent_id === (int) $agentUserId) {
            return true;
        }

        return $this->assignments()->where('agent_id', $agentUserId)->exists();
    }
}
