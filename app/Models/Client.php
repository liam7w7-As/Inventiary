<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'branch_id', 'name', 'phone', 'email', 'notes'
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function preSales() { return $this->hasMany(PreSale::class); }
    public function sales() { return $this->hasMany(Sale::class); }
    public function credits() { return $this->hasMany(Credit::class); }
}