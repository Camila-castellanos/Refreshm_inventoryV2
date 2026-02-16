<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; }
        .header { background: #f8f9fa; padding: 10px; text-align: center; border-bottom: 2px solid #333; }
        .order-info { margin: 20px 0; }
        .item-list { width: 100%; border-collapse: collapse; }
        .item-list th, .item-list td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .total { font-weight: bold; font-size: 1.2em; text-align: right; margin-top: 20px; }
        .footer { margin-top: 30px; font-size: 0.8em; color: #777; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
            <p>Order #{{ $sale->id }}</p>
        </div>

        <div class="order-info">
            <p>Hi {{ $sale->customer }},</p>
            <p>Thank you for your purchase! We've received your order and are processing it.</p>
        </div>

        <table class="item-list">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td>{{ $item->model }} ({{ $item->manufacturer }})</td>
                    <td>{{ number_format($item->selling_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total">
            <p>Subtotal: {{ number_format($sale->subtotal, 2) }}</p>
            @if($sale->tax > 0)
            <p>Tax: {{ number_format($sale->tax, 2) }}</p>
            @endif
            <p>Total: {{ number_format($sale->total, 2) }}</p>
        </div>

        <div class="footer">
            <p>If you have any questions, please contact us.</p>
            <p>&copy; {{ date('Y') }} SwiftStock. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
