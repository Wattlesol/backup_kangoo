<table>
    <thead>
        <tr>
            <th colspan="9">Orders Summary Report</th>
        </tr>
        <tr>
            <th>Total Orders</th>
            <th>Pending</th>
            <th>In Progress</th>
            <th>Delivered</th>
            <th>Cancelled</th>
            <th>Paid</th>
            <th>Unpaid</th>
            <th>Total Revenue</th>
            <th>Total Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $summary['total_orders'] }}</td>
            <td>{{ $summary['pending_orders'] }}</td>
            <td>{{ $summary['processing_orders'] }}</td>
            <td>{{ $summary['delivered_orders'] }}</td>
            <td>{{ $summary['cancelled_orders'] }}</td>
            <td>{{ $summary['paid_orders'] }}</td>
            <td>{{ $summary['unpaid_orders'] }}</td>
            <td>{{ $summary['total_revenue'] }}</td>
            <td>{{ $summary['total_value'] }}</td>
        </tr>
        <tr></tr>
        <tr>
            <th>Order Number</th>
            <th>Customer</th>
            <th>Customer Email</th>
            <th>Partner</th>
            <th>Store</th>
            <th>Items</th>
            <th>Status</th>
            <th>Payment Status</th>
            <th>Total Amount</th>
            <th>Order Date</th>
        </tr>
        @foreach($orders as $order)
            <tr>
                <td>{{ $order->formatted_order_number }}</td>
                <td>{{ optional($order->customer)->display_name ?: 'Guest' }}</td>
                <td>{{ optional($order->customer)->email }}</td>
                <td>{{ optional(optional($order->store)->provider)->display_name ?: ($order->is_admin_order ? 'Admin' : 'Unassigned') }}</td>
                <td>{{ optional($order->store)->name ?: 'Admin' }}</td>
                <td>{{ $order->items->count() }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td>
                <td>{{ ucfirst($order->payment_status) }}</td>
                <td>{{ $order->total_amount }}</td>
                <td>{{ optional($order->created_at)->format('Y-m-d H:i') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
