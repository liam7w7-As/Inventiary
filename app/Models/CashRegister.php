<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegister extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'user_id', 'opened_by', 'initial_balance',
        'sale_limit', 'opened_at', 'closed_at', 'status', 'notes'
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'sale_limit' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function opener() { return $this->belongsTo(User::class, 'opened_by'); }
    public function sales() { return $this->hasMany(Sale::class); }
}