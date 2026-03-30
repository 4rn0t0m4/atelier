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
        .tracking-box { background: #fefce8; border: 2px solid #fde68a; border-radius: 8px; padding: 20px; margin: 24px 0; text-align: center; }
        .tracking-box .label { font-size: 12px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
        .tracking-box .carrier { font-size: 16px; font-weight: 600; color: #92400e; margin-bottom: 8px; }
        .tracking-box .number { font-size: 20px; font-weight: 700; color: #111827; letter-spacing: 1px; }
        .btn { display: inline-block; background: #92400e; color: white; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { text-align: left; font-size: 12px; text-transform: uppercase; color: #6b7280; padding: 8px 0; border-bottom: 2px solid #e5e7eb; }
        td { padding: 10px 0; font-size: 14px; border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .address-box { background: #f9fafb; border-radius: 8px; padding: 14px; font-size: 13px; line-height: 1.6; margin-bottom: 24px; }
        .address-box h3 { margin: 0 0 8px; font-size: 13px; text-transform: uppercase; color: #6b7280; }
        .footer { text-align: center; padding: 20px 30px; background: #f9fafb; font-size: 12px; color: #9ca3af; }
        .footer a { color: #92400e; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Votre commande est en route !</h1>
        <p>Commande #{{ $order->number }}</p>
    </div>

    <div class="content">
        <p class="greeting">Bonjour {{ $order->billing_first_name }},</p>

        <p>Bonne nouvelle ! Votre commande <strong>#{{ $order->number }}</strong> a été expédiée.</p>

        @if($order->tracking_number)
            <div class="tracking-box">
                @if($order->tracking_carrier)
                    <div class="label">Transporteur</div>
                    <div class="carrier">{{ $order->tracking_carrier }}</div>
                @endif
                <div class="label">Numéro de suivi</div>
                <div class="number">{{ $order->tracking_number }}</div>
            </div>

            @php
                $trackingUrl = $order->tracking_url ?: match(strtolower($order->tracking_carrier ?? '')) {
                    'colissimo' => 'https://www.laposte.fr/outils/suivre-vos-envois?code=' . $order->tracking_number,
                    'chronopost' => 'https://www.chronopost.fr/tracking-cxf/tracking-cxf-web/suivi?language=fr&parcelNumber=' . $order->tracking_number,
                    'mondial relay', 'mondialrelay' => 'https://www.mondialrelay.fr/suivi-de-colis/?NumeroExpedition=' . $order->tracking_number,
                    default => null,
                };
            @endphp

            @if($trackingUrl)
                <p style="text-align: center; margin-bottom: 24px;">
                    <a href="{{ $trackingUrl }}" class="btn" style="color: #ffffff;">Suivre mon colis</a>
                </p>
            @endif
        @endif

        <h3 style="font-size: 14px; margin-bottom: 12px;">Détail de la commande</h3>
        <table>
            <thead>
                <tr><th>Produit</th><th class="text-right">Total</th></tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }} × {{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->total, 2, ',', ' ') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="address-box">
            @if(in_array($order->shipping_key, ['boxtal', 'boxtal_intl']) && $order->relay_point_code)
                <h3>Point relais de retrait</h3>
                {{ $order->shipping_address_1 }}
                <br>{{ $order->shipping_postcode }} {{ $order->shipping_city }}
                <br>Code : {{ $order->relay_point_code }}
            @else
                <h3>Adresse de livraison</h3>
                {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                {{ $order->shipping_address_1 }}
                @if($order->shipping_address_2)<br>{{ $order->shipping_address_2 }}@endif
                <br>{{ $order->shipping_postcode }} {{ $order->shipping_city }}
            @endif
        </div>

        <p style="font-size: 13px; color: #6b7280;">
            Une question ? Contactez-nous à
            <a href="mailto:contact@atelier-aubin.fr" style="color: #92400e;">contact@atelier-aubin.fr</a>
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Atelier d'Aubin — Tous droits réservés</p>
    </div>
</div>
</body>
</html>
