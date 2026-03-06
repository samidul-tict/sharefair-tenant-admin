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
        'category',
        'sub_category',
        'condition',
        'location',
        'is_marital_asset',
        'notes',
        'tags',
        'images',
        'links',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
        'purchase_price',
        'sale_date',
        'sale_price',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'sale_date'     => 'date',
        'is_marital_asset' => 'boolean',
        'is_active' => 'boolean',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
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
}
