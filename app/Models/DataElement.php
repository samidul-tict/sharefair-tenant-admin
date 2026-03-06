<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataElement extends Model
{
    // PostgreSQL schema + table name
    protected $table = 'data_element';

    protected $primaryKey = 'id';

    public $timestamps = false; // created_at / updated_at নেই

    protected $fillable = [
        'name',
        'value',
        'category_id',
        'sort_order',
        'is_editable',
        'is_active',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
        'is_active'   => 'boolean',
        'sort_order'  => 'integer',
        'category_id' => 'integer',
    ];

    /**
     * Relationship: DataElement belongs to DataCategory
     */
    public function category()
    {
        return $this->belongsTo(DataCategory::class, 'category_id');
    }
}
