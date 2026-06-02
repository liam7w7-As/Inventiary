<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Credit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_id', 'client_id', 'branch_id', 'total_amount',
        'paid_amount', 'balance', 'due_date', 'status', 'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function payments() { return $this->hasMany(CreditPayment::class); }
}