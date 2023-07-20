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

    /** RELATIONSHIPS
     * 
     */

    // belongs to group unit
    public function blood_unit()
    {
        return $this->belongsTo(BloodUnit::class);
    }
}
