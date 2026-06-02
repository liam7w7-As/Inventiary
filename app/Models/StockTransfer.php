<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'from_branch_id', 'to_branch_id', 'product_id', 'requested_by',
        'approved_by', 'quantity', 'status', 'notes', 'approved_at', 'completed_at'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function fromBranch() { return $this->belongsTo(Branch::class, 'from_branch_id'); }
    public function toBranch() { return $this->belongsTo(Branch::class, 'to_branch_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
}