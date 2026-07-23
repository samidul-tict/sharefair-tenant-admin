@extends('backend.layout.inner-app')
@section('title', 'Distribution Summary | Share Fair')
@section('proxima')

@php
    $caseTypeName = $case->caseType?->name ?? $case->case_type_value;
    $caseStatusName = $case->caseStatus?->name ?? ($case->case_status_value ?? 'N/A');
@endphp

<div class="case-show-modern cs-distribute-page">
    <div class="cs-container">
        <nav class="cs-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span class="cs-breadcrumb-sep" aria-hidden="true">/</span>
            <a href="{{ route('admin.cases.index') }}">Cases</a>
            <span class="cs-breadcrumb-sep" aria-hidden="true">/</span>
            <a href="{{ route('admin.cases.show', $case->id) }}">{{ $case->case_number }}</a>
            <span class="cs-breadcrumb-sep" aria-hidden="true">/</span>
            <span class="cs-breadcrumb-current">Distribution summary</span>
        </nav>

        @if (session('error'))
            <div class="alert alert-danger cs-flash-alert" role="alert">{{ session('error') }}</div>
        @endif

        <div class="cs-header cs-distribute-page-header">
            <div class="cs-case-title">
                <div class="cs-case-number">{{ $case->case_number }}</div>
                <div class="cs-case-type-badge">{{ $caseTypeName }}</div>
                <div class="cs-case-status-badge">
                    <span class="cs-status-dot" aria-hidden="true"></span>
                    {{ $caseStatusName }}
                </div>
            </div>
            <div class="cs-header-actions">
                <div class="cs-dist-download-wrap">
                    <button type="button" class="cs-btn-secondary cs-dist-download-toggle" id="distDownloadToggle" aria-expanded="false" aria-haspopup="true" aria-controls="distDownloadMenu">
                        <i class="fas fa-download" aria-hidden="true"></i> Download
                        <i class="fas fa-chevron-down cs-dist-download-caret" aria-hidden="true"></i>
                    </button>
                    <div class="cs-dist-download-menu" id="distDownloadMenu" hidden>
                        <a href="{{ route('admin.cases.distribute.download', ['id' => $case->id, 'format' => 'pdf']) }}" class="cs-dist-download-option">
                            <i class="fas fa-file-pdf" aria-hidden="true"></i> PDF
                        </a>
                        <a href="{{ route('admin.cases.distribute.download', ['id' => $case->id, 'format' => 'excel']) }}" class="cs-dist-download-option">
                            <i class="fas fa-file-excel" aria-hidden="true"></i> Excel
                        </a>
                    </div>
                </div>
                <button type="button" class="cs-btn-secondary cs-dist-email-open" data-dist-email-open>
                    <i class="fas fa-envelope" aria-hidden="true"></i> Email
                </button>
                <a href="{{ route('admin.cases.show', $case->id) }}" class="cs-btn-secondary">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to case
                </a>
            </div>
        </div>

        <div class="cs-distribute-page-intro">
            <div class="cs-dist-email-toast" data-dist-email-toast hidden role="status"></div>
            <h1 class="cs-distribute-page-title">Distribution summary</h1>
            <p class="cs-distribute-page-lead">
                @if($canConfirmDistribute ?? false)
                    Review the proposed allocation below. When you are satisfied, acknowledge the review and confirm division.
                    This action cannot be undone.
                @elseif($canAdjustDistribute ?? false)
                    Assets have been distributed and this case is pending approval.
                    You can adjust marital asset assignments between participants if needed.
                @else
                    Review how assets are allocated across participants for this case.
                @endif
            </p>
            @if($showDistributionCaps ?? false)
            <div class="cs-dist-value-caps" aria-label="Distribution value caps">
                <div class="cs-dist-value-cap-item">
                    <span class="cs-dist-value-cap-label">Client value cap</span>
                    <span class="cs-dist-value-cap-value">
                        @if(isset($distributionValueCaps['PL']) && $distributionValueCaps['PL']->distribution_value_cap !== null)
                            ${{ number_format((float) $distributionValueCaps['PL']->distribution_value_cap, 2) }}
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="cs-dist-value-cap-item">
                    <span class="cs-dist-value-cap-label">Spouse value cap</span>
                    <span class="cs-dist-value-cap-value">
                        @if(isset($distributionValueCaps['DEF']) && $distributionValueCaps['DEF']->distribution_value_cap !== null)
                            ${{ number_format((float) $distributionValueCaps['DEF']->distribution_value_cap, 2) }}
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>
            @endif
        </div>

        <div
            id="distributionSummaryApp"
            class="cs-distribute-page-app"
            data-preview-url="{{ route('admin.cases.distribute.preview', $case->id) }}"
            data-distribute-url="{{ route('admin.cases.distribute', $case->id) }}"
            data-adjust-draft-url="{{ route('admin.cases.distribute.adjustDraft', $case->id) }}"
            data-email-url="{{ route('admin.cases.distribute.email', $case->id) }}"
            data-success-url="{{ route('admin.cases.show', ['id' => $case->id, 'distributed' => 1]) }}"
            data-csrf-token="{{ csrf_token() }}"
            data-can-confirm="{{ ($canConfirmDistribute ?? false) ? '1' : '0' }}"
            data-can-adjust="{{ ($canAdjustDistribute ?? false) ? '1' : '0' }}"
            data-show-caps="{{ ($showDistributionCaps ?? false) ? '1' : '0' }}"
            data-cap-pl="{{ isset($distributionValueCaps['PL']) && $distributionValueCaps['PL']->distribution_value_cap !== null ? (float) $distributionValueCaps['PL']->distribution_value_cap : '' }}"
            data-cap-def="{{ isset($distributionValueCaps['DEF']) && $distributionValueCaps['DEF']->distribution_value_cap !== null ? (float) $distributionValueCaps['DEF']->distribution_value_cap : '' }}"
        >
            <div class="cs-distribute-preview" data-dist-loading>
                <p class="cs-distribute-preview-status"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Loading distribution preview…</p>
            </div>
            <div class="cs-distribute-preview cs-distribute-preview-error" data-dist-error hidden role="alert"></div>

            <div data-dist-summary class="cs-distribute-summary-root" hidden>
                <div class="cs-dist-summary-stats-wrap">
                    <div class="cs-dist-summary-stats" data-dist-stats aria-label="Distribution overview"></div>
                </div>

                <div class="cs-dist-summary-tabs" role="tablist" aria-label="Distribution view">
                    <button type="button" class="cs-dist-tab is-active" role="tab" id="dist-tab-allocations" aria-selected="true" aria-controls="dist-panel-allocations" data-dist-tab="allocations" data-dist-tab-label="Allocations">Allocations</button>
                    <button type="button" class="cs-dist-tab cs-dist-tab-warning" role="tab" id="dist-tab-non_marital" aria-selected="false" aria-controls="dist-panel-non_marital" data-dist-tab="non_marital" data-dist-tab-label="Non-marital">Non-marital</button>
                    <button type="button" class="cs-dist-tab cs-dist-tab-danger" role="tab" id="dist-tab-dont_want" aria-selected="false" aria-controls="dist-panel-dont_want" data-dist-tab="dont_want" data-dist-tab-label="Don't Want">Don't Want</button>
                    <button type="button" class="cs-dist-tab cs-dist-tab-success" role="tab" id="dist-tab-donations" aria-selected="false" aria-controls="dist-panel-donations" data-dist-tab="donations" data-dist-tab-label="Donations">Donations</button>
                    <button type="button" class="cs-dist-tab cs-dist-tab-muted" role="tab" id="dist-tab-unresolved" aria-selected="false" aria-controls="dist-panel-unresolved" data-dist-tab="unresolved" data-dist-tab-label="Unresolved">Unresolved</button>
                </div>

                <div class="cs-dist-summary-panels">
                    <div class="cs-dist-panel is-active" role="tabpanel" id="dist-panel-allocations" aria-labelledby="dist-tab-allocations" data-dist-panel="allocations"></div>
                    <div class="cs-dist-panel" role="tabpanel" id="dist-panel-non_marital" aria-labelledby="dist-tab-non_marital" data-dist-panel="non_marital" hidden></div>
                    <div class="cs-dist-panel" role="tabpanel" id="dist-panel-dont_want" aria-labelledby="dist-tab-dont_want" data-dist-panel="dont_want" hidden></div>
                    <div class="cs-dist-panel" role="tabpanel" id="dist-panel-donations" aria-labelledby="dist-tab-donations" data-dist-panel="donations" hidden></div>
                    <div class="cs-dist-panel" role="tabpanel" id="dist-panel-unresolved" aria-labelledby="dist-tab-unresolved" data-dist-panel="unresolved" hidden></div>
                </div>
            </div>

            <div class="cs-dist-adjust-root" data-dist-adjust hidden>
                <div class="cs-dist-adjust-header">
                    <div>
                        <h2 class="cs-dist-adjust-title">Adjust distribution</h2>
                        <p class="cs-dist-adjust-lead">Drag assets between participants or from Available marital assets (in-progress, rejected, and other listed statuses). Use Move to when needed.</p>
                    </div>
                    <div class="cs-dist-adjust-actions">
                        <button type="button" class="cs-btn-secondary" data-dist-adjust-cancel>Cancel</button>
                        <button type="button" class="cs-btn-primary" data-dist-adjust-apply>Save adjustments</button>
                    </div>
                </div>
                <div class="cs-dist-adjust-cap-banner" data-dist-adjust-caps hidden role="status"></div>
                <div class="cs-dist-adjust-search-block">
                    <label for="distributionAdjustSearch" class="cs-dist-adjust-search-label">Find an asset to move</label>
                    <div class="cs-dist-adjust-search">
                        <i class="fas fa-search cs-dist-adjust-search-icon" aria-hidden="true"></i>
                        <input
                            type="search"
                            id="distributionAdjustSearch"
                            class="cs-dist-adjust-search-input"
                            placeholder="Enter an asset name…"
                            autocomplete="off"
                            data-dist-adjust-search
                        >
                        <button type="button" class="cs-dist-adjust-search-clear" data-dist-adjust-search-clear hidden aria-label="Clear asset search">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <p class="cs-dist-adjust-search-status" data-dist-adjust-search-status role="status" hidden></p>
                <div class="cs-dist-adjust-board" data-dist-adjust-board></div>
            </div>

            @if($canConfirmDistribute ?? false)
            <aside class="cs-distribute-page-actions" aria-label="Distribution actions">
                <div class="cs-dist-unresolved-notice" data-dist-unresolved-notice hidden role="alert"></div>
                <label class="cs-distribute-review-check">
                    <input type="checkbox" data-dist-reviewed>
                    <span>I have reviewed the proposed allocations and understand this action cannot be undone.</span>
                </label>
                <div class="cs-distribute-page-action-buttons">
                    <div class="cs-dist-download-wrap cs-dist-download-wrap-inline">
                        <button type="button" class="cs-btn-secondary cs-dist-download-toggle" data-dist-download-toggle aria-expanded="false" aria-haspopup="true">
                            <i class="fas fa-download" aria-hidden="true"></i> Download
                            <i class="fas fa-chevron-down cs-dist-download-caret" aria-hidden="true"></i>
                        </button>
                        <div class="cs-dist-download-menu" data-dist-download-menu hidden>
                            <a href="{{ route('admin.cases.distribute.download', ['id' => $case->id, 'format' => 'pdf']) }}" class="cs-dist-download-option">
                                <i class="fas fa-file-pdf" aria-hidden="true"></i> PDF
                            </a>
                            <a href="{{ route('admin.cases.distribute.download', ['id' => $case->id, 'format' => 'excel']) }}" class="cs-dist-download-option">
                                <i class="fas fa-file-excel" aria-hidden="true"></i> Excel
                            </a>
                        </div>
                    </div>
                    <button type="button" class="cs-btn-secondary cs-dist-email-open" data-dist-email-open>
                        <i class="fas fa-envelope" aria-hidden="true"></i> Email
                    </button>
                    <a href="{{ route('admin.cases.show', $case->id) }}" class="cs-btn-secondary">Cancel</a>
                    <button type="button" class="cs-btn-primary cs-btn-distribute-confirm" data-dist-confirm disabled>Confirm division</button>
                </div>
            </aside>
            @elseif($canAdjustDistribute ?? false)
            <aside class="cs-distribute-page-actions" aria-label="Distribution actions">
                <p class="cs-dist-adjust-hint">This case is pending approval. You can adjust marital asset assignments below.</p>
                <div class="cs-distribute-page-action-buttons">
                    <button type="button" class="cs-btn-secondary" data-dist-adjust-open>
                        <i class="fas fa-random" aria-hidden="true"></i> Adjust distribution
                    </button>
                    <div class="cs-dist-download-wrap cs-dist-download-wrap-inline">
                        <button type="button" class="cs-btn-secondary cs-dist-download-toggle" data-dist-download-toggle aria-expanded="false" aria-haspopup="true">
                            <i class="fas fa-download" aria-hidden="true"></i> Download
                            <i class="fas fa-chevron-down cs-dist-download-caret" aria-hidden="true"></i>
                        </button>
                        <div class="cs-dist-download-menu" data-dist-download-menu hidden>
                            <a href="{{ route('admin.cases.distribute.download', ['id' => $case->id, 'format' => 'pdf']) }}" class="cs-dist-download-option">
                                <i class="fas fa-file-pdf" aria-hidden="true"></i> PDF
                            </a>
                            <a href="{{ route('admin.cases.distribute.download', ['id' => $case->id, 'format' => 'excel']) }}" class="cs-dist-download-option">
                                <i class="fas fa-file-excel" aria-hidden="true"></i> Excel
                            </a>
                        </div>
                    </div>
                    <button type="button" class="cs-btn-secondary cs-dist-email-open" data-dist-email-open>
                        <i class="fas fa-envelope" aria-hidden="true"></i> Email
                    </button>
                    @if($canCloseCase ?? false)
                    <button type="button" class="cs-btn-primary cs-btn-close-case" data-case-close-open>
                        <i class="fas fa-check-circle" aria-hidden="true"></i> Close case
                    </button>
                    @endif
                    <a href="{{ route('admin.cases.show', $case->id) }}" class="cs-btn-secondary">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to case
                    </a>
                </div>
            </aside>
            @else
            <aside class="cs-distribute-page-actions cs-distribute-page-actions-readonly" aria-label="Distribution actions">
                <div class="cs-distribute-page-action-buttons">
                    <div class="cs-dist-download-wrap cs-dist-download-wrap-inline">
                        <button type="button" class="cs-btn-secondary cs-dist-download-toggle" data-dist-download-toggle aria-expanded="false" aria-haspopup="true">
                            <i class="fas fa-download" aria-hidden="true"></i> Download
                            <i class="fas fa-chevron-down cs-dist-download-caret" aria-hidden="true"></i>
                        </button>
                        <div class="cs-dist-download-menu" data-dist-download-menu hidden>
                            <a href="{{ route('admin.cases.distribute.download', ['id' => $case->id, 'format' => 'pdf']) }}" class="cs-dist-download-option">
                                <i class="fas fa-file-pdf" aria-hidden="true"></i> PDF
                            </a>
                            <a href="{{ route('admin.cases.distribute.download', ['id' => $case->id, 'format' => 'excel']) }}" class="cs-dist-download-option">
                                <i class="fas fa-file-excel" aria-hidden="true"></i> Excel
                            </a>
                        </div>
                    </div>
                    <button type="button" class="cs-btn-secondary cs-dist-email-open" data-dist-email-open>
                        <i class="fas fa-envelope" aria-hidden="true"></i> Email
                    </button>
                    @if($canCloseCase ?? false)
                    <button type="button" class="cs-btn-primary cs-btn-close-case" data-case-close-open>
                        <i class="fas fa-check-circle" aria-hidden="true"></i> Close case
                    </button>
                    @endif
                    <a href="{{ route('admin.cases.show', $case->id) }}" class="cs-btn-secondary">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to case
                    </a>
                </div>
            </aside>
            @endif
        </div>
    </div>
</div>

@include('backend.cases.partials.close-case-modal')

@if($canConfirmDistribute ?? false)
<div class="cs-dist-email-modal cs-dist-unresolved-modal" data-dist-unresolved-modal hidden>
    <div class="cs-dist-email-backdrop" data-dist-unresolved-cancel></div>
    <div class="cs-dist-email-dialog cs-dist-unresolved-dialog" role="dialog" aria-modal="true" aria-labelledby="distUnresolvedTitle" aria-describedby="distUnresolvedDescription">
        <div class="cs-dist-email-header">
            <div class="cs-case-close-heading">
                <span class="cs-case-close-icon cs-dist-unresolved-icon" aria-hidden="true">
                    <i class="fas fa-hourglass-half"></i>
                </span>
                <div>
                    <h2 id="distUnresolvedTitle">Unresolved assets remain</h2>
                    <p>Confirm before proceeding with distribution.</p>
                </div>
            </div>
            <button type="button" class="cs-dist-email-close" data-dist-unresolved-cancel aria-label="Close dialog">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="cs-case-close-body" id="distUnresolvedDescription">
            <p data-dist-unresolved-modal-lead></p>
            <p>After distribution is confirmed, unresolved assets can no longer be addressed through this workflow. You may review them on the <strong>Unresolved</strong> tab before continuing.</p>
        </div>
        <div class="cs-dist-email-actions">
            <button type="button" class="cs-btn-secondary" data-dist-unresolved-cancel>Review unresolved</button>
            <button type="button" class="cs-btn-primary" data-dist-unresolved-proceed>
                <i class="fas fa-check" aria-hidden="true"></i>
                Confirm division anyway
            </button>
        </div>
    </div>
</div>
@endif

<div class="cs-dist-email-modal" data-dist-email-modal hidden>
    <div class="cs-dist-email-backdrop" data-dist-email-close></div>
    <div class="cs-dist-email-dialog" role="dialog" aria-modal="true" aria-labelledby="distEmailTitle">
        <div class="cs-dist-email-header">
            <div>
                <h2 id="distEmailTitle">Email distribution summary</h2>
                <p>Select case users who should receive the generated PDF summary.</p>
            </div>
            <button type="button" class="cs-dist-email-close" data-dist-email-close aria-label="Close email dialog">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <div class="cs-dist-email-status" data-dist-email-status hidden role="alert"></div>

        @if(($emailRecipients ?? collect())->isNotEmpty())
            <div class="cs-dist-email-users">
                @foreach($emailRecipients as $recipient)
                    <label class="cs-dist-email-user">
                        <input type="checkbox" value="{{ $recipient->id }}" data-dist-email-recipient>
                        <span class="cs-dist-email-user-main">
                            <span class="cs-dist-email-user-name">{{ $recipient->name ?: $recipient->email }}</span>
                            <span class="cs-dist-email-user-email">{{ $recipient->email }}</span>
                        </span>
                        @if(!empty($recipient->role_name))
                            <span class="cs-dist-email-user-role">{{ $recipient->role_name }}</span>
                        @endif
                    </label>
                @endforeach
            </div>
        @else
            <div class="cs-dist-email-empty">
                No case users with email addresses are available.
            </div>
        @endif

        <div class="cs-dist-email-actions">
            <button type="button" class="cs-btn-secondary" data-dist-email-close>Cancel</button>
            <button type="button" class="cs-btn-primary" data-dist-email-send @if(($emailRecipients ?? collect())->isEmpty()) disabled @endif>
                Send PDF
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('backend-assets/js/distribution-summary.js') }}"></script>
<script src="{{ asset('backend-assets/js/case-close-modal.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof initDistributionSummary === 'function') {
        initDistributionSummary(document.getElementById('distributionSummaryApp'));
    }
    if (typeof initCaseCloseModal === 'function') initCaseCloseModal();
});
</script>
@endpush
@endsection
