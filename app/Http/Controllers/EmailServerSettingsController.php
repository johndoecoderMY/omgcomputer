<?php

namespace App\Http\Controllers;

use App\Mail\IntegrationTestMail;
use App\Models\EmailServerSetting;
use App\Services\EmailMailboxService;
use App\Services\MailSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailServerSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = EmailServerSetting::query()->find('primary');

        return view('email.settings', [
            'settings' => $settings ?? new EmailServerSetting([
                'smtp_encryption' => 'tls',
                'imap_encryption' => 'ssl',
                'imap_folder' => 'INBOX',
                'imap_archive_folder' => 'Archive',
            ]),
            'smtpPasswordConfigured' => filled($settings?->smtp_password),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'smtp_host' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9.-]+$/'],
            'smtp_port' => ['nullable', 'required_with:smtp_host', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:1000'],
            'from_address' => ['nullable', 'required_with:smtp_host', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'imap_host' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9.-]+$/'],
            'imap_port' => ['nullable', 'required_with:imap_host', 'integer', 'min:1', 'max:65535'],
            'imap_encryption' => ['required', Rule::in(['tls', 'ssl', 'none'])],
            'imap_folder' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'imap_archive_folder' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\/-]+$/'],
        ]);

        $settings = EmailServerSetting::query()->firstOrNew(['id' => 'primary']);
        foreach ($data as $key => $value) {
            if (str_ends_with($key, '_password') && blank($value)) {
                continue;
            }
            $settings->{$key} = $value;
        }
        $settings->save();

        app(MailSettingsService::class)->apply();

        return back()->with('email_settings_result', [
            'success' => true,
            'message' => 'Email server settings saved. Passwords are stored encrypted and are not displayed.',
        ]);
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (blank($user->email)) {
            return back()->with('email_settings_result', [
                'success' => false,
                'message' => 'Your account does not have an email address to receive a test message.',
            ]);
        }

        if (blank(EmailServerSetting::query()->find('primary')?->smtp_host)) {
            return back()->with('email_settings_result', [
                'success' => false,
                'message' => 'Save an SMTP host and sender address before sending a test email.',
            ]);
        }

        app(MailSettingsService::class)->apply();
        try {
            Mail::to($user->email)->send(new IntegrationTestMail($user->name));
        } catch (TransportExceptionInterface $exception) {
            Log::warning('Configured SMTP test email failed.', ['exception' => $exception::class]);

            return back()->with('email_settings_result', [
                'success' => false,
                'message' => 'The SMTP test email could not be sent. Check the server, port, encryption, and credentials.',
            ]);
        }

        return back()->with('email_settings_result', [
            'success' => true,
            'message' => "A test email was sent to {$user->email}.",
        ]);
    }

    public function testImap(Request $request, EmailMailboxService $mailbox): RedirectResponse
    {
        try {
            $mailbox->testConnection($request->user());
        } catch (RuntimeException $exception) {
            Log::warning('Configured IMAP connection test failed.', ['reason' => $exception->getMessage()]);

            return back()->with('imap_test_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return back()->with('imap_test_result', [
            'success' => true,
            'message' => 'Connected to the configured IMAP mailbox.',
        ]);
    }
}
