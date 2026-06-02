<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreSale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'client_id', 'requested_by', 'approved_by',
        'status', 'notes', 'approved_at', 'converted_at',
        'payment_type', 'requires_approval', 'credit_installments',
        'credit_period_days', 'approved_installments',
        'approved_period_days', 'credit_notes'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'converted_at' => 'datetime',
        'requires_approval' => 'boolean',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function items() { return $this->hasMany(PreSaleItem::class); }

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true)
                     ->where('status', 'pending');
    }

    public function isCredit()
    {
        return $this->payment_type === 'credit';
    }

    public function canAutoApprove()
    {
        return $this->payment_type === 'cash' && $this->requires_approval === false;
    }
}