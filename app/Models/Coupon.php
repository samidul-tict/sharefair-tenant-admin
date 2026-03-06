<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $table = 'coupons';
    protected $fillable = [
        'code',
        'discount_type',
        'discount',
        'user_use_limit',
        'start_date',
        'end_date',
        'policy',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
    ];

    public $timestamps = false;

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'coupon_id', 'id');
    }
}
