<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditInstallment extends Model
{
    use SoftDeletes;

    protected $table = 'credit_installments';

    protected $fillable = [
        'credit_id', 'installment_number', 'amount', 'due_date',
        'paid_amount', 'paid_at', 'status'
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'paid_amount' => 'decimal:2',
        'amount' => 'decimal:2'
    ];

    public function credit()
    {
        return $this->belongsTo(Credit::class);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', today())
                     ->where('status', '!=', 'paid');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'partial']);
    }
}
