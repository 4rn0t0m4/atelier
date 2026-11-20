<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f9fafb; margin: 0; padding: 20px; color: #374151; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; }
        .header { background: #92400e; padding: 24px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 18px; }
        .content { padding: 24px; font-size: 14px; line-height: 1.6; }
        .info { background: #fefce8; border-radius: 8px; padding: 16px; margin: 16px 0; }
        .info p { margin: 6px 0; }
        .info strong { color: #92400e; }
        .footer { padding: 16px 24px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nouvelle connexion à l'admin</h1>
        </div>
        <div class="content">
            <p>Une connexion vient d'avoir lieu sur un compte administrateur de la boutique.</p>
            <div class="info">
                <p><strong>Compte :</strong> {{ $user->email }}</p>
                <p><strong>Appareil :</strong> {{ $device }}</p>
                <p><strong>Adresse IP :</strong> {{ $ip }}</p>
                <p><strong>Date :</strong> {{ $date }}</p>
            </div>
            <p>Si c'est vous, il n'y a rien à faire.</p>
            <p>
                Sinon, changez immédiatement votre mot de passe depuis
                <a href="{{ route('admin.security.index') }}">la page Sécurité de l'admin</a> :
                tous les autres appareils seront déconnectés.
            </p>
        </div>
        <div class="footer">
            Alerte de sécurité — atelier-aubin.fr
        </div>
    </div>
</body>
</html>
