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
}
