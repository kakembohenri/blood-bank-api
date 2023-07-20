<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hospital extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'FacilityCode',
        'District',
        'location',
        'created_at',
        'created_by',
        'modified_at',
        'modified_by'
    ];

    /**
     * MODEL RELATIONSHIPS
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
