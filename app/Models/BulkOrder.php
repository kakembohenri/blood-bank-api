<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkOrder extends Model
{
    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
        'hospital_id',
        'status_id',
        'health_stamp',
        'orderType',
        'approved_by',
        'created_at',
        'created_by',
        'modified_at',
        'modified_by'
    ];

    // Bulk order has many bulk order items
    public function bulkOrderItems()
    {
        return $this->HasMany(BulkOrderItems::class);
    }

    // Belongs to status
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    // Belongs to user
    public function user()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    // Belongs to bulk order type
    public function OrderTypes()
    {
        return $this->belongsTo(OrderTypes::class, 'orderType', 'id');
    }

    // Belongs to hospital
    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }
}
