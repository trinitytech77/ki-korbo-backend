<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'document_name',
        'is_mandatory',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
