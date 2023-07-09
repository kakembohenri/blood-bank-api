<?php

namespace App\Models;

use App\AuditTrail\AuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalInventory extends Model
{
    public $timestamps = false;
    use HasFactory;

    protected $fillable = [
        'hospital_id',
        'blood_unit',
        'status_id',
        'created_at',
        'created_by',
        'modified_at',
        'modified_by',
    ];
}
