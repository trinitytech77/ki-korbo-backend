<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'user_id', 'task_id', 'document_type', 'file_name', 
        'storage_path', 'mime_type', 'is_verified', 'expiry_date'
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
