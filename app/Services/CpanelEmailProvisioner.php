<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class CpanelEmailProvisioner
{
    public function configured(): bool
    {
        return (bool) config('services.cpanel.enabled');
    }

    public function testConnection(): int
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

        if (! $response->successful() || (int) $response->json('result.status') !== 1) {
            throw new RuntimeException('cPanel UAPI responded but could not list mailboxes. Check the email domain and the API token permissions.');
        }

        $mailboxes = $response->json('result.data');

        return is_array($mailboxes) ? count($mailboxes) : 0;
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

        if (! $response->successful() || (int) $response->json('result.status') !== 1) {
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
}
