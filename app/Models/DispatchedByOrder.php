<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchedByOrder extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'bulk_order_id',
        'name',
        'designation',
        'time',
        'signature',
        'created_at',
        'created_by',
        'modified_at',
        'modified_by'
    ];
}
