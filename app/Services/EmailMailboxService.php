<?php

namespace App\Services;

use App\Models\EmailServerSetting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use IMAP\Connection;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class EmailMailboxService
{
    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function send(User $user, array $recipients, string $subject, string $body, array $attachments = []): void
    {
        $mailbox = $user->emailMailbox;
        if (! $mailbox || blank($mailbox->email) || blank($mailbox->password)) {
            throw new RuntimeException('Connect your cPanel mailbox before sending email.');
        }

        $settings = $this->settings();
        if (blank($settings->smtp_host) || blank($settings->smtp_port)) {
            throw new RuntimeException('The administrator must configure the outgoing SMTP server before email can be sent.');
        }

        $mailer = Mail::build([
            'name' => 'user-mailbox-'.$user->getKey(),
            'transport' => 'smtp',
            'scheme' => $settings->smtp_encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => $settings->smtp_host,
            'port' => (int) $settings->smtp_port,
            'username' => $mailbox->email,
            'password' => $mailbox->password,
            'timeout' => 30,
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost',
        ]);
        $signature = trim((string) $mailbox->signature);
        $messageBody = $signature === ''
            ? $body
            : rtrim($body)."\n\n--\n".$signature;

        try {
            $mailer->raw($messageBody, function (Message $message) use ($user, $mailbox, $recipients, $subject, $attachments): void {
                $message->from($mailbox->email, $user->name)
                    ->subject($subject);

                foreach ($recipients as $recipient) {
                    $message->to($recipient);
                }

                foreach ($attachments as $attachment) {
                    $originalFilename = str_replace('\\', '/', $attachment->getClientOriginalName());
                    $filename = str_replace(["\r", "\n"], '_', basename($originalFilename));
                    $message->attach($attachment->getRealPath(), ['as' => $filename]);
                }
            });
        } catch (TransportExceptionInterface $exception) {
            Log::warning('Outgoing mailbox email failed.', [
                'user_id' => $user->getKey(),
                'exception' => $exception->getMessage(),
            ]);

            throw new RuntimeException('Your email could not be sent. Check your mailbox password and outgoing server settings, then try again.', 0, $exception);
        }
    }

    public function testConnection(User $user): void
    {
        $connection = $this->connect($user);
        imap_close($connection);
    }

    public function messages(User $user): array
    {
        $connection = $this->connect($user);
        try {
            imap_errors();
            $uids = imap_search($connection, 'ALL', SE_UID) ?: [];
            $imapErrors = imap_errors();
            if ($uids === [] && is_array($imapErrors) && $imapErrors !== []) {
                throw new RuntimeException($this->lastError('The inbox could not be read.'));
            }
            rsort($uids, SORT_NUMERIC);
            $messages = [];

            foreach (array_slice($uids, 0, 50) as $uid) {
                $overview = imap_fetch_overview($connection, (string) $uid, FT_UID);
                if (! is_array($overview) || ! isset($overview[0])) {
                    throw new RuntimeException($this->lastError('An email could not be loaded from the inbox.'));
                }

                $item = $overview[0];
                $messages[] = [
                    'uid' => (int) $uid,
                    'subject' => $this->decodeHeader($item->subject ?? '(No subject)'),
                    'from' => $this->decodeHeader($item->from ?? 'Unknown sender'),
                    'to' => $this->decodeHeader($item->to ?? ''),
                    'date' => $item->date ?? '',
                    'seen' => (bool) ($item->seen ?? false),
                    'flagged' => (bool) ($item->flagged ?? false),
                ];
            }

            return $messages;
        } finally {
            imap_close($connection);
        }
    }

    public function message(User $user, int $uid): array
    {
        $connection = $this->connect($user);
        try {
            $overview = imap_fetch_overview($connection, (string) $uid, FT_UID);
            if (! is_array($overview) || ! isset($overview[0])) {
                throw new RuntimeException('That email could not be found in the configured mailbox.');
            }

            $item = $overview[0];
            $structure = imap_fetchstructure($connection, (string) $uid, FT_UID);
            $body = $structure
                ? $this->bodyPart($connection, $uid, $structure)
                : imap_body($connection, (string) $uid, FT_UID);

            if (! is_string($body)) {
                $body = '';
            }

            return [
                'uid' => $uid,
                'subject' => $this->decodeHeader($item->subject ?? '(No subject)'),
                'from' => $this->decodeHeader($item->from ?? 'Unknown sender'),
                'to' => $this->decodeHeader($item->to ?? ''),
                'date' => $item->date ?? '',
                'seen' => (bool) ($item->seen ?? false),
                'body' => trim(strip_tags(html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
            ];
        } finally {
            imap_close($connection);
        }
    }

    public function updateMessage(User $user, int $uid, string $action): void
    {
        $this->updateMessages($user, [$uid], $action);
    }

    /**
     * @param  list<int>  $uids
     */
    public function updateMessages(User $user, array $uids, string $action): void
    {
        if (! in_array($action, ['archive', 'delete', 'read', 'unread', 'flag', 'unflag'], true)) {
            throw new RuntimeException('That email action is not supported.');
        }

        $connection = $this->connect($user);
        try {
            foreach ($uids as $uid) {
                $updated = match ($action) {
                    'read' => imap_setflag_full($connection, (string) $uid, '\\Seen', ST_UID),
                    'unread' => imap_clearflag_full($connection, (string) $uid, '\\Seen', ST_UID),
                    'flag' => imap_setflag_full($connection, (string) $uid, '\\Flagged', ST_UID),
                    'unflag' => imap_clearflag_full($connection, (string) $uid, '\\Flagged', ST_UID),
                    'delete' => imap_setflag_full($connection, (string) $uid, '\\Deleted', ST_UID),
                    'archive' => $this->archiveMessage($connection, $uid),
                };

                if (! $updated) {
                    throw new RuntimeException($this->lastError('The email action could not be completed.'));
                }
            }

            if (in_array($action, ['delete', 'archive'], true) && ! imap_expunge($connection)) {
                throw new RuntimeException($this->lastError('The email action could not be completed.'));
            }
        } finally {
            imap_close($connection);
        }
    }

    private function connect(User $user): Connection
    {
        if (! extension_loaded('imap')) {
            throw new RuntimeException('The PHP IMAP extension is not enabled on this server.');
        }

        $settings = $this->settings();
        $account = $user->emailMailbox;
        if (! $account || blank($account->email) || blank($account->password)) {
            throw new RuntimeException('Add your cPanel mailbox address and password before opening the inbox.');
        }
        if (blank($settings->imap_host) || blank($settings->imap_port)) {
            throw new RuntimeException('Configure the incoming IMAP server before opening the inbox.');
        }

        $security = match ($settings->imap_encryption) {
            'ssl' => 'ssl',
            'tls' => 'tls',
            default => 'notls',
        };
        $mailbox = sprintf('{%s:%d/imap/%s}%s', $settings->imap_host, $settings->imap_port, $security, $settings->imap_folder);
        $connection = @imap_open($mailbox, $account->email, $account->password);

        if (! $connection) {
            throw new RuntimeException($this->lastError('Could not connect to the incoming mail server. Check the IMAP settings.'));
        }

        return $connection;
    }

    private function settings(): EmailServerSetting
    {
        $settings = EmailServerSetting::query()->find('primary');
        if (! $settings) {
            throw new RuntimeException('Configure the incoming IMAP server before opening the inbox.');
        }

        return $settings;
    }

    private function archiveMessage(Connection $connection, int $uid): bool
    {
        $settings = $this->settings();
        $server = sprintf(
            '{%s:%d/imap/%s}',
            $settings->imap_host,
            $settings->imap_port,
            match ($settings->imap_encryption) {
                'ssl' => 'ssl',
                'tls' => 'tls',
                default => 'notls',
            }
        );
        $target = $server.$settings->imap_archive_folder;
        @imap_createmailbox($connection, imap_utf7_encode($target));

        return imap_mail_move($connection, (string) $uid, $target, CP_UID);
    }

    private function bodyPart(Connection $connection, int $uid, object $part, string $partNumber = ''): string
    {
        $type = (int) ($part->type ?? 0);
        if ($type === 1 && isset($part->parts) && is_array($part->parts)) {
            $plain = '';
            $html = '';
            foreach ($part->parts as $index => $child) {
                $number = $partNumber === '' ? (string) ($index + 1) : $partNumber.'.'.($index + 1);
                $content = $this->bodyPart($connection, $uid, $child, $number);
                if ((int) ($child->type ?? 0) === 0) {
                    $subtype = strtolower((string) ($child->subtype ?? 'plain'));
                    if ($subtype === 'plain' && $plain === '') {
                        $plain = $content;
                    } elseif ($subtype === 'html' && $html === '') {
                        $html = $content;
                    }
                } elseif ($content !== '' && $plain === '' && $html === '') {
                    $plain = $content;
                }
            }

            return $plain !== '' ? $plain : $html;
        }

        if ($type !== 0 || strtoupper((string) ($part->disposition ?? '')) === 'ATTACHMENT') {
            return '';
        }

        $section = $partNumber === '' ? '1' : $partNumber;
        $content = imap_fetchbody($connection, (string) $uid, $section, FT_UID);
        if (! is_string($content)) {
            return '';
        }

        return match ((int) ($part->encoding ?? 0)) {
            3 => base64_decode($content, true) ?: '',
            4 => quoted_printable_decode($content),
            default => $content,
        };
    }

    private function decodeHeader(string $value): string
    {
        $parts = imap_mime_header_decode($value);
        if (! is_array($parts)) {
            return imap_utf8($value);
        }

        return collect($parts)
            ->map(function (object $part): string {
                try {
                    return mb_convert_encoding($part->text, 'UTF-8', $part->charset ?: 'UTF-8');
                } catch (\ValueError) {
                    return $part->text;
                }
            })
            ->implode('');
    }

    private function lastError(string $fallback): string
    {
        $error = imap_last_error();

        return is_string($error) && $error !== '' ? mb_substr($error, 0, 300) : $fallback;
    }
}
