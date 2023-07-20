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

    /** RELATIONSHIPS
     * 
     */

    // has one hospital inventory
    public function hospital_inventory()
    {
        return $this->hasOne(HospitalInventory::class, 'blood_unit', 'id');
    }

    // belongs to one status
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    // belongs to one blood group
    public function blood_group()
    {
        return $this->belongsTo(BloodGroup::class, 'blood_group', 'id');
    }
}
