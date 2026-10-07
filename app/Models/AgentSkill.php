<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentSkill extends Model
{
    protected $fillable = ['agent_id', 'skill_name'];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
