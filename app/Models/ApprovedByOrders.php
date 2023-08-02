<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovedByOrders extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'bulk_order_id',
        'name',
        'designation',
        'signature',
    ];
}
