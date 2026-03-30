<?php

namespace App\Console\Commands\Migrate;

use App\Models\ContactMessage;

class WpContacts extends WpImportCommand
{
    protected $signature = 'migrate:wp-contacts';
    protected $description = 'Importe les messages de contact depuis Flamingo (Contact Form 7)';

    public function handle(): int
    {
        $this->info('Import des messages de contact (Flamingo)...');
        $this->safeTruncate('contact_messages');

        // Ne récupérer que les messages non-spam
        $messages = $this->wp()
            ->table('posts as p')
            ->leftJoin('postmeta as pm', function ($join) {
                $join->on('p.ID', '=', 'pm.post_id')
                    ->where('pm.meta_key', '=', '_submission_status');
            })
            ->where('p.post_type', 'flamingo_inbound')
            ->where(function ($q) {
                $q->whereNull('pm.meta_value')
                    ->orWhere('pm.meta_value', '!=', 'spam');
            })
            ->select('p.ID', 'p.post_date')
            ->orderBy('p.ID')
            ->get();

        $created = 0;

        foreach ($messages as $msg) {
            $email = $this->getMeta($msg->ID, '_from_email', '');
            $name = $this->getMeta($msg->ID, '_from_name', '');
            $subject = $this->getMeta($msg->ID, '_subject', '');
            $body = $this->getMeta($msg->ID, '_field_your-message', '');

            // Ignorer les messages vides
            if (! $email && ! $body) {
                continue;
            }

            // Nettoyer les placeholders CF7 non résolus
            if ($name === '[your-name]') {
                $name = '';
            }
            if ($subject === '[your-subject]') {
                $subject = '';
            }

            $contact = new ContactMessage();
            $contact->timestamps = false;
            $contact->fill([
                'name' => $name ?: 'Anonyme',
                'email' => $email ?: null,
                'phone' => null,
                'subject' => $subject ?: null,
                'message' => $body,
                'is_read' => true,
            ]);
            $contact->created_at = $msg->post_date;
            $contact->updated_at = $msg->post_date;
            $contact->save();

            $created++;
        }

        $this->printResult('Messages de contact', $created);

        return self::SUCCESS;
    }
}
