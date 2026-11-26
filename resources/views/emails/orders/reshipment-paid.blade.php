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
        .address-box { background: #f9fafb; border-radius: 8px; padding: 14px; font-size: 13px; line-height: 1.6; margin: 24px 0; }
        .address-box h3 { margin: 0 0 8px; font-size: 13px; text-transform: uppercase; color: #6b7280; }
        .footer { text-align: center; padding: 20px 30px; background: #f9fafb; font-size: 12px; color: #9ca3af; }
        .footer a { color: #92400e; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Réexpédition confirmée</h1>
        <p>Commande #{{ $order->number }}</p>
    </div>

    <div class="content">
        <p class="greeting">Bonjour {{ $order->billing_first_name }},</p>

        <p>
            Nous avons bien reçu votre paiement de <strong>{{ $reshipment->formatted_amount }}</strong>
            pour la réexpédition de votre commande <strong>#{{ $order->number }}</strong>.
        </p>

        <div class="address-box">
            <h3>Nouveau point relais de retrait</h3>
            <strong>{{ $reshipment->relay_point_name }}</strong>
            @if($reshipment->relay_point_address)<br>{{ $reshipment->relay_point_address }}@endif
            @if($reshipment->relay_network_label)<br><span style="color:#6b7280;">Réseau : {{ $reshipment->relay_network_label }}</span>@endif
        </div>

        <p>
            Nous préparons l'envoi de votre colis. Vous recevrez un email avec le numéro de suivi dès qu'il sera expédié.
        </p>

        <p style="font-size: 13px; color: #6b7280; margin-top: 24px;">
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
