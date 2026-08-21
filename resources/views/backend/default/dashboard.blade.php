@extends('backend.layout.inner-app')
@section('title', 'Dashboard | Share Fair')
@section('proxima')

<div class="dashboard-modern">
    <div class="page-header">
        <div class="header-content">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <span class="breadcrumb-current">Dashboard</span>
            </nav>
        </div>
    </div>
    <section class="section">
    <div class="section-body">
        <div class="row ">
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="card">
                    <div class="card-statistic-4">
                        <div class="align-items-center justify-content-between">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                    <div class="card-content">
                                        <h5 class="font-15">Total Cases</h5>
                                        <h2 class="mb-3 font-18">{{ $caseCount }}</h2>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                    <div class="dashboard-tile-icon dashboard-tile-icon-cases" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="card">
                    <div class="card-statistic-4">
                        <div class="align-items-center justify-content-between">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pr-0 pt-3">
                                    <div class="card-content">
                                        <h5 class="font-15">Total Employees</h5>
                                        <h2 class="mb-3 font-18">{{ $employeeCount }}</h2>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pl-0">
                                    <div class="dashboard-tile-icon dashboard-tile-icon-employees" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card dashboard-action-required-card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h4 class="card-title mb-0">Action required</h4>
                        @if(($attorneyDistributionCases ?? collect())->isNotEmpty())
                            <span class="cs-attention-badge">{{ $attorneyDistributionCases->count() }} case{{ $attorneyDistributionCases->count() === 1 ? '' : 's' }}</span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @if(($attorneyDistributionCases ?? collect())->isEmpty())
                            <p class="text-muted mb-0 px-4 py-4">No cases currently require distribution.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table dashboard-action-required-table mb-0">
                                    <caption class="sr-only">Cases ready for attorney distribution</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Case number</th>
                                            <th scope="col">Status</th>
                                            <th scope="col" class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($attorneyDistributionCases as $actionCase)
                                            <tr class="cases-row-action-required">
                                                <td>
                                                    <a href="{{ route('admin.cases.show', $actionCase->id) }}" class="case-number">{{ $actionCase->case_number }}</a>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-pending">
                                                        <span class="status-dot" aria-hidden="true"></span>
                                                        {{ $actionCase->caseStatus?->name ?? $actionCase->case_status_value }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('admin.cases.distribute.review', $actionCase->id) }}" class="cs-btn-primary cs-btn-sm cs-btn-distribute-inline">
                                                        <i class="fas fa-balance-scale" aria-hidden="true"></i> Distribute assets
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-xl-6 col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Cases by Status</h4>
                    </div>
                    <div class="card-body">
                        @if(!empty($caseStatusData))
                            <div id="dashboard-chart-case-status" class="dashboard-pie-chart" role="img" aria-label="Pie chart of cases by status"></div>
                        @else
                            <p class="text-muted mb-0 text-center py-4">No case data to display.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Case SLA / Deadline</h4>
                    </div>
                    <div class="card-body">
                        @if(array_sum($slaData ?? []) > 0)
                            <div id="dashboard-chart-sla" class="dashboard-pie-chart" role="img" aria-label="Pie chart of cases by SLA deadline"></div>
                        @else
                            <p class="text-muted mb-0 text-center py-4">No case data to display.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    </section>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var caseStatusLabels = @json($caseStatusLabels ?? []);
    var caseStatusData = @json($caseStatusData ?? []);
    var slaLabels = @json($slaLabels ?? []);
    var slaData = @json($slaData ?? []);

    if (typeof ApexCharts !== 'undefined' && caseStatusData.length) {
        new ApexCharts(document.querySelector('#dashboard-chart-case-status'), {
            chart: { type: 'pie', height: 320 },
            series: caseStatusData,
            labels: caseStatusLabels,
            legend: { position: 'bottom', horizontalAlign: 'center' },
            colors: ['#10B981', '#F59E0B', '#EF4444', '#6B7280', '#3B82F6', '#8B5CF6', '#EC4899'],
            dataLabels: { enabled: true }
        }).render();
    }

    if (typeof ApexCharts !== 'undefined' && slaData.length && slaData.some(function(v) { return v > 0; })) {
        new ApexCharts(document.querySelector('#dashboard-chart-sla'), {
            chart: { type: 'pie', height: 320 },
            series: slaData,
            labels: slaLabels,
            legend: { position: 'bottom', horizontalAlign: 'center' },
            colors: ['#EF4444', '#F59E0B', '#3B82F6', '#10B981'],
            dataLabels: { enabled: true }
        }).render();
    }
});
</script>
@endpush