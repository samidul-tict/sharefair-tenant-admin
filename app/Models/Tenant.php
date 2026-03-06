<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $table = 'tenants'; // PostgreSQL schema.table
    protected $primaryKey = 'id';
    public $timestamps = false; // Since you’re manually managing created/modified dates

    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'ein',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip_code',
        'country',
        'company_logo_url',
        'domain_url_alias',
        'schema_name',
        'theme_id',
        'is_profile_completed',
        'is_subscribed',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
    ];

    // 🔹 Relationships
    public function stateRef()
    {
        return $this->belongsTo(State::class, 'state', 'id');
    }

    public function countryRef()
    {
        return $this->belongsTo(Country::class, 'country', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by', 'id');
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class, 'theme_id', 'id');
    }
}
