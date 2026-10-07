<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'user_id', 'service_id', 'agent_id', 'status', 
        'government_fee', 'service_fee', 'agent_fee', 'total_amount', 
        'location', 'appointment_date', 'notes', 'completed_at'
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(TaskStatusHistory::class);
    }
}
