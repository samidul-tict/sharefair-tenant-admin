<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    use HasFactory;

    // 🔹 Table name with schema
    protected $table = 'themes';
    protected $primaryKey = 'id';
    public $timestamps = false; // you’re manually handling created/modified timestamps

    // 🔹 Fillable fields
    protected $fillable = [
        'name',
        'primary_color',
        'secondary_color',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
    ];

    // 🔹 Casts for proper data types
    protected $casts = [
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'last_modified_date' => 'datetime',
    ];

    // 🔹 Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by', 'id');
    }

    // Optional: reverse relation for tenants using this theme
    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'theme_id', 'id');
    }
}