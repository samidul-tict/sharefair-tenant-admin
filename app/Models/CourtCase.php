<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\DataElement;
use App\Models\User;

class CourtCase extends Model
{
    use HasFactory;

    protected $table = 'cases';  // schema + table

    protected $fillable = [
        'case_number',
        'case_type_value',
        'case_status_value',
        'case_description',
        'court_name',
        'is_legal_hold',
        'legal_hold_reason',
        'legal_hold_start_date',
        'legal_hold_end_date',
        'total_items_count',
        'total_items_value',
        'total_marital_assets_count',
        'total_marital_assets_value',
        'total_non_marital_assets_count',
        'total_dont_want_items_count',
        'total_dont_want_items_value',
        'total_users',
        'target_value_per_user',
        'distribution_date',
        'distributed_by',
        'is_active',
        'created_by',
        'created_date',
        'modified_by',
        'last_modified_date',
        'sla_deadline',
        'asset_sla_in_days',
        'max_number_of_arbitation_per_user',
        'distribution_sla_in_days',
        'max_number_of_distribution_attempts',
        'distribution_method_value',
        'distribute_by_client',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_legal_hold' => 'boolean',
        'distribute_by_client' => 'boolean',
        'legal_hold_start_date' => 'date',
        'legal_hold_end_date' => 'date',
        'distribution_date' => 'datetime',
        'sla_deadline' => 'datetime',
        'created_date' => 'datetime',
        'last_modified_date' => 'datetime',
    ];
    public $timestamps = false; // Manual timestamp fields

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function distributedBy()
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }

    public function caseType()
    {
        return $this->belongsTo(DataElement::class, 'case_type_value', 'value');
    }

    public function caseStatus()
    {
        return $this->belongsTo(DataElement::class, 'case_status_value', 'value');
    }

    public function distributionMethod()
    {
        return $this->belongsTo(DataElement::class, 'distribution_method_value', 'value');
    }

    public function caseUsers()
    {
        return $this->hasMany(CaseUserMapping::class, 'case_id');
    }

    /**
     * Case statuses that have a distribution summary (preview or completed).
     */
    public const DISTRIBUTION_SUMMARY_STATUSES = [
        'PEND_DIS',
        'PEND_APP',
        'PEND_CLS',
        'RES_COMP',
    ];

    /** Statuses at or after pending distribution (PEND_DIS). */
    public const STATUSES_AT_OR_AFTER_PEND_DIS = [
        'PEND_DIS',
        'PEND_APP',
        'PEND_CLS',
        'RES_COMP',
    ];

    /** Statuses at or after pending approval (PEND_APP). */
    public const STATUSES_AT_OR_AFTER_PEND_APP = [
        'PEND_APP',
        'PEND_CLS',
        'RES_COMP',
    ];

    /**
     * Whether the case has reached pending distribution or a later stage.
     */
    public function isAtOrPastPendingDistribution(): bool
    {
        return in_array($this->case_status_value, self::STATUSES_AT_OR_AFTER_PEND_DIS, true);
    }

    /**
     * Whether the case has reached pending approval or a later stage.
     */
    public function isAtOrPastPendingApproval(): bool
    {
        return in_array($this->case_status_value, self::STATUSES_AT_OR_AFTER_PEND_APP, true);
    }

    /**
     * Whether the case is closed (resolved complete).
     */
    public function isResolvedCompleted(): bool
    {
        return $this->case_status_value === 'RES_COMP';
    }

    /**
     * Whether the case may still be deleted from the admin UI.
     * Resolved/closed cases keep historical distribution data and must not be removed.
     */
    public function canBeDeleted(): bool
    {
        return !$this->isResolvedCompleted()
            && $this->case_status_value !== 'PEND_CLS';
    }

    /**
     * Field-lock flags for the case edit form and update handler.
     *
     * @return array{
     *     identity: bool,
     *     distribution_config: bool,
     *     distribution_attempts: bool,
     *     legal_hold_only: bool,
     * }
     */
    public function caseEditLockFlags(): array
    {
        return [
            'identity' => true,
            'distribution_config' => $this->isAtOrPastPendingDistribution(),
            'distribution_attempts' => $this->isAtOrPastPendingApproval(),
            'legal_hold_only' => $this->isResolvedCompleted(),
        ];
    }

    /**
     * Whether the distribution summary page should be available for this case.
     */
    public function hasDistributionSummary(): bool
    {
        return in_array($this->case_status_value, self::DISTRIBUTION_SUMMARY_STATUSES, true);
    }

    /**
     * Legal representative may run distribution when status is pending distribution
     * and assets are not distributed by the client.
     */
    public function canLegalRepresentativeDistribute(): bool
    {
        return $this->case_status_value === 'PEND_DIS'
            && !($this->distribute_by_client === true);
    }

    /**
     * Legal representative may adjust marital allocations while pending approval.
     */
    public function canLegalRepresentativeAdjustDistribution(): bool
    {
        return $this->case_status_value === 'PEND_APP'
            && !($this->distribute_by_client === true);
    }

    /**
     * Whether this case awaits attorney-driven distribution (PEND_DIS, not client-distributed).
     */
    public function needsAttorneyDistribution(): bool
    {
        return $this->canLegalRepresentativeDistribute();
    }

    /**
     * Cases where the legal representative must run distribution.
     */
    public function scopeNeedsAttorneyDistribution($query)
    {
        return $query
            ->where('case_status_value', 'PEND_DIS')
            ->where(function ($q) {
                $q->where('distribute_by_client', false)->orWhereNull('distribute_by_client');
            });
    }

    /**
     * Restrict to cases the given admin user may access (creator or mapped participant).
     */
    public function scopeAccessibleTo($query, ?User $user)
    {
        if ($user && $user->user_role_id === 'EMP') {
            return $query->whereIn('id', function ($subQuery) use ($user) {
                $subQuery->select('case_id')
                    ->from('case_user_mapping')
                    ->where('user_id', $user->id);
            });
        }

        $userId = $user?->id ?? auth()->id();

        return $query->where(function ($q) use ($userId) {
            $q->where('created_by', $userId)
                ->orWhereIn('id', function ($sub) use ($userId) {
                    $sub->select('case_id')
                        ->from('case_user_mapping')
                        ->where('user_id', $userId);
                });
        });
    }
}
