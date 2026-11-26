<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; margin: 0; padding: 20px; color: #374151; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; }
        .header { background: #92400e; padding: 24px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 20px; font-weight: 600; }
        .content { padding: 24px 30px; font-size: 14px; }
        .box { background: #f9fafb; border-radius: 8px; padding: 14px; line-height: 1.6; margin: 16px 0; }
        .btn { display: inline-block; background: #92400e; color: white; padding: 10px 24px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Réexpédition réglée — {{ $reshipment->formatted_amount }}</h1>
    </div>
    <div class="content">
        <p>
            <strong>{{ $order->billing_full_name }}</strong> a réglé la réexpédition de la commande
            <strong>#{{ $order->number }}</strong> ({{ $reshipment->payment_method === 'paypal' ? 'PayPal' : 'carte bancaire' }}).
        </p>

        <div class="box">
            <strong>Nouveau point relais</strong><br>
            {{ $reshipment->relay_point_name }}<br>
            {{ $reshipment->relay_point_address }}<br>
            Code : {{ $reshipment->relay_point_code }}
            @if($reshipment->relay_network_label) — {{ $reshipment->relay_network_label }}@endif
        </div>

        <p>La commande est repassée « en cours » : une nouvelle étiquette Boxtal est à créer.</p>

        <p style="text-align: center; margin-top: 20px;">
            <a href="{{ route('admin.orders.show', $order) }}" class="btn" style="color: #ffffff;">Voir la commande</a>
        </p>
    </div>
</div>
</body>
</html>
