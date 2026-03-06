<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $fillable = [
        'name',
        'iso_alpha_2_code',
        'intl_dial_code',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
    ];

    public $timestamps = false;

    /**
     * Creator relationship
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Modifier relationship
     */
    public function modifiedByUser()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
