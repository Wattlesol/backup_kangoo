<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .muted { color: #6b7280; }
        .summary { width: 100%; border-collapse: collapse; margin: 18px 0; }
        .summary td { border: 1px solid #e5e7eb; padding: 9px; }
        .summary strong { display: block; font-size: 15px; color: #4f46e5; }
        table.orders { width: 100%; border-collapse: collapse; }
        table.orders th, table.orders td { border-bottom: 1px solid #e5e7eb; padding: 7px 5px; text-align: left; }
        table.orders th { background: #5d5bbb; color: #fff; }
    </style>
</head>
<body>
    <h1>Orders Summary Report</h1>
    <div class="muted">Generated {{ $generatedAt->format('Y-m-d H:i') }}</div>

    <table class="summary">
        <tr>
            <td><strong>{{ $summary['total_orders'] }}</strong>Total Orders</td>
            <td><strong>{{ $summary['pending_orders'] }}</strong>Pending</td>
            <td><strong>{{ $summary['processing_orders'] }}</strong>In Progress</td>
            <td><strong>{{ $summary['delivered_orders'] }}</strong>Delivered</td>
            <td><strong>{{ $summary['cancelled_orders'] }}</strong>Cancelled</td>
        </tr>
        <tr>
            <td><strong>{{ $summary['paid_orders'] }}</strong>Paid</td>
            <td><strong>{{ $summary['unpaid_orders'] }}</strong>Unpaid</td>
            <td><strong>{{ getPriceFormat($summary['total_revenue']) }}</strong>Total Revenue</td>
            <td colspan="2"><strong>{{ getPriceFormat($summary['total_value']) }}</strong>Total Value</td>
        </tr>
    </table>

    <table class="orders">
        <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Partner</th>
                <th>Store</th>
                <th>Items</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Total</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
                <tr>
                    <td>{{ $order->formatted_order_number }}</td>
                    <td>{{ optional($order->customer)->display_name ?: 'Guest' }}</td>
                    <td>{{ optional(optional($order->store)->provider)->display_name ?: ($order->is_admin_order ? 'Admin' : 'Unassigned') }}</td>
                    <td>{{ optional($order->store)->name ?: 'Admin' }}</td>
                    <td>{{ $order->items->count() }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td>
                    <td>{{ ucfirst($order->payment_status) }}</td>
                    <td>{{ getPriceFormat($order->total_amount) }}</td>
                    <td>{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
