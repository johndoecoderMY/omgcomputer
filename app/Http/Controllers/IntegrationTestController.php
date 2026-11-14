<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmedMail;
use App\Mail\IntegrationTestMail;
use App\Models\BillingDocument;
use App\Models\Booking;
use App\Models\ChipSetting;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use App\Notifications\IntegrationTestNotification;
use App\Notifications\WorkspaceNotification;
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

        $bookingDateTime = now('Asia/Kuala_Lumpur')->addDays(2)->setTime(11, 0);
        $booking = new Booking([
            'reference' => 'BK-PREVIEW123',
            'customer_name' => 'Jamie Lee',
            'customer_email' => 'jamie@example.com',
            'customer_phone' => '+60123456789',
            'service_name' => 'Computer/PC Troubleshooting',
            'starts_at' => $bookingDateTime,
        ]);
        $bookingMail = new BookingConfirmedMail($booking);
        $ticket = new RepairTicket(['ticket_number' => 'RT-2026-000123']);
        $ticket->id = 123;
        $project = new Project(['name' => 'Website refresh']);
        $project->id = 456;
        $sampleStaff = new User(['name' => 'Sample Staff', 'email' => 'staff@example.com']);
        $workspacePreview = static function (string $label, string $title, string $message, string $url) use ($sampleStaff): array {
            $mail = (new WorkspaceNotification($title, $message, $url))->toMail($sampleStaff);

            return [
                'label' => $label,
                'subject' => $title,
                'html' => (string) $mail->render(),
            ];
        };

        $previews = [
            'quotation' => [
                'label' => 'Quotation',
                'subject' => 'Quotation '.$document->number.' from OMG Computer',
                'html' => view('emails.quotation', compact('document'))->render(),
            ],
            'booking_confirmation' => [
                'label' => 'Booking confirmed (customer)',
                'subject' => $bookingMail->envelope()->subject,
                'html' => $bookingMail->render(),
            ],
            'booking_received' => $workspacePreview(
                'New booking received (staff)',
                'New booking received',
                'Jamie Lee booked Computer/PC Troubleshooting for '.$bookingDateTime->format('M j, Y g:i A').'.',
                route('bookings.index'),
            ),
            'booking_updated' => $workspacePreview(
                'Booking updated (staff)',
                'Booking updated',
                'Booking BK-PREVIEW123 for Jamie Lee was updated to Confirmed.',
                route('bookings.index'),
            ),
            'booking_assigned' => $workspacePreview(
                'Booking assigned (staff)',
                'Booking assigned to you',
                'Booking BK-PREVIEW123 for Jamie Lee is assigned to you.',
                route('bookings.index'),
            ),
            'ticket_created' => $workspacePreview(
                'Ticket created (staff)',
                'Repair ticket created',
                'Repair ticket '.$ticket->ticket_number.' was created.',
                route('tickets.show', $ticket),
            ),
            'ticket_assigned' => $workspacePreview(
                'Ticket assigned (staff)',
                'Repair ticket assigned',
                'You have been assigned repair ticket '.$ticket->ticket_number.'.',
                route('tickets.show', $ticket),
            ),
            'ticket_updated' => $workspacePreview(
                'Ticket status updated (staff)',
                'Repair ticket updated',
                'Repair ticket '.$ticket->ticket_number.' was updated to In repair.',
                route('tickets.show', $ticket),
            ),
            'ticket_details_updated' => $workspacePreview(
                'Ticket details updated (staff)',
                'Repair ticket details updated',
                'Repair ticket '.$ticket->ticket_number.' was updated.',
                route('tickets.show', $ticket),
            ),
            'ticket_step_added' => $workspacePreview(
                'Repair step added (staff)',
                'Repair ticket update',
                'Repair ticket '.$ticket->ticket_number.': Replace charging port.',
                route('tickets.show', $ticket),
            ),
            'project_created' => $workspacePreview(
                'Project created (staff)',
                'Project created',
                sprintf('Project “%s” was created.', $project->name),
                route('projects.show', $project),
            ),
            'project_updated' => $workspacePreview(
                'Project updated (staff)',
                'Project updated',
                sprintf('Project “%s” was updated.', $project->name),
                route('projects.show', $project),
            ),
            'project_event' => $workspacePreview(
                'Project event scheduled (staff)',
                'Project event scheduled',
                '“Design review” was scheduled for Website refresh on '.now()->addDays(2)->format('M j, Y g:i A').'.',
                route('projects.show', $project),
            ),
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

        return view('reports.integrations.email-preview', compact('previews'));
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
        $chip = app(ChipCollect::class);
        $testMode = $chip->isTestMode();

        return view('reports.integrations.chip', [
            'chipConfigured' => $chip->isConfigured(),
            'testMode' => $testMode,
            'apiUrl' => config('services.chip.api_url'),
            'brandIdConfigured' => filled($chip->brandId()),
            'secretKeyConfigured' => $testMode
                ? filled(config('services.chip.test_secret_key'))
                : filled(config('services.chip.secret_key')),
            'currency' => config('services.chip.currency', 'MYR'),
        ]);
    }

    public function updateChipMode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'test_mode' => ['required', 'boolean'],
        ]);
        ChipSetting::setTestMode((bool) $data['test_mode']);

        return redirect()->route('reports.integrations.chip')->with('chip_mode_saved', true);
    }

    public function checkChip(ChipCollect $chip): RedirectResponse
    {
        if (! $chip->isConfigured()) {
            return back()->with('chip_result', [
                'success' => false,
                'message' => 'Configure the secret key and brand ID for the selected mode in the server environment.',
            ]);
        }

        try {
            $chip->testConnection();
        } catch (\Throwable $exception) {
            Log::warning('CHIP Collect connection test failed.', ['exception' => $exception::class]);

            return back()->with('chip_result', [
                'success' => false,
                'message' => 'CHIP could not be reached or the credentials for the selected mode were rejected. Check the matching CHIP secret key and brand ID, CHIP_API_URL, and the server network connection.',
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
