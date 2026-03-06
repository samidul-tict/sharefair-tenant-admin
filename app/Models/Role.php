<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'data_element';
    
    // Add logic to scope to roles? optional but commonly roles are category 1 or 2.
    
    protected $fillable = [
        'name',
        'value',
        'category_id',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
    ];

    public $timestamps = false;

    public function userRoleMappings()
    {
        // UserRoleMapping links via role_value -> data_element.value
        return $this->hasMany(UserRoleMapping::class, 'role_value', 'value');
    }
}
