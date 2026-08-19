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
        .product-name { font-size: 18px; font-weight: 600; color: #92400e; }
        .btn { display: inline-block; background: #92400e; color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-size: 15px; font-weight: 600; }
        .footer { text-align: center; padding: 20px 30px; background: #f9fafb; font-size: 12px; color: #9ca3af; }
        .footer a { color: #92400e; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Bonne nouvelle !</h1>
        <p>Un produit que vous attendiez est de retour</p>
    </div>

    <div class="content">
        <p>Bonjour,</p>

        <p>Le produit <span class="product-name">{{ $product->name }}</span> est de nouveau disponible sur notre boutique.</p>

        <p>N'attendez pas trop, les stocks sont limités !</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ url($product->url()) }}" class="btn">Voir le produit</a>
        </p>
    </div>

    <div class="footer">
        <p>Vous recevez cet email car vous avez demandé à être averti(e) de la remise en stock de ce produit.</p>
        <p><a href="{{ url('/') }}">Atelier d'Aubin</a></p>
    </div>
</div>
</body>
</html>
