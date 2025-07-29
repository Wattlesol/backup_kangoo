<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order {{ $order->formatted_order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #2c3e50;
            background: #f8f9fa;
        }

        .print-container {
            max-width: 900px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .print-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            position: relative;
        }

        .print-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .header-content {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-info h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .company-info p {
            font-size: 16px;
            opacity: 0.9;
            margin: 2px 0;
        }

        .order-badge {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .order-badge h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .order-badge .order-number {
            font-size: 18px;
            font-weight: 600;
            opacity: 0.9;
        }

        .print-body {
            padding: 40px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            border-left: 4px solid #667eea;
        }

        .info-card h3 {
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .info-card h3::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            margin-right: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
        }

        .info-value {
            color: #2c3e50;
            font-weight: 500;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-pending::before { background: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-confirmed::before { background: #155724; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-processing::before { background: #004085; }
        .status-shipped { background: #e2e3e5; color: #383d41; }
        .status-shipped::before { background: #383d41; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .status-delivered::before { background: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-cancelled::before { background: #721c24; }

        .payment-pending { background: #fff3cd; color: #856404; }
        .payment-pending::before { background: #856404; }
        .payment-paid { background: #d4edda; color: #155724; }
        .payment-paid::before { background: #155724; }
        .payment-failed { background: #f8d7da; color: #721c24; }
        .payment-failed::before { background: #721c24; }
        .payment-refunded { background: #e2e3e5; color: #383d41; }
        .payment-refunded::before { background: #383d41; }

        .address-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }

        .address-card h3 {
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .address-card h3::before {
            content: '📍';
            margin-right: 10px;
            font-size: 20px;
        }

        .address-line {
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .address-line:last-child {
            margin-bottom: 0;
        }

        .items-section {
            margin: 40px 0;
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 60px;
            height: 3px;
            background: #764ba2;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }

        .items-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }

        .items-table tbody tr:hover {
            background: #f8f9fa;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .product-info {
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 15px;
            margin-bottom: 4px;
        }

        .product-details {
            font-size: 12px;
            color: #6c757d;
            line-height: 1.4;
        }

        .quantity-badge {
            background: #667eea;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            text-align: center;
            min-width: 50px;
        }

        .price-cell {
            text-align: right;
            font-weight: 600;
            color: #2c3e50;
            font-size: 15px;
        }

        .totals-section {
            margin-top: 40px;
            display: flex;
            justify-content: flex-end;
        }

        .totals-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            padding: 25px;
            min-width: 350px;
            border: 1px solid #dee2e6;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .totals-row:last-child {
            border-bottom: none;
        }

        .totals-row.total-final {
            border-top: 2px solid #667eea;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
        }

        .totals-label {
            font-weight: 600;
            color: #6c757d;
        }

        .totals-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .print-footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }

        .print-footer h4 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .print-footer p {
            color: #6c757d;
            margin: 5px 0;
        }

        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .print-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .print-btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        @media print {
            body {
                margin: 0;
                background: white;
            }
            .print-container {
                margin: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .print-actions {
                display: none;
            }
            .print-header {
                background: #667eea !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            .items-table th {
                background: #667eea !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Print Actions -->
    <div class="print-actions">
        <button class="print-btn" onclick="window.print()">
            🖨️ Print Receipt
        </button>
        <a href="{{ route('order.index') }}" class="print-btn" style="background: #6c757d;">
            ← Back to Orders
        </a>
    </div>

    <div class="print-container">
        <!-- Header -->
        <div class="print-header">
            <div class="header-content">
                <div class="company-info">
                    <h1>{{ $data->site_name ?? 'Your Store' }}</h1>
                    @if($data && $data->contact_number)
                        <p>📞 {{ $data->contact_number }}</p>
                    @endif
                    @if($data && $data->site_email)
                        <p>✉️ {{ $data->site_email }}</p>
                    @endif
                    @if($data && $data->site_description)
                        <p>{{ $data->site_description }}</p>
                    @endif
                </div>
                <div class="order-badge">
                    <h2>ORDER RECEIPT</h2>
                    <div class="order-number">{{ $order->formatted_order_number }}</div>
                </div>
            </div>
        </div>

        <div class="print-body">
            <!-- Order & Customer Info Grid -->
            <div class="info-grid">
                <div class="info-card">
                    <h3>Order Information</h3>
                    <div class="info-row">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value">{{ $order->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Order Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status:</span>
                        <span class="info-value">
                            <span class="status-badge payment-{{ $order->payment_status }}">{{ ucfirst($order->payment_status) }}</span>
                        </span>
                    </div>
                    @if($order->store)
                    <div class="info-row">
                        <span class="info-label">Store:</span>
                        <span class="info-value">{{ $order->store->name }}</span>
                    </div>
                    @elseif($order->is_admin_order)
                    <div class="info-row">
                        <span class="info-label">Store:</span>
                        <span class="info-value">Admin Store</span>
                    </div>
                    @endif
                    @if($order->delivery_phone)
                    <div class="info-row">
                        <span class="info-label">Delivery Phone:</span>
                        <span class="info-value">{{ $order->delivery_phone }}</span>
                    </div>
                    @endif
                </div>

                <div class="info-card">
                    <h3>Customer Information</h3>
                    @if($order->customer)
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value">{{ $order->customer->display_name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value">{{ $order->customer->email }}</span>
                        </div>
                        @if($order->customer->contact_number)
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span class="info-value">{{ $order->customer->contact_number }}</span>
                        </div>
                        @endif
                    @else
                        <div class="info-row">
                            <span class="info-label">Customer:</span>
                            <span class="info-value">Guest Order</span>
                        </div>
                    @endif
                    @if($order->delivery_notes)
                    <div class="info-row">
                        <span class="info-label">Delivery Notes:</span>
                        <span class="info-value">{{ $order->delivery_notes }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Delivery Address -->
            @if($order->delivery_address)
            @php
                $address = $order->delivery_address;

                // Handle different address formats
                if (is_string($address)) {
                    // Try to decode JSON string
                    $decoded = json_decode($address, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $address = $decoded;
                    }
                }
            @endphp

            <div class="address-card">
                <h3>Delivery Address</h3>
                @if(is_array($address) && !empty($address))
                    @if(!empty($address['name']))
                        <div class="address-line"><strong>{{ $address['name'] }}</strong></div>
                    @endif
                    @if(!empty($address['address']))
                        <div class="address-line">{{ $address['address'] }}</div>
                    @endif
                    <div class="address-line">
                        @if(!empty($address['city'])){{ $address['city'] }}@endif
                        @if(!empty($address['city']) && !empty($address['state'])), @endif
                        @if(!empty($address['state'])){{ $address['state'] }}@endif
                        @if(!empty($address['zip'])) {{ $address['zip'] }}@endif
                    </div>
                    @if(!empty($address['country']))
                        <div class="address-line">{{ $address['country'] }}</div>
                    @endif
                @else
                    <div class="address-line">{{ $order->delivery_address }}</div>
                @endif
            </div>
            @endif

            <!-- Order Items -->
            <div class="items-section">
                <h2 class="section-title">Order Items</h2>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Product Details</th>
                            <th style="width: 15%;" class="text-center">Quantity</th>
                            <th style="width: 20%;" class="text-right">Unit Price</th>
                            <th style="width: 20%;" class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>
                                <div class="product-info">
                                    <div class="product-name">{{ $item->product_name }}</div>
                                    <div class="product-details">
                                        @if($item->product_sku)
                                            <div>SKU: {{ $item->product_sku }}</div>
                                        @endif
                                        @if($item->product_variant_name)
                                            <div>Variant: {{ $item->product_variant_name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="quantity-badge">{{ $item->quantity }}</span>
                            </td>
                            <td class="price-cell">{{ getPriceFormat($item->unit_price) }}</td>
                            <td class="price-cell">{{ getPriceFormat($item->total_price) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Order Totals -->
            <div class="totals-section">
                <div class="totals-card">
                    <div class="totals-row">
                        <span class="totals-label">Subtotal:</span>
                        <span class="totals-value">{{ getPriceFormat($order->subtotal) }}</span>
                    </div>
                    @if($order->tax_amount > 0)
                    <div class="totals-row">
                        <span class="totals-label">Tax:</span>
                        <span class="totals-value">{{ getPriceFormat($order->tax_amount) }}</span>
                    </div>
                    @endif
                    @if($order->delivery_fee > 0)
                    <div class="totals-row">
                        <span class="totals-label">Delivery Fee:</span>
                        <span class="totals-value">{{ getPriceFormat($order->delivery_fee) }}</span>
                    </div>
                    @endif
                    @if($order->discount_amount > 0)
                    <div class="totals-row">
                        <span class="totals-label">Discount:</span>
                        <span class="totals-value" style="color: #28a745;">-{{ getPriceFormat($order->discount_amount) }}</span>
                    </div>
                    @endif
                    <div class="totals-row total-final">
                        <span class="totals-label">Total Amount:</span>
                        <span class="totals-value">{{ getPriceFormat($order->total_amount) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="print-footer">
            <h4>Thank you for your order!</h4>
            <p>This is a computer-generated receipt. No signature required.</p>
            <p>Generated on {{ now()->format('M d, Y H:i A') }}</p>
            @if($data && $data->site_description)
                <p style="margin-top: 15px; font-style: italic;">{{ $data->site_description }}</p>
            @endif
        </div>
    </div>
</body>
</html>
