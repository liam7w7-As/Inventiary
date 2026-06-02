<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'product_id', 'current_stock', 'min_stock'
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function product() { return $this->belongsTo(Product::class); }
}