<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalStaffBloodOrder extends Model
{
    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
        'hospital_staff_id',
        'hospital_id',
        'patient_name',
        'patient_age',
        'patient_gender',
        'patient_email',
        'patient_phone',
        'blood_group_id',
        'blood_component_id',
        'quantity',
        'status_id',
        'approved_by',
        'created_at',
        'created_by',
        'modified_at',
        'modified_by',
    ];

    // Belongs to a status
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    // Belongs to blood group
    public function bloodGroup()
    {
        return $this->belongsTo(BloodGroup::class, 'blood_group_id', 'id');
    }

    // Belongs to blood component
    public function bloodComponent()
    {
        return $this->belongsTo(BloodProduct::class, 'blood_component_id', 'id');
    }
}
