<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'branch_id', 'action', 'model_type',
        'model_id', 'description', 'ip_address'
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
}