<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodUnit extends Model
{
    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
        'blood_group',
        'blood_product',
        'date_of_expiry',
        'status_id',
        'created_at',
        'created_by',
        'modified_at',
        'modified_by'
    ];
}
