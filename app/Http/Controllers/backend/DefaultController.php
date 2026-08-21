<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\CourtCase;
use App\Models\User;
use App\Support\AdminContext;
use Illuminate\Support\Facades\DB;

class DefaultController extends Controller
{
    public function dashboard()
    {
        $logUser = AdminContext::logUser();
        $tenantId = $logUser->tenant_id ?? null;

        $caseCount = 0;
        $caseStatusLabels = [];
        $caseStatusData = [];
        $slaLabels = ['Deadline passed', 'Due in 7 days', 'Due in a month', 'On track'];
        $slaData = [0, 0, 0, 0];

        if ($tenantId) {
            $tenantCaseIds = CourtCase::query()
                ->join('case_user_mapping as cum', 'cases.id', '=', 'cum.case_id')
                ->join('user_role_mapping as urm', function ($join) use ($tenantId) {
                    $join->on('cum.user_id', '=', 'urm.user_id')
                        ->where('urm.tenant_id', $tenantId);
                })
                ->where('cum.role_value', 'LEGAL_RE')
                ->distinct()
                ->pluck('cases.id');

            $caseCount = $tenantCaseIds->count();

            if ($caseCount > 0) {
                $statusCounts = CourtCase::whereIn('id', $tenantCaseIds)
                    ->select('case_status_value', DB::raw('count(*) as total'))
                    ->groupBy('case_status_value')
                    ->pluck('total', 'case_status_value');

                $statusValues = $statusCounts->keys()->filter()->toArray();

                $statusNames = $statusValues
                    ? DB::table('data_element')->whereIn('value', $statusValues)->pluck('name', 'value')->toArray()
                    : [];

                foreach ($statusCounts as $value => $total) {
                    $caseStatusLabels[] = $statusNames[$value] ?? $value;
                    $caseStatusData[] = (int) $total;
                }

                $now = now();
                $in7Days = $now->copy()->addDays(7);
                $in30Days = $now->copy()->addDays(30);

                $slaRow = CourtCase::whereIn('id', $tenantCaseIds)
                    ->selectRaw('
                        SUM(CASE WHEN sla_deadline IS NOT NULL AND sla_deadline < ? THEN 1 ELSE 0 END) AS deadline_passed,
                        SUM(CASE WHEN sla_deadline IS NOT NULL AND sla_deadline >= ? AND sla_deadline <= ? THEN 1 ELSE 0 END) AS due_in_7_days,
                        SUM(CASE WHEN sla_deadline IS NOT NULL AND sla_deadline > ? AND sla_deadline <= ? THEN 1 ELSE 0 END) AS due_in_month,
                        SUM(CASE WHEN sla_deadline IS NULL OR sla_deadline > ? THEN 1 ELSE 0 END) AS on_track
                    ', [$now, $now, $in7Days, $in7Days, $in30Days, $in30Days])
                    ->first();

                $slaData = [
                    (int) ($slaRow->deadline_passed ?? 0),
                    (int) ($slaRow->due_in_7_days ?? 0),
                    (int) ($slaRow->due_in_month ?? 0),
                    (int) ($slaRow->on_track ?? 0),
                ];
            }
        }

        $employeeCount = $tenantId
            ? User::whereHas('userRoleMappings', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('role_value', 'EMP');
            })->count()
            : 0;

        $attorneyDistributionCases = $logUser
            ? CourtCase::query()
                ->with(['caseStatus'])
                ->where('is_active', true)
                ->accessibleTo($logUser)
                ->needsAttorneyDistribution()
                ->orderByDesc('last_modified_date')
                ->limit(10)
                ->get()
            : collect();

        return view('backend.default.dashboard', compact(
            'caseCount',
            'employeeCount',
            'caseStatusLabels',
            'caseStatusData',
            'slaLabels',
            'slaData',
            'attorneyDistributionCases'
        ));
    }
}
