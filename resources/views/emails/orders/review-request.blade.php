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
        .btn { display: inline-block; background: #92400e; color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-size: 15px; font-weight: 600; }
        .stars { color: #f59e0b; font-size: 20px; letter-spacing: 2px; margin: 16px 0; }
        .footer { text-align: center; padding: 20px 30px; background: #f9fafb; font-size: 12px; color: #9ca3af; }
        .footer a { color: #92400e; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Votre avis nous intéresse !</h1>
        <p>Commande #{{ $order->number }}</p>
    </div>

    <div class="content">
        <p class="greeting">Bonjour {{ $order->billing_first_name }},</p>

        <p>Vous avez reçu votre commande depuis quelques jours maintenant. Nous espérons que tout vous plaît !</p>

        <p>Pourriez-vous prendre un instant pour donner votre avis sur les produits que vous avez commandés ? Cela aide énormément les autres clients dans leur choix.</p>

        <div style="margin: 24px 0;">
            @foreach($order->items as $item)
                @if($item->product)
                    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 8px;">
                        <tr>
                            @if($item->product->featuredImage)
                                <td width="60" style="padding-right: 14px;">
                                    <img src="{{ url($item->product->featuredImage->url) }}" alt="{{ $item->product_name }}" width="60" height="60" style="border-radius: 8px; object-fit: cover;">
                                </td>
                            @endif
                            <td>
                                <div style="font-size: 14px; font-weight: 500; color: #111827;">{{ $item->product_name }}</div>
                                <div style="font-size: 12px; color: #6b7280;">Quantité : {{ $item->quantity }}</div>
                            </td>
                            <td width="120" style="text-align: right;">
                                <a href="{{ $item->product->url() }}#avis" style="color: #92400e; font-size: 13px; font-weight: 600; text-decoration: none;">Donner mon avis</a>
                            </td>
                        </tr>
                    </table>
                @endif
            @endforeach
        </div>

        <div style="text-align: center; margin: 28px 0;">
            <div class="stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
            <a href="{{ url('/boutique') }}" class="btn" style="color: #ffffff;">Voir la boutique</a>
        </div>

        <p style="font-size: 13px; color: #6b7280; text-align: center; margin-top: 24px;">
            Merci pour votre confiance !
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Atelier d'Aubin — Tous droits réservés</p>
        <p style="margin-top: 4px;">
            <a href="{{ url('/') }}">atelier-aubin.fr</a>
        </p>
    </div>
</div>
</body>
</html>
