<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'address', 'phone', 'email', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users() { return $this->hasMany(User::class); }
    public function branchProducts() { return $this->hasMany(BranchProduct::class); }
    public function inventoryMovements() { return $this->hasMany(InventoryMovement::class); }
    public function clients() { return $this->hasMany(Client::class); }
    public function cashRegisters() { return $this->hasMany(CashRegister::class); }
    public function preSales() { return $this->hasMany(PreSale::class); }
    public function sales() { return $this->hasMany(Sale::class); }
    public function credits() { return $this->hasMany(Credit::class); }
    public function deliveryNotes() { return $this->hasMany(DeliveryNote::class); }
}