<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sale_id', 'branch_id', 'format', 'printed_by', 'printed_at'
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function printer() { return $this->belongsTo(User::class, 'printed_by'); }
}