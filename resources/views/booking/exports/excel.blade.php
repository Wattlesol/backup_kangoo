<table>
    <thead>
        <tr>
            <th colspan="9">Orders Summary</th>
        </tr>
        <tr>
            <th>Total Orders</th>
            <th>Unassigned</th>
            <th>High Priority</th>
            <th>Pending</th>
            <th>In Progress</th>
            <th>Completed</th>
            <th>Cancelled</th>
            <th>Overdue</th>
            <th>Total Value</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $summary['total_orders'] }}</td>
            <td>{{ $summary['unassigned_orders'] }}</td>
            <td>{{ $summary['high_priority_orders'] }}</td>
            <td>{{ $summary['pending_orders'] }}</td>
            <td>{{ $summary['in_progress_orders'] }}</td>
            <td>{{ $summary['completed_orders'] }}</td>
            <td>{{ $summary['cancelled_orders'] }}</td>
            <td>{{ $summary['overdue_orders'] }}</td>
            <td>{{ $summary['total_value'] }}</td>
        </tr>
        <tr></tr>
        <tr>
            <th>Order Number</th>
            <th>Service</th>
            <th>Customer</th>
            <th>Customer Email</th>
            <th>Partner</th>
            <th>Status</th>
            <th>Stage</th>
            <th>Priority</th>
            <th>Amount</th>
            <th>Expected Completion</th>
            <th>Created At</th>
        </tr>
        @foreach($bookings as $booking)
            <tr>
                <td>{{ $booking->sanad_reference ?: 'ORD-'.$booking->id }}</td>
                <td>{{ optional($booking->service)->name_en ?: optional($booking->service)->name }}</td>
                <td>{{ optional($booking->customer)->display_name }}</td>
                <td>{{ optional($booking->customer)->email }}</td>
                <td>{{ optional($booking->provider)->display_name ?: 'Unassigned' }}</td>
                <td>{{ $booking->status }}</td>
                <td>{{ $booking->sanad_stage }}</td>
                <td>{{ ucfirst($booking->sanad_priority ?: 'normal') }}</td>
                <td>{{ $booking->total_amount ?: $booking->amount }}</td>
                <td>{{ optional($booking->expected_completion_at)->format('Y-m-d H:i') }}</td>
                <td>{{ optional($booking->created_at)->format('Y-m-d H:i') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
