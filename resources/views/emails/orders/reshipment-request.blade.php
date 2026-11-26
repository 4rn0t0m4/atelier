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
        .info-box { background: #fefce8; border: 2px solid #fde68a; border-radius: 8px; padding: 20px; margin: 24px 0; text-align: center; }
        .info-box .label { font-size: 12px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
        .info-box .amount { font-size: 24px; font-weight: 700; color: #92400e; }
        .btn { display: inline-block; background: #92400e; color: white; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; }
        .footer { text-align: center; padding: 20px 30px; background: #f9fafb; font-size: 12px; color: #9ca3af; }
        .footer a { color: #92400e; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Votre colis nous a été retourné</h1>
        <p>Commande #{{ $order->number }}</p>
    </div>

    <div class="content">
        <p class="greeting">Bonjour {{ $order->billing_first_name }},</p>

        <p>
            Votre colis n'a pas été retiré dans le délai prévu par le point relais et nous a été retourné.
            Pas d'inquiétude : votre commande <strong>#{{ $order->number }}</strong> est bien conservée à l'atelier.
        </p>

        <p>
            Pour la recevoir à nouveau, il vous suffit de choisir un nouveau point relais et de régler
            les frais de réexpédition.
        </p>

        <div class="info-box">
            <div class="label">Frais de réexpédition</div>
            <div class="amount">{{ $reshipment->formatted_amount }}</div>
        </div>

        <p style="text-align: center; margin: 24px 0;">
            <a href="{{ $reshipment->url }}" class="btn" style="color: #ffffff;">Choisir mon point relais et payer</a>
        </p>

        <p style="font-size: 13px; color: #6b7280;">
            Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
            <a href="{{ $reshipment->url }}" style="color: #92400e; word-break: break-all;">{{ $reshipment->url }}</a>
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
