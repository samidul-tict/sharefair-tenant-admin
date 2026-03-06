<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users'; // schema-qualified
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'preferred_language',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
    ];

    protected $hidden = ['password', 'remember_token'];

    public $timestamps = false; // Using custom date fields instead of created_at/updated_at

    // 🔹 Relationships

    // A user can have multiple roles (many-to-many through user_role_mapping)
    public function userRoleMappings()
    {
        return $this->hasMany(UserRoleMapping::class, 'user_id', 'id');
    }

    // Convenient access: all roles of this user
    public function roles()
    {
        return $this->belongsToMany(DataElement::class, 'core.user_role_mapping', 'user_id', 'role_value', 'id', 'value')
            ->withPivot(['tenant_id', 'is_active'])
            ->wherePivot('is_active', true);
    }

    // Get the tenant via mapping (one user can belong to one tenant)
    public function tenant()
    {
        return $this->hasOneThrough(
            Tenant::class,
            UserRoleMapping::class,
            'user_id',     // Foreign key on user_role_mapping
            'id',          // Foreign key on tenants
            'id',          // Local key on users
            'tenant_id'    // Local key on user_role_mapping
        );
    }

    // User who created this record
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    // Subscriptions (user indirectly has many subscriptions via tenant)
    public function subscriptions()
    {
        return $this->hasManyThrough(
            Subscription::class,
            Tenant::class,
            'id',         // Local key on tenants
            'tenant_id',  // Foreign key on subscriptions
            'id',         // Local key on users
            'id'          // Foreign key on tenants
        );
    }
}
