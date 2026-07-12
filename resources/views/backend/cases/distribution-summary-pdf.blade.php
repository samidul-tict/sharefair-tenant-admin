<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Distribution Summary — {{ $case->case_number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1e293b; font-size: 11px; line-height: 1.45; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #0f172a; }
        h2 { font-size: 14px; margin: 18px 0 8px; color: #0f172a; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        .meta { color: #64748b; margin-bottom: 16px; }
        .stats { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .stats td { padding: 6px 8px; border: 1px solid #e2e8f0; }
        .stats td:first-child { font-weight: bold; background: #f8fafc; width: 28%; }
        .alloc-block { margin-bottom: 14px; page-break-inside: avoid; }
        .alloc-head { background: #f1f5f9; padding: 8px 10px; border: 1px solid #e2e8f0; border-bottom: none; }
        .alloc-head strong { font-size: 12px; }
        .alloc-meta { color: #64748b; font-size: 10px; margin-top: 2px; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { border: 1px solid #e2e8f0; padding: 5px 6px; text-align: left; vertical-align: top; }
        table.items th { background: #e8eef4; font-size: 10px; }
        .empty { color: #64748b; font-style: italic; padding: 8px 0; }
        .footer { margin-top: 24px; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Distribution Summary</h1>
    <div class="meta">
        Case <strong>{{ $case->case_number }}</strong>
        · {{ $case->caseType?->name ?? $case->case_type_value }}
        · {{ $case->caseStatus?->name ?? $case->case_status_value }}
        · Generated {{ $generatedAt->format('M j, Y g:i A') }}
    </div>

    <table class="stats">
        <tr><td>Assets</td><td>{{ $data['item_count'] ?? '—' }}</td></tr>
        <tr><td>Total value</td><td>${{ isset($data['total_value']) ? number_format((float) $data['total_value'], 2) : '—' }}</td></tr>
        <tr><td>Participants</td><td>{{ $data['total_users'] ?? '—' }}</td></tr>
        <tr><td>Target per user</td><td>${{ isset($data['target_value_per_user']) ? number_format((float) $data['target_value_per_user'], 2) : '—' }}</td></tr>
    </table>

    @php
        $target = (float) ($data['target_value_per_user'] ?? 0);
        $renderItems = function ($items) {
            if (empty($items)) {
                return '<p class="empty">No items.</p>';
            }
            $html = '<table class="items"><thead><tr><th>Asset</th><th>Price</th><th>Brand</th><th>Reason</th></tr></thead><tbody>';
            foreach ($items as $item) {
                $price = $item['concluded_price'] ?? $item['purchase_price'] ?? null;
                $priceStr = $price !== null && $price !== '' ? '$' . number_format((float) $price, 2) : '—';
                $html .= '<tr><td>' . e($item['name'] ?? 'Unnamed') . '</td><td>' . $priceStr . '</td><td>' . e($item['brand'] ?? '—') . '</td><td>' . e($item['allocation_reason'] ?? '—') . '</td></tr>';
            }
            return $html . '</tbody></table>';
        };
        $renderAllocSection = function ($title, $allocations, $showTarget = true) use ($target, $renderItems) {
            echo '<h2>' . e($title) . '</h2>';
            if (empty($allocations)) {
                echo '<p class="empty">No entries in this section.</p>';
                return;
            }
            foreach ($allocations as $alloc) {
                $name = $alloc['user_name'] ?? $alloc['user_email'] ?? 'User';
                $received = isset($alloc['allocated_value']) ? number_format((float) $alloc['allocated_value'], 2) : '—';
                echo '<div class="alloc-block"><div class="alloc-head"><strong>' . e($name) . '</strong>';
                echo ' <span>(' . e($alloc['user_role'] ?? '') . ')</span>';
                echo '<div class="alloc-meta">' . e($alloc['user_email'] ?? '') . ' · ' . (int) ($alloc['allocated_item_count'] ?? 0) . ' items · Received $' . $received;
                if ($showTarget) {
                    echo ' · Target $' . number_format($target, 2);
                    if (isset($alloc['value_difference']) && $alloc['value_difference'] != 0) {
                        $sign = $alloc['value_difference'] >= 0 ? '+' : '';
                        echo ' · Diff ' . $sign . '$' . number_format((float) $alloc['value_difference'], 2);
                    }
                }
                echo '</div></div>';
                echo $renderItems($alloc['items'] ?? []);
                echo '</div>';
            }
        };
    @endphp

    @php $renderAllocSection('Allocations', $data['allocations'] ?? [], true); @endphp
    @php $renderAllocSection('Non-marital assets', $data['non_marital_assets'] ?? [], false); @endphp

    <h2>Don't Want</h2>
    {!! $renderItems($data['dont_want_items'] ?? []) !!}

    <h2>Donations</h2>
    @if(!empty($data['donation_items']))
        @if(!empty($data['total_donation_items_value']))
            <p class="alloc-meta">Total donation value: ${{ number_format((float) $data['total_donation_items_value'], 2) }}</p>
        @endif
        {!! $renderItems($data['donation_items']) !!}
    @else
        <p class="empty">No donations in this case.</p>
    @endif

    <div class="footer">Share Fair · Distribution summary export · {{ $case->case_number }}</div>
</body>
</html>
