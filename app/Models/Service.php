<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_category_id',
        'name',
        'slug',
        'description',
        'estimated_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Resolve route binding by either ID or slug.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', $value)->orWhere('slug', $value)->firstOrFail();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ServiceRequirement::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ServiceStep::class)->orderBy('step_order', 'asc');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(ServiceFee::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get government fee amount.
     */
    public function getGovernmentFeeAttribute(): float
    {
        return (float) ($this->fees->firstWhere('fee_type', 'GOVERNMENT')?->amount ?? 0.00);
    }

    /**
     * Get KORBO platform service fee amount.
     */
    public function getKorboFeeAttribute(): float
    {
        return (float) ($this->fees->firstWhere('fee_type', 'KORBO')?->amount ?? 0.00);
    }

    /**
     * Get field agent fee amount.
     */
    public function getAgentFeeAttribute(): float
    {
        return (float) ($this->fees->firstWhere('fee_type', 'AGENT')?->amount ?? 0.00);
    }

    /**
     * Calculate total fees.
     */
    public function getTotalFeeAttribute(): float
    {
        return (float) $this->fees->sum('amount');
    }

    /**
     * Get complete fee breakdown dictionary.
     */
    public function getFeeBreakdownAttribute(): array
    {
        return [
            'government_fee' => $this->government_fee,
            'korbo_fee' => $this->korbo_fee,
            'agent_fee' => $this->agent_fee,
            'total_amount' => $this->total_fee,
        ];
    }
}
