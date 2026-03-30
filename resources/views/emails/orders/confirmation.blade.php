<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; margin: 0; padding: 20px; color: #374151; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; }
        .header { background: #92400e; padding: 30px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 22px; font-weight: 600; }
        .header p { color: #fde68a; margin: 8px 0 0; font-size: 14px; }
        .content { padding: 30px; }
        .greeting { font-size: 16px; margin-bottom: 20px; }
        .order-info { background: #fefce8; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        .order-info p { margin: 4px 0; font-size: 14px; }
        .order-info strong { color: #92400e; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { text-align: left; font-size: 12px; text-transform: uppercase; color: #6b7280; padding: 8px 0; border-bottom: 2px solid #e5e7eb; }
        td { padding: 10px 0; font-size: 14px; border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .addon { color: #888; font-size: 12px; }
        .totals { border-top: 2px solid #e5e7eb; padding-top: 12px; }
        .totals p { display: flex; justify-content: space-between; margin: 6px 0; font-size: 14px; }
        .totals .total-line { font-size: 16px; font-weight: 700; color: #92400e; border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 8px; }
        .address-box { background: #f9fafb; border-radius: 8px; padding: 14px; font-size: 13px; line-height: 1.6; margin-bottom: 12px; }
        .address-box h3 { margin: 0 0 8px; font-size: 13px; text-transform: uppercase; color: #6b7280; }
        .footer { text-align: center; padding: 20px 30px; background: #f9fafb; font-size: 12px; color: #9ca3af; }
        .footer a { color: #92400e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Merci pour votre commande !</h1>
            <p>Atelier d'Aubin</p>
        </div>

        <div class="content">
            <p class="greeting">Bonjour {{ $order->billing_first_name }},</p>
            <p>Votre commande a bien été enregistrée et votre paiement confirmé. Voici le récapitulatif :</p>

            <div class="order-info">
                <p><strong>Commande :</strong> #{{ $order->number }}</p>
                <p><strong>Date :</strong> {{ $order->created_at->format('d/m/Y à H:i') }}</p>
                <p><strong>Livraison :</strong> {{ $order->shipping_method }}</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th class="text-right">Qté</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>
                                {{ $item->product_name }}
                                @foreach($item->addons as $addon)
                                    <br><span class="addon">+ {{ $addon->group_name }}: {{ $addon->addon_name }}
                                        @if($addon->price > 0)({{ number_format($addon->price, 2, ',', ' ') }} €)@endif
                                    </span>
                                @endforeach
                            </td>
                            <td class="text-right">{{ $item->quantity }}</td>
                            <td class="text-right">{{ number_format($item->total, 2, ',', ' ') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <p><span>Sous-total</span> <span>{{ number_format($order->subtotal, 2, ',', ' ') }} €</span></p>
                @if($order->discount_total > 0)
                    <p><span>Remise{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span> <span>-{{ number_format($order->discount_total, 2, ',', ' ') }} €</span></p>
                @endif
                <p><span>Livraison</span> <span>{{ $order->shipping_total > 0 ? number_format($order->shipping_total, 2, ',', ' ') . ' €' : 'Gratuit' }}</span></p>
                <p class="total-line"><span>Total</span> <span>{{ number_format($order->total, 2, ',', ' ') }} €</span></p>
            </div>

            <div style="margin-top: 24px;">
                <div class="address-box">
                    <h3>Adresse de facturation</h3>
                    {{ $order->billing_first_name }} {{ $order->billing_last_name }}<br>
                    {{ $order->billing_address_1 }}<br>
                    @if($order->billing_address_2){{ $order->billing_address_2 }}<br>@endif
                    {{ $order->billing_postcode }} {{ $order->billing_city }}
                </div>
                @if($order->shipping_address_1 && $order->shipping_address_1 !== $order->billing_address_1)
                    <div class="address-box">
                        <h3>Adresse de livraison</h3>
                        {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                        {{ $order->shipping_address_1 }}<br>
                        @if($order->shipping_address_2){{ $order->shipping_address_2 }}<br>@endif
                        {{ $order->shipping_postcode }} {{ $order->shipping_city }}
                    </div>
                @endif
                @if($order->relay_point_code)
                    <div class="address-box">
                        <h3>Point relais</h3>
                        Code : {{ $order->relay_point_code }}
                    </div>
                @endif
            </div>

            @if($order->customer_note)
                <div style="margin-top: 16px; padding: 12px; background: #fffbeb; border-radius: 8px; font-size: 13px;">
                    <strong>Note :</strong> {{ $order->customer_note }}
                </div>
            @endif
        </div>

        <div class="footer">
            <p>Atelier d'Aubin — Objets en bois personnalisés</p>
            <p><a href="{{ url('/') }}">atelier-aubin.fr</a></p>
        </div>
    </div>
</body>
</html>
