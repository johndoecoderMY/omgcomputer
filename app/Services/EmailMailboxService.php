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

    public function message(User $user, int $uid, bool $allowRemoteImages = false): array
    {
        $connection = $this->connect($user);
        try {
            $overview = imap_fetch_overview($connection, (string) $uid, FT_UID);
            if (! is_array($overview) || ! isset($overview[0])) {
                throw new RuntimeException('That email could not be found in the configured mailbox.');
            }

            $item = $overview[0];
            $structure = imap_fetchstructure($connection, (string) $uid, FT_UID);
            $parts = $structure
                ? $this->bodyParts($connection, $uid, $structure)
                : ['plain' => (string) imap_body($connection, (string) $uid, FT_UID), 'html' => '', 'inline_images' => []];
            $plainBody = trim($parts['plain']);
            $htmlBody = $this->embedInlineImages(trim($parts['html']), $parts['inline_images']);

            if ($htmlBody === '' && $this->looksLikeHtml($plainBody)) {
                $htmlBody = $plainBody;
                $plainBody = '';
            }

            return [
                'uid' => $uid,
                'subject' => $this->decodeHeader($item->subject ?? '(No subject)'),
                'from' => $this->decodeHeader($item->from ?? 'Unknown sender'),
                'to' => $this->decodeHeader($item->to ?? ''),
                'date' => $item->date ?? '',
                'seen' => (bool) ($item->seen ?? false),
                'body' => $plainBody !== '' ? $plainBody : $this->plainTextFromHtml($htmlBody),
                'body_html' => $htmlBody !== '' ? $this->secureHtmlForDisplay($htmlBody, $allowRemoteImages) : null,
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
            $error = $this->lastError('Could not connect to the incoming mail server. Check the IMAP settings.');
            @imap_errors();
            @imap_alerts();

            throw new RuntimeException($error);
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

    /**
     * @return array{plain: string, html: string, inline_images: array<string, string>}
     */
    private function bodyParts(Connection $connection, int $uid, object $part, string $partNumber = ''): array
    {
        $type = (int) ($part->type ?? 0);
        $subtype = strtolower((string) ($part->subtype ?? ''));
        $contentId = trim((string) ($part->id ?? ''), " \t\n\r\0\x0B<>");
        $disposition = strtoupper((string) ($part->disposition ?? ''));

        if ($type === 5 && $contentId !== '' && $disposition !== 'ATTACHMENT') {
            $section = $partNumber === '' ? '1' : $partNumber;
            $content = imap_fetchbody($connection, (string) $uid, $section, FT_UID);
            $content = $this->decodePartContent($content, (int) ($part->encoding ?? 0));
            $mimeSubtype = match ($subtype) {
                'jpg', 'jpeg' => 'jpeg',
                'png', 'gif', 'webp', 'bmp' => $subtype,
                default => null,
            };

            if ($content !== '' && strlen($content) <= 2 * 1024 * 1024 && $mimeSubtype !== null) {
                return [
                    'plain' => '',
                    'html' => '',
                    'inline_images' => [strtolower($contentId) => 'data:image/'.$mimeSubtype.';base64,'.base64_encode($content)],
                ];
            }
        }

        if ($disposition === 'ATTACHMENT' || $this->partFilename($part) !== '') {
            return ['plain' => '', 'html' => '', 'inline_images' => []];
        }

        if ($type === 1 && isset($part->parts) && is_array($part->parts)) {
            $bodies = ['plain' => '', 'html' => '', 'inline_images' => []];
            foreach ($part->parts as $index => $child) {
                $number = $partNumber === '' ? (string) ($index + 1) : $partNumber.'.'.($index + 1);
                $childBodies = $this->bodyParts($connection, $uid, $child, $number);
                foreach (['plain', 'html'] as $format) {
                    if ($bodies[$format] === '' && $childBodies[$format] !== '') {
                        $bodies[$format] = $childBodies[$format];
                    }
                }
                $bodies['inline_images'] = array_merge($bodies['inline_images'], $childBodies['inline_images']);
            }

            return $bodies;
        }

        if ($type !== 0) {
            return ['plain' => '', 'html' => '', 'inline_images' => []];
        }

        $section = $partNumber === '' ? '1' : $partNumber;
        $content = imap_fetchbody($connection, (string) $uid, $section, FT_UID);
        if (! is_string($content)) {
            return ['plain' => '', 'html' => '', 'inline_images' => []];
        }

        $content = $this->decodePartContent($content, (int) ($part->encoding ?? 0));
        $charset = $this->partCharset($part);
        if ($charset !== '' && strcasecmp($charset, 'UTF-8') !== 0) {
            try {
                $content = mb_convert_encoding($content, 'UTF-8', $charset);
            } catch (\ValueError) {
                // Keep the decoded body when a sender declares an unsupported charset.
            }
        }

        return match (strtolower((string) ($part->subtype ?? 'plain'))) {
            'html' => ['plain' => '', 'html' => $content, 'inline_images' => []],
            'plain' => ['plain' => $content, 'html' => '', 'inline_images' => []],
            default => ['plain' => '', 'html' => '', 'inline_images' => []],
        };
    }

    private function decodePartContent(string|false $content, int $encoding): string
    {
        if (! is_string($content)) {
            return '';
        }

        return match ($encoding) {
            3 => base64_decode($content, true) ?: '',
            4 => quoted_printable_decode($content),
            default => $content,
        };
    }

    /**
     * @param  array<string, string>  $inlineImages
     */
    private function embedInlineImages(string $html, array $inlineImages): string
    {
        if ($html === '' || $inlineImages === []) {
            return $html;
        }

        return preg_replace_callback(
            '/cid:([^\'"\s<>]+)/i',
            static function (array $match) use ($inlineImages): string {
                $contentId = strtolower(trim(rawurldecode(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')), '<>'));

                return $inlineImages[$contentId] ?? $match[0];
            },
            $html,
        ) ?? $html;
    }

    private function partFilename(object $part): string
    {
        foreach (['parameters', 'dparameters'] as $property) {
            foreach (($part->{$property} ?? []) as $parameter) {
                if (in_array(strtolower((string) ($parameter->attribute ?? '')), ['filename', 'name'], true)) {
                    return (string) ($parameter->value ?? '');
                }
            }
        }

        return '';
    }

    private function partCharset(object $part): string
    {
        foreach (($part->parameters ?? []) as $parameter) {
            if (strtolower((string) ($parameter->attribute ?? '')) === 'charset') {
                return (string) ($parameter->value ?? '');
            }
        }

        return '';
    }

    private function looksLikeHtml(string $body): bool
    {
        return preg_match('/<(?:!doctype\s+html|html|head|body|style|table|div|p)\b/i', $body) === 1;
    }

    private function plainTextFromHtml(string $html): string
    {
        $html = preg_replace('/<(?:head|style|script)\b[^>]*>.*?<\/(?:head|style|script)\s*>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<\/(?:p|div|tr|li|h[1-6]|br)\s*>/i', "\n", $html) ?? $html;

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function secureHtmlForDisplay(string $html, bool $allowRemoteImages = false): string
    {
        $imageSources = $allowRemoteImages ? 'data: https:' : 'data:';
        $policy = "default-src 'none'; img-src {$imageSources}; style-src 'unsafe-inline'; font-src data:; form-action 'none'; base-uri 'none'; object-src 'none'; frame-src 'none'; connect-src 'none'";
        $meta = '<meta http-equiv="Content-Security-Policy" content="'.e($policy).'">';

        if (preg_match('/<head\b[^>]*>/i', $html, $match, PREG_OFFSET_CAPTURE) === 1) {
            $insertAt = $match[0][1] + strlen($match[0][0]);

            return substr_replace($html, $meta, $insertAt, 0);
        }

        return '<!doctype html><html><head><meta charset="utf-8">'.$meta.'</head><body>'.$html.'</body></html>';
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
