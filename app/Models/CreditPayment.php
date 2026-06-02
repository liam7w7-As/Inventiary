<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'credit_id', 'user_id', 'amount', 'payment_date', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function credit() { return $this->belongsTo(Credit::class); }
    public function user() { return $this->belongsTo(User::class); }
}