<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; margin: 0; padding: 20px; color: #374151; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; }
        .header { background: #92400e; padding: 24px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 18px; }
        .content { padding: 24px; }
        .info { background: #fefce8; border-radius: 8px; padding: 14px; margin-bottom: 20px; font-size: 14px; }
        .info p { margin: 4px 0; }
        .info strong { color: #92400e; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 6px 0; color: #6b7280; border-bottom: 2px solid #e5e7eb; font-size: 11px; text-transform: uppercase; }
        td { padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .addon { color: #888; font-size: 11px; }
        .total { font-size: 18px; font-weight: 700; color: #92400e; text-align: center; margin: 20px 0; }
        .btn { display: inline-block; background: #92400e; color: white; text-decoration: none; padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nouvelle commande #{{ $order->number }}</h1>
        </div>
        <div class="content">
            <div class="info">
                <p><strong>Client :</strong> {{ $order->billing_first_name }} {{ $order->billing_last_name }}</p>
                <p><strong>Email :</strong> {{ $order->billing_email }}</p>
                @if($order->billing_phone)<p><strong>Tél :</strong> {{ $order->billing_phone }}</p>@endif
                <p><strong>Livraison :</strong> {{ $order->shipping_method }}</p>
                <p><strong>Date :</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <table>
                <thead>
                    <tr><th>Produit</th><th class="text-right">Qté</th><th class="text-right">Total</th></tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>
                                {{ $item->product_name }}
                                @foreach($item->addons as $addon)
                                    <br><span class="addon">+ {{ $addon->addon_label }}: {{ $addon->addon_value }}
                                        @if($addon->addon_price > 0)({{ number_format($addon->addon_price, 2, ',', ' ') }} €)@endif
                                    </span>
                                @endforeach
                            </td>
                            <td class="text-right">{{ $item->quantity }}</td>
                            <td class="text-right">{{ number_format($item->total, 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="total">Total : {{ number_format($order->total, 2, ',', ' ') }} €</div>

            @if($order->customer_note)
                <div style="padding: 10px; background: #fffbeb; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
                    <strong>Note client :</strong> {{ $order->customer_note }}
                </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn" style="color: #ffffff;">Voir la commande</a>
            </div>
        </div>
    </div>
</body>
</html>
