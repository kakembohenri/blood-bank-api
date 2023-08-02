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
        'bloodGroup_id',
        'bloodComponent_id',
        'quantity',
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

    // belongs to blood component
    public function BloodComponent()
    {
        return $this->belongsTo(BloodProduct::class, 'bloodComponent_id', 'id');
    }

    // belongs to blood group
    public function BloodGroup()
    {
        return $this->belongsTo(BloodGroup::class, 'bloodGroup_id', 'id');
    }
}
