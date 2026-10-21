<?php

namespace App\Services;

use App\Models\EmailServerSetting;

class MailSettingsService
{
    public function apply(): void
    {
        $settings = EmailServerSetting::query()->find('primary');
        if (! $settings || blank($settings->smtp_host)) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $settings->smtp_encryption === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => $settings->smtp_host,
            'mail.mailers.smtp.port' => $settings->smtp_port,
            'mail.mailers.smtp.username' => $settings->smtp_username,
            'mail.mailers.smtp.password' => $settings->smtp_password,
            'mail.mailers.smtp.auto_tls' => $settings->smtp_encryption === 'tls',
            'mail.from.address' => $settings->from_address,
            'mail.from.name' => $settings->from_name,
        ]);

        app('mail.manager')->purge('smtp');
    }
}
