<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['service_category_id', 'name', 'slug', 'description', 'estimated_days', 'is_active'];

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function requirements()
    {
        return $this->hasMany(ServiceRequirement::class);
    }

    public function steps()
    {
        return $this->hasMany(ServiceStep::class);
    }

    public function fees()
    {
        return $this->hasMany(ServiceFee::class);
    }
}
