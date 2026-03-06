<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataCategory extends Model
{
    // PostgreSQL schema + table
    protected $table = 'data_category';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relationship:
     * data_category.id = data_element.category_id
     */
    public function dataElements()
    {
        return $this->hasMany(DataElement::class, 'category_id', 'id');
    }
}
