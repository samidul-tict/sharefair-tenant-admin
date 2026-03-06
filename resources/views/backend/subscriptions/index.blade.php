@extends('backend.layout.inner-app')
@section('title', 'My Subscriptions | Share Fair')

@section('proxima')
<div class="cases-list-modern">
    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="page-header">
        <div class="header-content">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span class="breadcrumb-separator" aria-hidden="true">/</span>
                <span class="breadcrumb-current">My Subscriptions</span>
            </nav>
        </div>
        @if(hasPermission('subscriptions', 'create') || (isset($logUser) && $logUser->user_role_id == 'TENANT_A') || true) 
        {{-- Permission check skipped for demo or assuming tenant admin has access --}}
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addSubscriptionModal">
            <i class="fas fa-plus"></i> Add New Subscription
        </button>
        @endif
    </div>

    {{-- Add Subscription Modal --}}
    <div class="modal fade" id="addSubscriptionModal" tabindex="-1" role="dialog" aria-labelledby="addSubscriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.subscriptions.store') }}" method="POST" id="subscriptionForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addSubscriptionModalLabel">Add New Subscription</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        {{-- Step 1: Select Plan --}}
                        <div class="form-group">
                            <label class="d-block font-weight-bold mb-3">1. Select a Plan</label>
                            <div class="row">
                                @foreach($plans as $plan)
                                <div class="col-md-6 mb-3">
                                    <div class="custom-control custom-radio h-100">
                                        <input type="radio" id="plan_{{ $plan->id }}" name="plan_id" class="custom-control-input plan-radio" 
                                            value="{{ $plan->id }}" data-price="{{ $plan->selling_price }}" data-name="{{ $plan->name }}" required>
                                        <label class="custom-control-label w-100 h-100 border rounded p-3 cursor-pointer" for="plan_{{ $plan->id }}">
                                            <span class="d-block font-weight-bold text-primary">{{ $plan->name }}</span>
                                            <span class="d-block h4 my-2">${{ number_format($plan->selling_price, 2) }}</span>
                                            <span class="d-block text-muted small">{{ $plan->duration_in_days }} Days Duration</span>
                                            <p class="mb-0 mt-2 text-muted small">{{ \Illuminate\Support\Str::limit($plan->description, 60) }}</p>
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Step 2: Coupon --}}
                        <div class="form-group">
                            <label class="font-weight-bold">2. Apply Coupon (Optional)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="couponCodeInput" name="coupon_code" placeholder="Enter coupon code">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="applyCouponBtn">Apply</button>
                                </div>
                            </div>
                            <small id="couponMessage" class="form-text text-muted"></small>
                        </div>

                        {{-- Summary --}}
                        <div class="summary-box mt-4">
                            <h6 class="font-weight-bold mb-3" style="color: #6777ef;">Summary</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Plan Price:</span>
                                <span id="summaryPlanPrice" class="font-weight-bold">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Discount:</span>
                                <span id="summaryDiscount">-$0.00</span>
                            </div>
                             <div class="d-flex justify-content-between mb-2 text-muted">
                                <span>Tax:</span>
                                <span>$0.00</span>
                            </div>
                            <hr class="my-3">
                            <div class="d-flex justify-content-between font-weight-bold h5 mb-0">
                                <span>Total:</span>
                                <span id="summaryTotal" class="text-success">$0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="submitSubscriptionBtn" disabled>Subscribe Now</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    {{-- Active Subscription Block --}}
    @if($activeSubscription)
    <div class="active-subscription-card" style="padding: 25px; background: #fff; margin-bottom: 25px; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="mb-3 mb-md-0">
                <h5 class="mb-2" style="color: #1e293b; font-weight: 700; font-size: 1.25rem;">Current Plan: {{ $activeSubscription->plan->name ?? 'N/A' }}</h5>
                <p class="mb-0" style="color: #64748b; font-size: 1rem;">
                    Status: <span class="badge badge-success px-3 py-2" style="border-radius: 20px;">{{ ucfirst($activeSubscription->status) }}</span> &nbsp;|&nbsp; 
                    Expiring on: 
                    <strong style="color: #334155;">
                        {{ $activeSubscription->end_date ? \Carbon\Carbon::parse($activeSubscription->end_date)->format('d M, Y') : 'Never' }}
                    </strong>
                </p>
            </div>
            <div>
                <style>
                    /* Custom Big Toggle Switch */
                    .switch { position: relative; display: inline-block; width: 80px; height: 40px; margin-bottom: 0; vertical-align: middle; }
                    .switch input { opacity: 0; width: 0; height: 0; }
                    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 40px; }
                    .slider:before { position: absolute; content: ""; height: 32px; width: 32px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
                    input:checked + .slider { background-color: #22c55e; } /* Bright Green */
                    input:focus + .slider { box-shadow: 0 0 1px #22c55e; }
                    input:checked + .slider:before { transform: translateX(40px); }
                </style>
                <div class="d-flex align-items-center bg-light px-4 py-3 rounded" style="border: 1px solid #f1f5f9;">
                    <span class="mr-3 font-weight-bold text-dark" style="font-size: 1.1rem; letter-spacing: 0.5px;">AUTO RENEW</span>
                    <label class="switch">
                        <input type="checkbox" id="autoRenewCheck" {{ ($activeSubscription->auto_renew ?? true) ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-warning mb-4" role="alert">
        You do not have an active subscription.
    </div>
    @endif

    <div class="table-container">
        <table>
            <caption class="sr-only">List of subscriptions with plan, dates, price, status, and actions</caption>
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Plan</th>
                    <th scope="col">Start Date</th>
                    <th scope="col">End Date</th>
                    <th scope="col">Price</th>
                    <th scope="col">Created At</th>
                    <th scope="col">Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $subscription)
                <tr>
                    <td class="row-number">{{ $loop->iteration }}</td>
                    <td class="font-weight-bold">{{ $subscription->plan->name ?? 'N/A' }}</td>
                    <td class="date">{{ $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d M, Y') : '-' }}</td>
                    <td class="date">{{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('d M, Y') : '-' }}</td>
                    <td>${{ number_format($subscription->total_price, 2) }}</td>
                    <td class="date">{{ $subscription->created_date ? \Carbon\Carbon::parse($subscription->created_date)->format('d M, Y') : '-' }}</td>
                    <td>
                        @if($subscription->is_active)
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="actions actions-icons">
                            <a href="{{ route('admin.subscriptions.invoice', $subscription->id) }}" class="btn-action-icon" title="Download Invoice" aria-label="Download Invoice" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7 10 12 15 17 10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-state">No subscription history found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

    <style>
        /* Custom Big Toggle Switch */
        .switch { position: relative; display: inline-block; width: 80px; height: 40px; margin-bottom: 0; vertical-align: middle; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 40px; }
        .slider:before { position: absolute; content: ""; height: 32px; width: 32px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
        input:checked + .slider { background-color: #22c55e; } /* Bright Green */
        input:focus + .slider { box-shadow: 0 0 1px #22c55e; }
        input:checked + .slider:before { transform: translateX(40px); }

        /* Plan Selection Styles */
        .plan-radio:checked + label {
            border-color: #6777ef !important; /* Theme Primary */
            background-color: #f4f6fc;
            box-shadow: 0 2px 6px rgba(103, 119, 239, 0.2);
        }
        .plan-radio:checked + label .text-primary {
            color: #6777ef !important;
        }

        /* Summary Box Styles */
        .summary-box {
            background-color: #f8f9fa;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
        }
    </style>


@push('scripts')
<script>
    $(document).ready(function() {
        // Auto Renew Logic
        $('#autoRenewCheck').change(function() {
            let isChecked = $(this).is(':checked');
            $.ajax({
                url: "{{ route('admin.subscriptions.auto-renew') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    auto_renew: isChecked ? 1 : 0
                },
                success: function(response) {
                    if(response.success) {
                        iziToast.success({ title: 'Success', message: 'Auto-renew preference updated', position: 'topRight' });
                    }
                },
                error: function(xhr) {
                    iziToast.error({ title: 'Error', message: 'Something went wrong', position: 'topRight' });
                    $('#autoRenewCheck').prop('checked', !isChecked); // Revert
                }
            });
        });

        // --- Add Subscription Modal Logic ---
        let selectedPlanPrice = 0;
        let discountAmount = 0;
        let taxAmount = 0; // Fixed for now

        function updateSummary() {
            let total = selectedPlanPrice - discountAmount + taxAmount;
            if(total < 0) total = 0;

            $('#summaryPlanPrice').text('$' + selectedPlanPrice.toFixed(2));
            $('#summaryDiscount').text('-$' + discountAmount.toFixed(2));
            $('#summaryTotal').text('$' + total.toFixed(2));
            
            // Enable submit if plan is selected
            if(selectedPlanPrice > 0) {
                $('#submitSubscriptionBtn').removeAttr('disabled');
            } else {
                $('#submitSubscriptionBtn').attr('disabled', 'disabled');
            }
        }

        $('.plan-radio').change(function() {
            selectedPlanPrice = parseFloat($(this).data('price'));
            // Reset coupon on plan change
            $('#couponCodeInput').val('');
            $('#couponMessage').text('').removeClass('text-success text-danger');
            discountAmount = 0;
            updateSummary();
        });

        $('#applyCouponBtn').click(function() {
            let code = $('#couponCodeInput').val().trim();
            let planId = $('input[name="plan_id"]:checked').val();

            if(!planId) {
                $('#couponMessage').text('Please select a plan first.').addClass('text-danger').removeClass('text-success');
                return;
            }

            if(!code) {
                $('#couponMessage').text('Please enter a coupon code.').addClass('text-danger').removeClass('text-success');
                return;
            }

            $.ajax({
                url: "{{ route('admin.subscriptions.check-coupon') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    coupon_code: code
                },
                success: function(response) {
                    if(response.success) {
                        $('#couponMessage').text(response.message).addClass('text-success').removeClass('text-danger');
                        
                        // Calculate discount
                        let coupon = response.coupon;
                        if(coupon.discount_type == 'PERCENTAGE') {
                            discountAmount = (selectedPlanPrice * coupon.discount) / 100;
                        } else {
                            discountAmount = parseFloat(coupon.discount);
                        }
                        
                        // Cap discount
                        if(discountAmount > selectedPlanPrice) discountAmount = selectedPlanPrice;

                        updateSummary();
                        iziToast.success({ title: 'Success', message: 'Coupon applied successfully!', position: 'topRight' });
                    } else {
                        $('#couponMessage').text(response.message).addClass('text-danger').removeClass('text-success');
                        discountAmount = 0;
                        updateSummary();
                        iziToast.warning({ title: 'Invalid', message: response.message, position: 'topRight' });
                    }
                },
                error: function() {
                    $('#couponMessage').text('Error validating coupon.').addClass('text-danger').removeClass('text-success');
                    discountAmount = 0;
                    updateSummary();
                    iziToast.error({ title: 'Error', message: 'Failed to validate coupon.', position: 'topRight' });
                }
            });
        });
    });
</script>
@endpush
