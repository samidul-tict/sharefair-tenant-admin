<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'plans';
    protected $fillable = [
        'name',
        'description',
        'policy',
        'base_price',
        'selling_price',
        'duration_in_days',
        'file',
        'is_active',
        'soft_delete',
        'created_date',
        'last_modification_date',
    ];

    public $timestamps = false;

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id', 'id');
    }
}
