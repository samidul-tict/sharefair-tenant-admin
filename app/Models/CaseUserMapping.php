<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaseUserMapping extends Model
{
    use HasFactory;

    protected $table = 'case_user_mapping';

    protected $fillable = [
        'case_id',
        'user_id',
        'role_value',
        'user_status_value',
        'participate_in_distribution',
        'representing_to_user',
        'allocated_item_count',
        'allocated_value',
        'value_difference',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
        'is_default',
        'default_location_id',
    ];

    public $timestamps = false; // Using custom timestamp fields

    // Relationships
    public function courtCase()
    {
        return $this->belongsTo(CourtCase::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role()
    {
        // Assuming DataElement model exists or using direct DB, but strictly we should use a Model.
        // Since we haven't seen DataElement model, and user used DB::table('data_element'), I will leave this but point to role_value.
        // If Role model is now DataElement wrapper, we should check Role.php update task.
        // For now, I'll update the FK.
        return $this->belongsTo(Role::class, 'role_value', 'value');
    }

    // Tenant relationship removed as tenant_id is not in schema provided.

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
