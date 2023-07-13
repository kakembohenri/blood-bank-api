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


    /** DATABASE RELATIONSHIPS
     * 
     */

    // Belongs to a user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Belongs to a hospital
    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }
}
