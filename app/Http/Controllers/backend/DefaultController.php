<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\CourtCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DefaultController extends Controller
{
    public function dashboard()
    {
        $logUser = User::leftJoin('user_role_mapping as urm', 'users.id', '=', 'urm.user_id')
            ->where('users.id', Auth::id())
            ->select('users.*', 'urm.role_value as user_role_id', 'urm.tenant_id')
            ->first();

        $tenantId = $logUser->tenant_id ?? null;

        $caseCount = 0;
        $caseTypeLabels = [];
        $caseTypeData = [];
        $caseStatusLabels = [];
        $caseStatusData = [];
        $slaLabels = ['Deadline passed', 'Due in 7 days', 'Due in a month', 'On track'];
        $slaData = [0, 0, 0, 0];

        if ($tenantId) {
            $tenantUserIds = User::whereHas('userRoleMappings', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })->pluck('id');

            $tenantCaseIds = CourtCase::whereHas('caseUsers', function ($q) use ($tenantUserIds) {
                $q->where('role_value', 'LEGAL_RE')->whereIn('user_id', $tenantUserIds);
            })->pluck('id');

            $caseCount = $tenantCaseIds->count();

            if ($caseCount > 0) {
                $typeCounts = CourtCase::whereIn('id', $tenantCaseIds)
                    ->select('case_type_value', DB::raw('count(*) as total'))
                    ->groupBy('case_type_value')
                    ->pluck('total', 'case_type_value');

                $statusCounts = CourtCase::whereIn('id', $tenantCaseIds)
                    ->select('case_status_value', DB::raw('count(*) as total'))
                    ->groupBy('case_status_value')
                    ->pluck('total', 'case_status_value');

                $typeValues = $typeCounts->keys()->filter()->toArray();
                $statusValues = $statusCounts->keys()->filter()->toArray();

                $typeNames = $typeValues
                    ? DB::table('data_element')->whereIn('value', $typeValues)->pluck('name', 'value')->toArray()
                    : [];
                $statusNames = $statusValues
                    ? DB::table('data_element')->whereIn('value', $statusValues)->pluck('name', 'value')->toArray()
                    : [];

                foreach ($typeCounts as $value => $total) {
                    $caseTypeLabels[] = $typeNames[$value] ?? $value;
                    $caseTypeData[] = (int) $total;
                }
                foreach ($statusCounts as $value => $total) {
                    $caseStatusLabels[] = $statusNames[$value] ?? $value;
                    $caseStatusData[] = (int) $total;
                }

                // SLA / deadline: deadline passed, due in 7 days, due in a month, on track
                $now = now();
                $in7Days = $now->copy()->addDays(7);
                $in30Days = $now->copy()->addDays(30);

                $deadlinePassed = CourtCase::whereIn('id', $tenantCaseIds)
                    ->whereNotNull('sla_deadline')
                    ->where('sla_deadline', '<', $now)
                    ->count();
                $dueIn7Days = CourtCase::whereIn('id', $tenantCaseIds)
                    ->whereNotNull('sla_deadline')
                    ->where('sla_deadline', '>=', $now)
                    ->where('sla_deadline', '<=', $in7Days)
                    ->count();
                $dueInMonth = CourtCase::whereIn('id', $tenantCaseIds)
                    ->whereNotNull('sla_deadline')
                    ->where('sla_deadline', '>', $in7Days)
                    ->where('sla_deadline', '<=', $in30Days)
                    ->count();
                $onTrack = CourtCase::whereIn('id', $tenantCaseIds)
                    ->where(function ($q) use ($now, $in30Days) {
                        $q->whereNull('sla_deadline')
                            ->orWhere('sla_deadline', '>', $in30Days);
                    })
                    ->count();

                $slaData = [
                    (int) $deadlinePassed,
                    (int) $dueIn7Days,
                    (int) $dueInMonth,
                    (int) $onTrack,
                ];
            }
        }

        $employeeCount = $tenantId
            ? User::whereHas('userRoleMappings', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('role_value', 'EMP');
            })->count()
            : 0;

        return view('backend.default.dashboard', compact(
            'caseCount',
            'employeeCount',
            'caseTypeLabels',
            'caseTypeData',
            'caseStatusLabels',
            'caseStatusData',
            'slaLabels',
            'slaData'
        ));
    }
}
