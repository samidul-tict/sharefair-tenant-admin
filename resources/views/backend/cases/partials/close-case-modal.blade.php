@if($canCloseCase ?? false)
<div class="case-show-modern cs-dist-email-modal cs-case-close-modal" data-case-close-modal hidden>
    <div class="cs-dist-email-backdrop" data-case-close-cancel></div>
    <div class="cs-dist-email-dialog cs-case-close-dialog" role="dialog" aria-modal="true" aria-labelledby="caseCloseTitle" aria-describedby="caseCloseDescription">
        <div class="cs-dist-email-header">
            <div class="cs-case-close-heading">
                <span class="cs-case-close-icon" aria-hidden="true">
                    <i class="fas fa-check-circle"></i>
                </span>
                <div>
                    <h2 id="caseCloseTitle">Close this case?</h2>
                    <p>Confirm that this legal matter has been completed.</p>
                </div>
            </div>
            <button type="button" class="cs-dist-email-close" data-case-close-cancel aria-label="Close dialog">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="cs-case-close-body" id="caseCloseDescription">
            <p>The case status will change from <strong>Pending approval</strong> to <strong>Resolved and completed</strong>.</p>
            <p>You will return to the case details page after it is closed.</p>
        </div>
        <form method="POST" action="{{ route('admin.cases.close', $case->id) }}" data-case-close-form>
            @csrf
            <div class="cs-dist-email-actions">
                <button type="button" class="cs-btn-secondary" data-case-close-cancel>Cancel</button>
                <button type="submit" class="cs-btn-primary cs-case-close-confirm" data-case-close-submit>
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span>Close case</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif
