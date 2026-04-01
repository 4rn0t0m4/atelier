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
        .info { background: #fefce8; border-radius: 8px; padding: 16px; margin-bottom: 20px; font-size: 14px; }
        .info p { margin: 6px 0; }
        .info strong { color: #92400e; }
        .message-box { background: #f9fafb; border-radius: 8px; padding: 16px; font-size: 14px; line-height: 1.6; white-space: pre-wrap; }
        .footer { padding: 16px 24px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nouveau message de contact</h1>
        </div>
        <div class="content">
            <div class="info">
                <p><strong>Nom :</strong> {{ $contact->name }}</p>
                <p><strong>E-mail :</strong> {{ $contact->email }}</p>
                @if($contact->phone)
                    <p><strong>Téléphone :</strong> {{ $contact->phone }}</p>
                @endif
                @if($contact->subject)
                    <p><strong>Sujet :</strong> {{ $contact->subject }}</p>
                @endif
            </div>
            <div class="message-box">{{ $contact->message }}</div>
        </div>
        <div class="footer">
            Envoyé depuis le formulaire de contact — atelier-aubin.fr
        </div>
    </div>
</body>
</html>
