<?php

namespace App\Http\Controllers;

use App\Mail\IntegrationTestMail;
use App\Models\BillingDocument;
use App\Notifications\IntegrationTestNotification;
use App\Services\ChipCollect;
use App\Services\CpanelEmailProvisioner;
use App\Services\MailSettingsService;
use App\Services\ParcelTrackingService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class IntegrationTestController extends Controller
{
    public function cpanel(CpanelEmailProvisioner $cpanel): View
    {
        return view('reports.integrations.cpanel', [
            'cpanelReady' => $cpanel->configured(),
            'host' => config('services.cpanel.host'),
            'port' => config('services.cpanel.port'),
            'usernameConfigured' => filled(config('services.cpanel.username')),
            'domain' => config('services.cpanel.email_domain'),
        ]);
    }

    public function checkCpanel(CpanelEmailProvisioner $cpanel): RedirectResponse
    {
        if (! $cpanel->configured()) {
            return back()->with('cpanel_result', [
                'success' => false,
                'message' => 'Configure CPANEL_HOST, CPANEL_USERNAME, CPANEL_API_TOKEN, and CPANEL_EMAIL_DOMAIN in the server environment, then clear cached configuration.',
            ]);
        }

        try {
            $mailboxEmails = $cpanel->testConnection();
        } catch (\RuntimeException $exception) {
            Log::warning('Configured cPanel connection test failed.', [
                'reason' => $exception->getMessage(),
            ]);

            return back()->with('cpanel_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return back()->with('cpanel_result', [
            'success' => true,
            'message' => 'Connected to cPanel UAPI and verified mailbox access for '.config('services.cpanel.email_domain').'.',
            'mailbox_emails' => $mailboxEmails,
        ]);
    }

    public function notifications(): View
    {
        return view('reports.integrations.notifications', [
            'mailer' => config('mail.default'),
            'fromAddress' => config('mail.from.address'),
        ]);
    }

    public function sendTestNotification(Request $request): RedirectResponse
    {
        $request->user()->notify(new IntegrationTestNotification(
            'Test notification',
            'Your workspace notifications are working.',
            route('notifications.index'),
        ));

        return back()->with('notification_test_result', [
            'success' => true,
            'message' => 'A test notification was sent to your account.',
        ]);
    }

    public function sendTestEmail(Request $request, MailSettingsService $mailSettings): RedirectResponse
    {
        $user = $request->user();

        if (blank($user->email)) {
            return redirect()->route('reports.integrations.notifications')->with('email_test_result', [
                'success' => false,
                'message' => 'Your account does not have an email address to send the test to.',
            ]);
        }

        $mailSettings->apply();
        try {
            Mail::to($user->email)->send(new IntegrationTestMail($user->name));
        } catch (TransportExceptionInterface $exception) {
            Log::warning('Configured SMTP test email failed.', ['exception' => $exception::class]);

            return redirect()->route('reports.integrations.notifications')->with('email_test_result', [
                'success' => false,
                'message' => 'The test email could not be sent. Check the configured mail transport.',
            ]);
        }

        return redirect()->route('reports.integrations.notifications')->with('email_test_result', [
            'success' => true,
            'message' => "A test email was sent to {$user->email}.",
        ]);
    }

    public function emailPreview(): View
    {
        $document = new BillingDocument([
                'number' => 'QUO-000123',
                'type' => 'quotation',
                'customer_name' => 'Sample Customer',
                'customer_email' => 'customer@example.com',
            'total' => 2480,
        ]);
        $document->id = 1;
        $document->exists = true;

        $previews = [
            'quotation' => [
                'label' => 'Quotation',
                'subject' => 'Quotation '.$document->number.' from OMG Computer',
                'html' => view('emails.quotation', compact('document'))->render(),
            ],
            'member_invitation' => [
                'label' => 'Member invitation',
                'subject' => 'Verify your email and set up your OMG Computer account',
                'html' => view('emails.member-invitation', [
                    'recipientName' => 'Sample Member',
                    'invitationUrl' => url('/verify-email/sample-preview'),
                ])->render(),
            ],
            'test_email' => [
                'label' => 'Test email',
                'subject' => 'OMG Computer test email',
                'html' => view('emails.integration-test', ['recipientName' => 'Sample User'])->render(),
            ],
        ];
        $emailHtml = $previews['quotation']['html'];

        return view('reports.integrations.email-preview', compact('document', 'emailHtml', 'previews'));
    }

    public function whatsapp(): View
    {
        return view('reports.integrations.whatsapp', [
            'whatsappReady' => filled(config('services.meta.access_token'))
                && filled(config('services.meta.whatsapp_phone_number_id')),
            'phoneNumberIdConfigured' => filled(config('services.meta.whatsapp_phone_number_id')),
            'apiVersion' => config('services.meta.graph_api_version'),
        ]);
    }

    public function checkWhatsapp(): RedirectResponse
    {
        $accessToken = config('services.meta.access_token');
        $phoneNumberId = config('services.meta.whatsapp_phone_number_id');
        $apiVersion = config('services.meta.graph_api_version');

        if (blank($accessToken) || blank($phoneNumberId)) {
            return back()->with('integration_result', [
                'success' => false,
                'message' => 'Configure META_ACCESS_TOKEN and META_WHATSAPP_PHONE_NUMBER_ID before testing.',
            ]);
        }

        if (! is_string($apiVersion) || ! preg_match('/^v\d+\.\d+$/', $apiVersion)) {
            return back()->with('integration_result', [
                'success' => false,
                'message' => 'The configured Meta Graph API version is invalid. Use a version such as v25.0.',
            ]);
        }

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->get("https://graph.facebook.com/{$apiVersion}/".rawurlencode((string) $phoneNumberId), [
                    'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,platform_type',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Meta WhatsApp connection test could not reach Graph API.', [
                'exception' => $exception::class,
            ]);

            return back()->with('integration_result', [
                'success' => false,
                'message' => 'Could not reach Meta Graph API. Check the server network connection and try again.',
            ]);
        }

        if (! $response->successful()) {
            $message = $response->json('error.message');
            $errorCode = $response->json('error.code');
            $permissionGuidance = (int) $errorCode === 10
                ? ' Grant the Meta app whatsapp_business_management permission, make sure the access token has that permission, and assign the WhatsApp Business Account and phone number to the token owner. For production access, complete any required Meta App Review and Advanced Access approval.'
                : '';

            return back()->with('integration_result', [
                'success' => false,
                'message' => is_string($message) && $message !== ''
                    ? mb_substr($message, 0, 400).$permissionGuidance
                    : "Meta returned HTTP {$response->status()}. Check the access token, phone-number ID, and permissions.".$permissionGuidance,
            ]);
        }

        $phone = $response->json();
        if (! is_array($phone) || ! isset($phone['id'])) {
            return back()->with('integration_result', [
                'success' => false,
                'message' => 'Meta responded, but the response did not contain a WhatsApp phone-number profile.',
            ]);
        }

        return back()->with('integration_result', [
            'success' => true,
            'message' => 'Meta accepted the credentials and returned the WhatsApp phone-number profile.',
            'phone' => [
                'id' => $phone['id'],
                'display_phone_number' => $phone['display_phone_number'] ?? null,
                'verified_name' => $phone['verified_name'] ?? null,
                'quality_rating' => $phone['quality_rating'] ?? null,
                'code_verification_status' => $phone['code_verification_status'] ?? null,
                'platform_type' => $phone['platform_type'] ?? null,
            ],
        ]);
    }

    public function chip(): View
    {
        return view('reports.integrations.chip', [
            'chipConfigured' => (bool) config('services.chip.enabled'),
            'apiUrl' => config('services.chip.api_url'),
            'brandIdConfigured' => filled(config('services.chip.brand_id')),
            'secretKeyConfigured' => filled(config('services.chip.secret_key')),
            'currency' => config('services.chip.currency', 'MYR'),
        ]);
    }

    public function checkChip(ChipCollect $chip): RedirectResponse
    {
        if (! config('services.chip.enabled')) {
            return back()->with('chip_result', [
                'success' => false,
                'message' => 'Configure CHIP_SECRET_KEY and CHIP_BRAND_ID in the server environment, then clear cached configuration.',
            ]);
        }

        try {
            $chip->testConnection();
        } catch (\Throwable $exception) {
            Log::warning('CHIP Collect connection test failed.', ['exception' => $exception::class]);

            return back()->with('chip_result', [
                'success' => false,
                'message' => 'CHIP could not be reached or the configured credentials were rejected. Check CHIP_SECRET_KEY, CHIP_BRAND_ID, CHIP_API_URL, and the server network connection.',
            ]);
        }

        return back()->with('chip_result', [
            'success' => true,
            'message' => 'CHIP Collect accepted the credentials and returned a valid callback public key.',
        ]);
    }

    public function parcelTracking(ParcelTrackingService $tracking): View
    {
        return view('reports.integrations.parcel-tracking', [
            'apiKeyConfigured' => $tracking->isConfigured(),
        ]);
    }

    public function trackParcel(Request $request, ParcelTrackingService $tracking): RedirectResponse
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'min:3', 'max:100'],
        ]);
        try {
            return back()->with('parcel_tracking_result', [
                'success' => true,
                'tracking' => $tracking->track(trim($data['tracking_number'])),
            ]);
        } catch (\RuntimeException $exception) {
            return back()->withInput()->with('parcel_tracking_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
