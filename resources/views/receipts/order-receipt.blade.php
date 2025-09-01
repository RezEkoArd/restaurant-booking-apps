<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Receipt - Order #{{ $order->id }}</title>
    <style>
        /* Reset & Base Styles */
        body,
        table,
        div,
        span,
        p {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            color: #2c3e50;
            font-size: 12px;
            line-height: 1.5;
        }

        /* Container - dompdf tidak support max-width, jadi gunakan width + margin auto */
        .receipt-container {
            width: 400px;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #34495e;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .company-info {
            font-size: 10px;
            color: #7f8c8d;
            line-height: 1.4;
            margin-bottom: 10px;
        }

        .receipt-title {
            font-size: 14px;
            font-weight: bold;
            color: #e74c3c;
            background: #ecf0f1;
            padding: 5px 10px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 10px;
        }

        /* Order Info */
        .order-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }

        .order-details {
            margin-bottom: 8px;
        }

        .order-details:last-child {
            margin-bottom: 0;
        }

        .label {
            font-weight: bold;
            color: #34495e;
            font-size: 11px;
            display: inline-block;
            width: 100px;
        }

        .value {
            color: #2c3e50;
            font-size: 11px;
            display: inline-block;
        }

        /* Items Section */
        .items-section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #34495e;
            border-bottom: 1px solid #bdc3c7;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .item-row {
            padding: 8px 0;
            border-bottom: 1px dotted #bdc3c7;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-name {
            font-weight: bold;
            color: #2c3e50;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .item-details {
            display: table;
            width: 100%;
            margin-top: 2px;
        }

        .qty-price {
            display: table-cell;
            width: 60%;
            font-size: 10px;
            color: #7f8c8d;
        }

        .item-subtotal {
            display: table-cell;
            width: 40%;
            font-weight: bold;
            color: #27ae60;
            font-size: 11px;
            text-align: right;
        }

        /* Summary Section */
        .summary-section {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }

        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .summary-label {
            display: table-cell;
            width: 55%;
            font-weight: bold;
        }

        .summary-value {
            display: table-cell;
            width: 45%;
            text-align: right;
        }

        .summary-row.subtotal {
            padding-top: 8px;
            border-top: 1px solid #bdc3c7;
        }

        .summary-row.total {
            font-size: 14px;
            color: #e74c3c;
            background: white;
            padding: 10px;
            border-radius: 3px;
            margin-top: 10px;
            border: 2px solid #e74c3c;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #bdc3c7;
        }

        .thank-you {
            font-size: 13px;
            font-weight: bold;
            color: #27ae60;
            margin-bottom: 8px;
        }

        .footer-info {
            font-size: 9px;
            color: #95a5a6;
            line-height: 1.3;
        }

        .generated-info {
            margin-top: 10px;
            font-size: 8px;
            color: #bdc3c7;
            font-style: italic;
        }

        /* Currency styling */
        .currency {
            font-size: 10px;
            color: #95a5a6;
        }

        /* Page setup for PDF */
        @page {
            margin: 15mm;
        }

        body {
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">
                {{ $paymentData['company']['name'] ?? 'Warung DiSayurin Warga' }}
            </div>
            <div class="company-info">
                {{ $paymentData['company']['address'] ?? 'Jl. In Aja Dulu No. 123, Jakarta Pusat' }}<br>
                Telp: {{ $paymentData['company']['phone'] ?? '(021) 1234-5678' }}<br>
                Email: {{ $paymentData['company']['email'] ?? 'info@wartegsayurin.com' }}
            </div>
            <div class="receipt-title">RECEIPT / STRUK</div>
        </div>

        <!-- Order Info -->
        <div class="order-info">
            <div class="order-details">
                <span class="label">Order ID:</span>
                <span class="value">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="order-details">
                <span class="label">Table:</span>
                <span class="value">{{ $paymentData['table_number'] }}</span>
            </div>
            <div class="order-details">
                <span class="label">Date:</span>
                <span class="value">{{ \Carbon\Carbon::parse($order->opened_at)->format('d/m/Y H:i') }}</span>
            </div>
            @if($order->closed_at)
            <div class="order-details">
                <span class="label">Closed:</span>
                <span class="value">{{ \Carbon\Carbon::parse($order->closed_at)->format('d/m/Y H:i') }}</span>
            </div>
            @endif
            <div class="order-details">
                <span class="label">Cashier:</span>
                <span class="value">{{ $order->user->name ?? 'System' }}</span>
            </div>
        </div>

        <!-- Items -->
        <div class="items-section">
            <div class="section-title">Order Items</div>
            @foreach($paymentData['items'] as $item)
            <div class="item-row">
                <div class="item-name">{{ $item['menu_name'] }}</div>
                <div class="item-details">
                    <div class="qty-price">
                        <span>{{ $item['quantity'] }}x</span>
                        <span><span class="currency">Rp</span> {{ number_format($item['price'], 0, ',', '.') }}</span>
                    </div>
                    <div class="item-subtotal">
                        <span class="currency">Rp</span> {{ number_format($item['subtotal'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-row">
                <span class="summary-label">Subtotal:</span>
                <span class="summary-value"><span class="currency">Rp</span> {{ number_format($paymentData['subtotal'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Service Charge (5%):</span>
                <span class="summary-value"><span class="currency">Rp</span> {{ number_format($paymentData['service_charge'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Tax/PPN (11%):</span>
                <span class="summary-value"><span class="currency">Rp</span> {{ number_format($paymentData['tax'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-row subtotal">
                <span class="summary-label">SUBTOTAL:</span>
                <span class="summary-value"><span class="currency">Rp</span> {{ number_format($paymentData['subtotal'] + $paymentData['service_charge'] + $paymentData['tax'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-row total">
                <span class="summary-label">TOTAL AMOUNT:</span>
                <span class="summary-value"><span class="currency">Rp</span> {{ number_format($paymentData['total_amount'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="thank-you">Terima Kasih atas Kunjungan Anda!</div>
            <div class="footer-info">
                Simpan struk ini sebagai bukti pembayaran<br>
                Komplain dapat disampaikan dalam 24 jam<br>
                dengan menunjukkan struk ini
            </div>
            <div class="generated-info">
                Generated: {{ $paymentData['generated_at']->format('d/m/Y H:i:s') }}<br>
                System: Laravel Receipt Generator v1.0
            </div>
        </div>
    </div>
</body>

</html>