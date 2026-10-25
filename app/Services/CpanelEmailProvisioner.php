<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CpanelEmailProvisioner
{
    public function configured(): bool
    {
        return (bool) config('services.cpanel.enabled');
    }

    /**
     * @return list<string>
     */
    public function testConnection(): array
    {
        $host = $this->host();
        $domain = strtolower((string) config('services.cpanel.email_domain'));
        $port = (int) config('services.cpanel.port');
        $url = sprintf('https://%s:%d/execute/Email/list_pops', $host, $port);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'cpanel '.config('services.cpanel.username').':'.config('services.cpanel.api_token'),
            ])->acceptJson()->connectTimeout(5)->timeout(15)->get($url, [
                'domain' => $domain,
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to cPanel UAPI. Check the hostname, port, DNS, and server network access.', previous: $exception);
        }

        if ($response->status() === 401 || $response->status() === 403) {
            throw new RuntimeException('cPanel rejected the API credentials. Check the cPanel username and API token, and confirm the token has access to the Email UAPI.');
        }

        if (! $response->successful() || $this->uapiStatus($response) !== 1) {
            throw new RuntimeException($this->mailboxListFailureMessage($response));
        }

        $mailboxes = $response->json('data', $response->json('result.data', []));

        return collect(is_array($mailboxes) ? $mailboxes : [])
            ->map(fn (mixed $mailbox): mixed => is_array($mailbox) ? ($mailbox['email'] ?? null) : null)
            ->filter(fn (mixed $email): bool => is_string($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false)
            ->map(fn (string $email): string => trim($email))
            ->values()
            ->all();
    }

    /**
     * @return array{email: string, password: string}
     */
    public function provision(string $username): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('cPanel email provisioning is not configured.');
        }

        $host = $this->host();
        $domain = (string) config('services.cpanel.email_domain');
        $port = (int) config('services.cpanel.port');
        $quota = (int) config('services.cpanel.email_quota');

        if (! filter_var('mailbox@'.$domain, FILTER_VALIDATE_EMAIL) || $quota < 0) {
            throw new RuntimeException('The cPanel host, port, email domain, or mailbox quota configuration is invalid.');
        }

        $username = strtolower($username);
        $domain = strtolower($domain);
        $email = $username.'@'.$domain;
        $password = Str::random(48);
        $url = sprintf(
            'https://%s:%d/execute/Email/add_pop',
            $host,
            $port,
        );
        try {
            $response = Http::withHeaders([
                'Authorization' => 'cpanel '.config('services.cpanel.username').':'.config('services.cpanel.api_token'),
            ])->acceptJson()->asForm()->connectTimeout(5)->timeout(20)->post($url, [
                'email' => $username,
                'domain' => $domain,
                'password' => $password,
                'quota' => $quota,
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to cPanel UAPI. Check the host, port, and network access.', previous: $exception);
        }

        if (! $response->successful() || $this->uapiStatus($response) !== 1) {
            throw new RuntimeException('cPanel could not create the mailbox. Check the UAPI settings, domain, and mailbox name.');
        }

        return [
            'email' => $email,
            'password' => $password,
        ];
    }

    private function host(): string
    {
        $host = trim((string) config('services.cpanel.host'));
        $host = preg_replace('#^https?://#i', '', $host);
        $host = rtrim((string) $host, '/');
        $port = (int) config('services.cpanel.port');

        if (
            ! preg_match('/^[A-Za-z0-9.-]+$/', $host)
            || ! filter_var('mailbox@'.config('services.cpanel.email_domain'), FILTER_VALIDATE_EMAIL)
            || blank(config('services.cpanel.username'))
            || blank(config('services.cpanel.api_token'))
            || $port < 1
            || $port > 65535
        ) {
            throw new RuntimeException('The cPanel host, port, username, API token, or email domain configuration is invalid or incomplete.');
        }

        return $host;
    }

    private function mailboxListFailureMessage(Response $response): string
    {
        $details = collect([
            $response->json('errors'),
            $response->json('messages'),
            $response->json('result.errors'),
            $response->json('result.messages'),
        ])->flatten()
            ->filter(fn (mixed $message): bool => is_string($message) && trim($message) !== '')
            ->map(fn (string $message): string => trim($message))
            ->unique()
            ->implode(' ');

        if ($details !== '') {
            $secrets = array_filter([
                (string) config('services.cpanel.api_token'),
                (string) config('services.cpanel.username'),
            ]);
            $details = str_replace($secrets, '[redacted]', $details);
            $details = Str::limit($details, 300);
        } else {
            $details = "HTTP {$response->status()}, UAPI status ".var_export($this->uapiStatus($response), true).'.';
        }

        return 'cPanel could not list mailboxes: '.$details
            .' Verify CPANEL_EMAIL_DOMAIN is a domain configured in this cPanel account (not the mail-server hostname) and that the API token can access Email UAPI.';
    }

    private function uapiStatus(Response $response): ?int
    {
        $status = $response->json('status', $response->json('result.status'));

        return is_numeric($status) ? (int) $status : null;
    }
}
