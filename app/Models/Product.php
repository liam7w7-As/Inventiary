<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'category_id', 'supplier_id',
        'product_type', 'units_per_box', 'purchase_price',
        'sale_price', 'box_discount', 'notes', 'has_inventory', 'is_active'
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'box_discount' => 'decimal:2',
        'has_inventory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category() { return $this->belongsTo(Category::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function branchProducts() { return $this->hasMany(BranchProduct::class); }
    public function inventoryMovements() { return $this->hasMany(InventoryMovement::class); }
}