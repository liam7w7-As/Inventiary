<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'branch_id', 'cash_register_id', 'client_id', 'user_id',
        'pre_sale_id', 'sale_type', 'payment_type', 'subtotal',
        'discount', 'total', 'status', 'cancel_reason', 'cancelled_by',
        'cancelled_at', 'notes'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function cashRegister() { return $this->belongsTo(CashRegister::class); }
    public function client() { return $this->belongsTo(Client::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function preSale() { return $this->belongsTo(PreSale::class); }
    public function canceller() { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function items() { return $this->hasMany(SaleItem::class); }
    public function credits() { return $this->hasMany(Credit::class); }
    public function deliveryNotes() { return $this->hasMany(DeliveryNote::class); }
}