<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #FF6B35;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #FF6B35;
            margin: 0;
            font-size: 28px;
        }
        .alert-box {
            background-color: #fff3cd;
            border-left: 4px solid #FF6B35;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .order-number {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
        }
        .order-number strong {
            font-size: 18px;
            color: #333;
        }
        .section {
            margin: 25px 0;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #FF6B35;
            margin-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 5px;
        }
        .info-row {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th {
            background-color: #FF6B35;
            color: white;
            padding: 12px;
            text-align: left;
        }
        table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        .total-row {
            font-weight: bold;
            font-size: 16px;
        }
        .total-amount {
            font-size: 20px;
            color: #FF6B35;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #FF6B35;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>New Order Received</h1>
        </div>

        <div class="alert-box">
            <strong>⚠️ Action Required:</strong> A new order has been placed and requires your attention.
        </div>

        <div class="order-number">
            <strong>Order Number: {{ $order->order_number }}</strong>
        </div>

        <div class="section">
            <div class="section-title">Customer Information</div>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $order->billing_first_name }} {{ $order->billing_last_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $order->billing_email }}</span>
            </div>
            @if($order->billing_phone)
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value">{{ $order->billing_phone }}</span>
            </div>
            @endif
            @if($order->user)
            <div class="info-row">
                <span class="info-label">User ID:</span>
                <span class="info-value">#{{ $order->user_id }}</span>
            </div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">Order Items</div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>£{{ number_format($item->product_price, 2) }}</td>
                        <td>£{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Order Summary</div>
            <div class="info-row">
                <span class="info-label">Subtotal:</span>
                <span class="info-value">£{{ number_format($order->total_amount - $order->shipping_cost, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Shipping:</span>
                <span class="info-value">£{{ number_format($order->shipping_cost, 2) }}</span>
            </div>
            <div class="info-row total-row">
                <span class="info-label">Total Amount:</span>
                <span class="info-value total-amount">£{{ number_format($order->total_amount, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span class="info-value">{{ ucfirst($order->payment_method) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Status:</span>
                <span class="info-value">{{ ucfirst($order->payment_status) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Order Status:</span>
                <span class="info-value">{{ ucfirst($order->status) }}</span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Billing Address</div>
            <p>
                {{ $order->billing_first_name }} {{ $order->billing_last_name }}<br>
                {{ $order->billing_address }}<br>
                {{ $order->billing_city }}, {{ $order->billing_postcode }}<br>
                {{ $order->billing_country }}
            </p>
        </div>

        @if($order->shipping_address && ($order->shipping_address !== $order->billing_address))
        <div class="section">
            <div class="section-title">Shipping Address</div>
            <p>
                {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}, {{ $order->shipping_postcode }}<br>
                {{ $order->shipping_country }}
            </p>
        </div>
        @endif

        <div class="section">
            <div class="section-title">Shipping Information</div>
            <div class="info-row">
                <span class="info-label">Shipping Method:</span>
                <span class="info-value">{{ $order->shipping_method ?? 'Standard Shipping' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Order Date:</span>
                <span class="info-value">{{ $order->created_at->format('F j, Y g:i A') }}</span>
            </div>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}/admin/orders/{{ $order->id }}" class="button">View Order in Admin Panel</a>
        </div>

        <div class="footer">
            <p>This is an automated notification. Please process this order as soon as possible.</p>
            <p><strong>{{ config('app.name') }} Admin</strong></p>
        </div>
    </div>
</body>
</html>
