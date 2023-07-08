<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrderItem extends Model
{
    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
        'bulk_order',
        'blood_unit',
        'hospital_id',
        'created_at',
        'created_by',
        'modified_at',
        'modified_by'
    ];

    // A blood unit item has one blood unit
    // public function bloodUnit()
    // {
    //     return $this->hasOne(BloodUnit::class);
    // }
}
