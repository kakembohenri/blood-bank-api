<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalStaff extends Model
{
    public $timestamps = false;
    use HasFactory;

    protected $fillable = [
        'name',
        'hospital_id',
        'user_id',
        'created_at',
        'created_by',
        'modified_at',
        'modified_by'
    ];
}
