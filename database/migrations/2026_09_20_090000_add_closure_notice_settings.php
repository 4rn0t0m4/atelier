<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const KEYS = [
        'closure_notice_enabled',
        'closure_notice_message',
        'closure_notice_until',
    ];

    public function up(): void
    {
        Setting::set('closure_notice_enabled', '1');
        Setting::set('closure_notice_message', "L'atelier sera fermé du 24 au 28 septembre inclus. Les commandes passées durant cette période seront traitées dès notre retour.");
        Setting::set('closure_notice_until', '2026-09-28');
    }

    public function down(): void
    {
        Setting::whereIn('key', self::KEYS)->delete();
    }
};
