<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .muted { color: #6b7280; }
        .summary { width: 100%; border-collapse: collapse; margin: 18px 0; }
        .summary td { border: 1px solid #e5e7eb; padding: 10px; }
        .summary strong { display: block; font-size: 16px; color: #4f46e5; }
        table.orders { width: 100%; border-collapse: collapse; }
        table.orders th, table.orders td { border-bottom: 1px solid #e5e7eb; padding: 8px 6px; text-align: left; }
        table.orders th { background: #5d5bbb; color: #fff; }
    </style>
</head>
<body>
    <h1>Orders Summary Report</h1>
    <div class="muted">Generated {{ $generatedAt->format('Y-m-d H:i') }}</div>

    <table class="summary">
        <tr>
            <td><strong>{{ $summary['total_orders'] }}</strong>Total Orders</td>
            <td><strong>{{ $summary['unassigned_orders'] }}</strong>Unassigned</td>
            <td><strong>{{ $summary['high_priority_orders'] }}</strong>High Priority</td>
            <td><strong>{{ $summary['overdue_orders'] }}</strong>Overdue</td>
        </tr>
        <tr>
            <td><strong>{{ $summary['pending_orders'] }}</strong>Pending</td>
            <td><strong>{{ $summary['in_progress_orders'] }}</strong>In Progress</td>
            <td><strong>{{ $summary['completed_orders'] }}</strong>Completed</td>
            <td><strong>{{ getPriceFormat($summary['total_value']) }}</strong>Total Value</td>
        </tr>
    </table>

    <table class="orders">
        <thead>
            <tr>
                <th>Order</th>
                <th>Service</th>
                <th>Customer</th>
                <th>Partner</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Expected</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
                <tr>
                    <td>{{ $booking->quick_reference }}</td>
                    <td>{{ optional($booking->service)->name_en ?: optional($booking->service)->name }}</td>
                    <td>{{ optional($booking->customer)->display_name }}</td>
                    <td>{{ optional($booking->provider)->display_name ?: 'Unassigned' }}</td>
                    <td>{{ $booking->status }}</td>
                    <td>{{ ucfirst($booking->sanad_priority ?: 'normal') }}</td>
                    <td>{{ optional($booking->expected_completion_at)->format('Y-m-d H:i') ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
