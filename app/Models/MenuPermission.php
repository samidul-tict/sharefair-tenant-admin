<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuPermission extends Model
{
    public $timestamps = false;
    protected $table = 'menu_permission'; // Schema + Table

    protected $fillable = [
        'user_id',
        'menu_id',
        'can_view',
        'can_create',
        'can_edit',
        'can_delete',
        'can_upload',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date'
    ];

    protected $casts = [
        'can_view'   => 'boolean',
        'can_create' => 'boolean',
        'can_edit'   => 'boolean',
        'can_delete' => 'boolean',
        'can_upload' => 'boolean',
        'is_active'  => 'boolean',
    ];

    // Relationship: Menu
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    // Relationship: User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
