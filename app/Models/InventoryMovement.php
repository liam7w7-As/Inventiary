<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'product_id', 'user_id', 'movement_type',
        'quantity', 'purchase_price', 'stock_before', 'stock_after',
        'reference_id', 'reference_type', 'notes'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'stock_before' => 'decimal:2',
        'stock_after' => 'decimal:2',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function user() { return $this->belongsTo(User::class); }
}