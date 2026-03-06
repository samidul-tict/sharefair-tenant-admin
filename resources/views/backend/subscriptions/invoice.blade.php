<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $subscription->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); font-size: 16px; line-height: 24px; }
        .invoice-box table { width: 100%; line-height: inherit; text-align: left; }
        .invoice-box table td { padding: 5px; vertical-align: top; }
        .invoice-box table tr td:nth-child(2) { text-align: right; }
        .top-header { font-size: 45px; line-height: 45px; color: #333; }
        .information-table td { padding-bottom: 40px; }
        .heading td { background: #eee; border-bottom: 1px solid #ddd; font-weight: bold; }
        .item td { border-bottom: 1px solid #eee; }
        .item.last td { border-bottom: none; }
        .total td { border-top: 2px solid #eee; font-weight: bold; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">
                                <h1>INVOICE</h1>
                            </td>
                            <td>
                                Invoice #: {{ $subscription->id }}<br>
                                Created: {{ \Carbon\Carbon::parse($subscription->created_date)->format('F d, Y') }}<br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="information">
                <td colspan="2">
                    <table class="information-table">
                        <tr>
                            <td>
                                <strong>Billed To:</strong><br>
                                {{ $subscription->tenant->name ?? 'Tenant Name' }}<br>
                                {{ $subscription->tenant->address_line_1 ?? '' }}<br>
                                {{ $subscription->tenant->email ?? '' }}
                            </td>
                            <td>
                                <strong>Payable To:</strong><br>
                                Share Fare<br>
                                123 Legal Street<br>
                                New York, NY, 10001
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr class="heading">
                <td>Item</td>
                <td>Price</td>
            </tr>

            @php
                $netAmount = $subscription->total_price - $subscription->tax_price;
                // Use plan's selling price as base, fallback to netAmount (which means no discount shown) if plan is missing.
                $basePrice = $subscription->plan?->selling_price ?? $netAmount;
                $discount = 0;
                $couponCode = $subscription->coupon?->code ?? 'Applied';
                $discountText = "Coupon Discount ($couponCode)";

                if ($subscription->coupon) {
                    if ($subscription->coupon->discount_type == 'PERCENTAGE') {
                        // Calculate discount based on the percentage
                        $percentage = $subscription->coupon->discount;
                        $discount = ($basePrice * $percentage) / 100;
                        $discountText = "Coupon Discount ($couponCode - " . (float)$percentage . "%)";
                    } elseif ($subscription->coupon->discount_type == 'FIXED_AMOUNT') {
                        // Fixed amount discount
                        $discount = $subscription->coupon->discount;
                        $discountText = "Coupon Discount ($couponCode)";
                    } else {
                        // Fallback logic for legacy/unknown types
                        $discount = $basePrice - $netAmount;
                    }
                    
                    // Safety check: Discount cannot exceed base price
                    if ($discount > $basePrice) $discount = $basePrice;
                    if ($discount < 0) $discount = 0;
                }
            @endphp

            <tr class="item">
                <td>{{ $subscription->plan?->name ?? 'Subscription Plan' }} (Multimedia)</td>
                <td>${{ number_format($basePrice, 2) }}</td>
            </tr>

            @if($discount > 0)
            <tr class="item">
                <td>{{ $discountText }}</td>
                <td>- ${{ number_format($discount, 2) }}</td>
            </tr>
            @endif

            <tr class="item">
                <td>Tax</td>
                <td>${{ number_format($subscription->tax_price, 2) }}</td>
            </tr>

            <tr class="total">
                <td></td>
                <td>Total: ${{ number_format($subscription->total_price, 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
