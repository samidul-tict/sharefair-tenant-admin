<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    protected $table = 'items'; // schema + table name

    protected $primaryKey = 'id';

    public $timestamps = false; // because custom timestamp columns exist

    protected $fillable = [
        'case_id',
        'name',
        'quantity',
        'description',
        'location_id',
        'category',
        'other_category',
        'condition',
        'notes',
        'tags',
        'images',
        'links',
        'brand',
        'other_brand',
        'model',
        'serial_number',
        'purchase_year',
        'purchase_price',
        'estimated_value',
        'concluded_price',
        'accessories_status_value',
        'has_original_packaging',
        'has_valid_warranty',
        'is_marital_asset',
        'assigned_to_user_id',
        'assigned_reason',
        'status',
        'sla_deadline',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
    ];

    protected $casts = [
        'purchase_year' => 'integer',
        'is_marital_asset' => 'boolean',
        'has_original_packaging' => 'boolean',
        'has_valid_warranty' => 'boolean',
        'is_active' => 'boolean',
        'purchase_price' => 'decimal:2',
        'estimated_value' => 'decimal:2',
        'concluded_price' => 'decimal:2',
        'sla_deadline' => 'date',
        'created_date' => 'datetime',
        'last_modified_date' => 'datetime',
    ];

    /* ------------------ Relationships ------------------ */

    // Each item belongs to a case
    public function case()
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    // Created by user
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Modified by user
    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function location()
    {
        return $this->belongsTo(AssociatedLocation::class, 'location_id');
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
