<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; margin: 0; padding: 20px; color: #374151; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; }
        .header { background: #dc2626; padding: 30px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 22px; font-weight: 600; }
        .header p { color: #fecaca; margin: 8px 0 0; font-size: 14px; }
        .content { padding: 30px; }
        .greeting { font-size: 16px; margin-bottom: 20px; }
        .order-info { background: #fef2f2; border-radius: 8px; padding: 16px; margin-bottom: 24px; }
        .order-info p { margin: 4px 0; font-size: 14px; }
        .order-info strong { color: #dc2626; }
        .btn { display: inline-block; background: #92400e; color: white; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; }
        .footer { text-align: center; padding: 20px 30px; background: #f9fafb; font-size: 12px; color: #9ca3af; }
        .footer a { color: #92400e; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Échec de paiement</h1>
        <p>Commande #{{ $order->number }}</p>
    </div>

    <div class="content">
        <p class="greeting">Bonjour {{ $order->billing_first_name }},</p>

        <p>Le paiement de votre commande <strong>#{{ $order->number }}</strong> d'un montant de <strong>{{ number_format($order->total, 2, ',', ' ') }} €</strong> n'a pas pu être traité.</p>

        <div class="order-info">
            <p><strong>Raison possible :</strong> carte refusée, fonds insuffisants ou expiration.</p>
            <p>Votre commande n'a pas été annulée. Vous pouvez réessayer le paiement en retournant sur la boutique.</p>
        </div>

        <p style="text-align: center;">
            <a href="{{ route('shop.index') }}" class="btn" style="color: #ffffff;">Retourner à la boutique</a>
        </p>

        <p style="font-size: 13px; color: #6b7280; margin-top: 24px;">
            Si le problème persiste, n'hésitez pas à nous contacter à
            <a href="mailto:contact@atelier-aubin.fr" style="color: #92400e;">contact@atelier-aubin.fr</a>.
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Atelier d'Aubin — Tous droits réservés</p>
    </div>
</div>
</body>
</html>
