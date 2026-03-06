<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu'; // Schema + Table name

    protected $primaryKey = 'id';
    public $timestamps = false; // because created_at & updated_at are not automatic

    protected $fillable = [
        'menu_name',
        'route_name',
        'icon',
        'parent_id',
        'sort_order',
        'is_active',
        'created_at',
        'updated_at',
        'admin_type'
    ];

    /**
     * Parent Menu Relation
     */
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Child Menu Relation
     */
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('sort_order', 'ASC');
    }
}
