<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'step_name',
        'step_order',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
