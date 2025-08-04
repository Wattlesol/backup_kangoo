@php
    // Get site settings and branding
    $sitesetup = App\Models\Setting::where('type','site-setup')->where('key', 'site-setup')->first();
    $siteData = $sitesetup ? json_decode($sitesetup->value) : null;

    // Get theme colors
    $brandColors = \App\Models\ThemeSetting::getBrandColors();
    $primaryColor = $brandColors['blue']['light'] ?? '#5F60B9';
    $primaryColorDark = $brandColors['blue']['dark'] ?? '#4A4A8A';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $order->formatted_order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            background: #f5f5f5;
        }

        .receipt-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Header Section */
        .receipt-header {
            background: white;
            padding: 40px 40px 30px;
            border-bottom: 3px solid {{ $primaryColor }};
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .company-section {
            flex: 1;
        }

        .company-logo {
            max-width: 200px;
            max-height: 80px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .company-details {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .company-details p {
            margin: 4px 0;
        }

        .receipt-info {
            text-align: right;
            min-width: 250px;
        }

        .receipt-title {
            font-size: 24px;
            font-weight: 700;
            color: {{ $primaryColor }};
            margin-bottom: 8px;
        }

        .receipt-number {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .receipt-date {
            color: #666;
            font-size: 14px;
        }

        /* Body Section */
        .receipt-body {
            padding: 40px;
        }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .info-block h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f0f0;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f8f8f8;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: #666;
        }

        .info-value {
            font-weight: 500;
            color: #333;
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #e2e3e5; color: #383d41; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .payment-pending { background: #fff3cd; color: #856404; }
        .payment-paid { background: #d4edda; color: #155724; }
        .payment-failed { background: #f8d7da; color: #721c24; }
        .payment-refunded { background: #e2e3e5; color: #383d41; }

        /* Address Section */
        .address-section {
            background: #f9f9f9;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid {{ $primaryColor }};
        }

        .address-section h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .address-line {
            margin-bottom: 6px;
            color: #555;
            line-height: 1.5;
        }

        .address-line:last-child {
            margin-bottom: 0;
        }

        /* Items Section */
        .items-section {
            margin: 30px 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid {{ $primaryColor }};
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ddd;
        }

        .items-table th {
            background: #f8f8f8;
            color: #333;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 2px solid #ddd;
        }

        .items-table th.text-center {
            text-align: center;
        }

        .items-table th.text-right {
            text-align: right;
        }

        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
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
            color: #333;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .product-details {
            font-size: 12px;
            color: #666;
            line-height: 1.4;
        }

        .quantity-cell {
            text-align: center;
            font-weight: 600;
            color: #333;
        }

        .price-cell {
            text-align: right;
            font-weight: 600;
            color: #333;
        }

        /* Totals Section */
        .totals-section {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }

        .totals-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            min-width: 300px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .totals-row:last-child {
            border-bottom: none;
        }

        .totals-row.total-final {
            border-top: 2px solid {{ $primaryColor }};
            margin-top: 10px;
            padding-top: 12px;
            font-size: 16px;
            font-weight: 700;
        }

        .totals-label {
            font-weight: 500;
            color: #555;
        }

        .totals-value {
            font-weight: 600;
            color: #333;
        }

        /* Footer Section */
        .receipt-footer {
            background: #f8f8f8;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #ddd;
        }

        .receipt-footer h4 {
            color: {{ $primaryColor }};
            font-size: 18px;
            margin-bottom: 10px;
        }

        .receipt-footer p {
            color: #666;
            margin: 5px 0;
            font-size: 13px;
        }

        /* Print Actions */
        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .print-btn {
            background: {{ $primaryColor }};
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .print-btn:hover {
            background: {{ $primaryColorDark }};
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .print-btn.secondary {
            background: #6c757d;
        }

        .print-btn.secondary:hover {
            background: #5a6268;
        }

        /* Print Styles */
        @media print {
            body {
                margin: 0;
                background: white;
                font-size: 12px;
            }

            .receipt-container {
                margin: 0;
                box-shadow: none;
                max-width: none;
            }

            .print-actions {
                display: none;
            }

            .receipt-header {
                padding: 30px 30px 20px;
            }

            .receipt-body {
                padding: 30px;
            }

            .receipt-footer {
                padding: 20px 30px;
            }

            .items-table th {
                background: #f8f8f8 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .section-title {
                border-bottom-color: {{ $primaryColor }} !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .totals-row.total-final {
                border-top-color: {{ $primaryColor }} !important;
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
        <a href="{{ route('order.index') }}" class="print-btn secondary">
            ← Back to Orders
        </a>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="header-content">
                <div class="company-section">
                    @if($siteData && getSingleMedia(imageSession('get'),'logo',null))
                        <img src="{{ getSingleMedia(imageSession('get'),'logo',null) }}" alt="Company Logo" class="company-logo">
                    @endif
                    <h1 class="company-name">{{ $siteData->site_name ?? config('app.name', 'Your Store') }}</h1>
                    <div class="company-details">
                        @if($siteData && $siteData->contact_number)
                            <p>Phone: {{ $siteData->contact_number }}</p>
                        @endif
                        @if($siteData && $siteData->site_email)
                            <p>Email: {{ $siteData->site_email }}</p>
                        @endif
                        @if($siteData && $siteData->site_description)
                            <p>{{ $siteData->site_description }}</p>
                        @endif
                    </div>
                </div>
                <div class="receipt-info">
                    <h2 class="receipt-title">RECEIPT</h2>
                    <div class="receipt-number"># {{ $order->formatted_order_number }}</div>
                    <div class="receipt-date">{{ $order->created_at->format('M d, Y H:i A') }}</div>
                </div>
            </div>
        </div>

        <div class="receipt-body">
            <!-- Order & Customer Info Grid -->
            <div class="info-section">
                <div class="info-block">
                    <h3>Order Information</h3>
                    <div class="info-item">
                        <span class="info-label">Order Date:</span>
                        <span class="info-value">{{ $order->created_at->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Order Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Payment Status:</span>
                        <span class="info-value">
                            <span class="status-badge payment-{{ $order->payment_status }}">{{ ucfirst($order->payment_status) }}</span>
                        </span>
                    </div>
                    @if($order->store)
                    <div class="info-item">
                        <span class="info-label">Store:</span>
                        <span class="info-value">{{ $order->store->name }}</span>
                    </div>
                    @elseif($order->is_admin_order)
                    <div class="info-item">
                        <span class="info-label">Store:</span>
                        <span class="info-value">Admin Store</span>
                    </div>
                    @endif
                    @if($order->delivery_phone)
                    <div class="info-item">
                        <span class="info-label">Delivery Phone:</span>
                        <span class="info-value">{{ $order->delivery_phone }}</span>
                    </div>
                    @endif
                </div>

                <div class="info-block">
                    <h3>Customer Information</h3>
                    @if($order->customer)
                        <div class="info-item">
                            <span class="info-label">Name:</span>
                            <span class="info-value">{{ $order->customer->display_name }}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value">{{ $order->customer->email }}</span>
                        </div>
                        @if($order->customer->contact_number)
                        <div class="info-item">
                            <span class="info-label">Phone:</span>
                            <span class="info-value">{{ $order->customer->contact_number }}</span>
                        </div>
                        @endif
                    @else
                        <div class="info-item">
                            <span class="info-label">Customer:</span>
                            <span class="info-value">Guest Order</span>
                        </div>
                    @endif
                    @if($order->delivery_notes)
                    <div class="info-item">
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

            <div class="address-section">
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
                            <td class="quantity-cell">{{ $item->quantity }}</td>
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
        <div class="receipt-footer">
            <h4>Thank you for your order!</h4>
            <p>This is a computer-generated receipt. No signature required.</p>
            <p>Generated on {{ now()->format('M d, Y H:i A') }}</p>
            @if($siteData && $siteData->site_copyright)
                <p style="margin-top: 15px; font-style: italic;">{{ $siteData->site_copyright }}</p>
            @endif
        </div>
    </div>
</body>
</html>
