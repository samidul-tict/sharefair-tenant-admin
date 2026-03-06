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
            <div class="col-xl-4 col-lg-6 col-md-12">
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
            <div class="col-xl-4 col-lg-6 col-md-12">
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
            <div class="col-xl-4 col-lg-6 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Cases by Type</h4>
                    </div>
                    <div class="card-body">
                        @if(!empty($caseTypeData))
                            <div id="dashboard-chart-case-type" class="dashboard-pie-chart" role="img" aria-label="Pie chart of cases by type"></div>
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
    var caseTypeLabels = @json($caseTypeLabels ?? []);
    var caseTypeData = @json($caseTypeData ?? []);
    var caseStatusLabels = @json($caseStatusLabels ?? []);
    var caseStatusData = @json($caseStatusData ?? []);
    var slaLabels = @json($slaLabels ?? []);
    var slaData = @json($slaData ?? []);

    if (typeof ApexCharts !== 'undefined' && caseTypeData.length) {
        new ApexCharts(document.querySelector('#dashboard-chart-case-type'), {
            chart: { type: 'pie', height: 320 },
            series: caseTypeData,
            labels: caseTypeLabels,
            legend: { position: 'bottom', horizontalAlign: 'center' },
            colors: ['#0A2540', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#EC4899'],
            dataLabels: { enabled: true }
        }).render();
    }

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