<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
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
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
            font-size: 28px;
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
        .status-update {
            background-color: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
            margin: 5px 0;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-processing { background-color: #2196F3; color: #fff; }
        .status-shipped { background-color: #9c27b0; color: #fff; }
        .status-delivered { background-color: #4CAF50; color: #fff; }
        .status-cancelled { background-color: #f44336; color: #fff; }
        .section {
            margin: 25px 0;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #4CAF50;
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
            background-color: #4CAF50;
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
            background-color: #4CAF50;
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
            @if($newStatus === 'processing')
                <h1>Your Order is Being Prepared</h1>
            @elseif($newStatus === 'shipped')
                <h1>Your Order is on the Way!</h1>
            @elseif($newStatus === 'delivered')
                <h1>Your Order has been Delivered</h1>
            @elseif($newStatus === 'cancelled')
                <h1>Order Cancellation Notice</h1>
            @else
                <h1>Order Status Update</h1>
            @endif
        </div>

        <div class="order-number">
            <strong>Order Number: {{ $order->order_number }}</strong>
        </div>

        <p>Dear {{ $order->billing_first_name }} {{ $order->billing_last_name }},</p>

        @if($newStatus === 'processing')
            <p>Great news! Your order is now being processed and prepared for shipment.</p>
        @elseif($newStatus === 'shipped')
            <p>Exciting news! Your order has been shipped and is on its way to you.</p>
        @elseif($newStatus === 'delivered')
            <p>Your order has been successfully delivered! We hope you enjoy your purchase.</p>
        @elseif($newStatus === 'cancelled')
            <p>We're writing to inform you about a change to your order.</p>
        @else
            <p>We wanted to let you know that your order status has been updated.</p>
        @endif

        <div class="status-update">
            <div style="margin-bottom: 10px;">
                <strong>Previous Status:</strong>
                <span class="status-badge status-{{ $previousStatus }}">{{ ucfirst($previousStatus) }}</span>
            </div>
            <div>
                <strong>New Status:</strong>
                <span class="status-badge status-{{ $newStatus }}">{{ ucfirst($newStatus) }}</span>
            </div>
        </div>

        @if($newStatus === 'processing')
        <p>We're working hard to get your order ready. You'll receive another notification once your order has been shipped.</p>
        @elseif($newStatus === 'shipped')
        <p>Your order is now in transit and should arrive soon. You can track your order using the details below.</p>
        @elseif($newStatus === 'delivered')
        <p>We hope you're happy with your purchase! If you have any questions or concerns, please don't hesitate to contact our customer service team.</p>
        @elseif($newStatus === 'cancelled')
        <p>We're sorry to inform you that your order has been cancelled. If you have any questions about this cancellation or need assistance, please contact our customer service team.</p>
        @endif

        <div class="section">
            <div class="section-title">Order Summary</div>
            <div class="info-row">
                <span class="info-label">Order Number:</span>
                <span class="info-value">{{ $order->order_number }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Order Date:</span>
                <span class="info-value">{{ $order->created_at->format('F d, Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Amount:</span>
                <span class="info-value">£{{ number_format($order->total_amount, 2) }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Current Status:</span>
                <span class="info-value">
                    <span class="status-badge status-{{ $newStatus }}">{{ ucfirst($newStatus) }}</span>
                </span>
            </div>
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

        @if($order->shipping_address)
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

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}/orders" class="button">View Order Details</a>
        </div>

        <div class="footer">
            <p>If you have any questions about your order, please don't hesitate to contact us.</p>
            <p>Thank you for shopping with us!</p>
            <p><strong>{{ config('app.name') }}</strong></p>
        </div>
    </div>
</body>
</html>

