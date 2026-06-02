<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreSaleItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pre_sale_id', 'product_id', 'quantity', 'sale_price',
        'discount', 'subtotal'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function preSale() { return $this->belongsTo(PreSale::class); }
    public function product() { return $this->belongsTo(Product::class); }
}