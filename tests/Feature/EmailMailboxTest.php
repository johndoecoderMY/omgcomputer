<?php

namespace Tests\Feature;

use App\Mail\IntegrationTestMail;
use App\Models\EmailServerSetting;
use App\Models\User;
use App\Services\EmailMailboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Mailer;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailMailboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_encrypted_smtp_settings_and_send_a_test_email(): void
    {
        $admin = $this->admin();
        Mail::fake();

        $this->actingAs($admin)->put(route('email.settings.update'), $this->settingsData())
            ->assertRedirect();

        $settings = EmailServerSetting::query()->findOrFail('primary');
        $this->assertSame('smtp.example.test', $settings->smtp_host);
        $this->assertSame('smtp-secret', $settings->smtp_password);
        $this->assertNotSame('smtp-secret', DB::table('email_server_settings')->value('smtp_password'));
        $this->assertSame('smtp.example.test', config('mail.mailers.smtp.host'));
        $this->assertSame('support@example.test', config('mail.from.address'));

        $preservedPasswordData = $this->settingsData();
        $preservedPasswordData['smtp_password'] = '';
        $this->actingAs($admin)->put(route('email.settings.update'), $preservedPasswordData)
            ->assertRedirect();
        $this->assertSame('smtp-secret', EmailServerSetting::query()->findOrFail('primary')->smtp_password);

        $this->actingAs($admin)->get(route('email.settings'))
            ->assertOk()
            ->assertSee('Email server settings')
            ->assertSee('Saved; leave blank to keep current password')
            ->assertDontSee('smtp-secret')
            ->assertDontSee('imap-secret');

        $this->actingAs($admin)->post(route('email.settings.test-smtp'))
            ->assertRedirect()
            ->assertSessionHas('email_settings_result.success', true);

        Mail::assertSent(IntegrationTestMail::class, fn (IntegrationTestMail $mail): bool => $mail->hasTo($admin->email));
    }

    public function test_admin_can_view_imap_messages_and_email_details(): void
    {
        $admin = $this->admin();
        $messages = [[
            'uid' => 25,
            'subject' => 'Repair booking confirmation',
            'from' => 'Customer <customer@example.test>',
            'date' => 'Wed, 21 Oct 2026 10:00:00 +0800',
            'seen' => false,
        ]];

        $this->mock(EmailMailboxService::class, function (MockInterface $mock) use ($admin, $messages): void {
            $mock->shouldReceive('messages')->once()->with($admin)->andReturn($messages);
            $mock->shouldReceive('message')->once()->with($admin, 25)->andReturn([
                ...$messages[0],
                'to' => 'support@example.test',
                'body' => 'Your repair booking is confirmed.',
            ]);
            $mock->shouldReceive('updateMessage')->once()->with($admin, 25, 'archive');
        });

        $this->actingAs($admin)->get(route('email.inbox'))
            ->assertOk()
            ->assertSee('Repair booking confirmation')
            ->assertSee('Customer <customer@example.test>');

        $this->actingAs($admin)->get(route('email.show', 25))
            ->assertOk()
            ->assertSee('Your repair booking is confirmed.')
            ->assertSee('Archive')
            ->assertSee('Delete');

        $this->actingAs($admin)->post(route('email.action', [25, 'archive']))
            ->assertRedirect(route('email.inbox'))
            ->assertSessionHas('mailbox_success', 'Email archived.');
    }

    public function test_user_can_poll_their_mailbox_for_new_messages(): void
    {
        $admin = $this->admin();
        $messages = [[
            'uid' => 27,
            'subject' => 'New booking request',
            'from' => 'Customer <customer@example.test>',
            'to' => 'support@example.test',
            'date' => 'Wed, 21 Oct 2026 10:00:00 +0800',
            'seen' => false,
            'flagged' => true,
        ]];

        $this->mock(EmailMailboxService::class, function (MockInterface $mock) use ($admin, $messages): void {
            $mock->shouldReceive('messages')->once()->with($admin)->andReturn($messages);
        });

        $this->actingAs($admin)->getJson(route('email.poll'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('messages.0.uid', 27)
            ->assertJsonPath('messages.0.flagged', true)
            ->assertJsonStructure(['messages', 'checked_at']);
    }

    public function test_user_can_open_compose_and_send_with_an_attachment(): void
    {
        $admin = $this->admin();
        $mailbox = $admin->emailMailbox()->create([
            'email' => 'admin@example.test',
            'password' => 'mailbox-secret',
        ]);
        $attachment = UploadedFile::fake()->create('quote.pdf', 32, 'application/pdf');

        $this->mock(EmailMailboxService::class, function (MockInterface $mock) use ($admin, $attachment): void {
            $mock->shouldReceive('send')->once()->with(
                $admin,
                ['customer@example.test', 'manager@example.test'],
                'Repair quote',
                'The quote is attached.',
                [$attachment],
            );
        });

        $this->actingAs($admin)->get(route('email.compose'))
            ->assertOk()
            ->assertSee('New message')
            ->assertSee('admin@example.test')
            ->assertSee('Attach files');

        $this->actingAs($admin)->post(route('email.send'), [
            'recipients' => ['customer@example.test', 'manager@example.test'],
            'subject' => 'Repair quote',
            'body' => 'The quote is attached.',
            'attachments' => [$attachment],
        ])
            ->assertRedirect(route('email.inbox'))
            ->assertSessionHas('mailbox_success', 'Email sent successfully.');
    }

    public function test_composing_rejects_invalid_recipients_and_disallowed_attachments(): void
    {
        $admin = $this->admin();
        $this->mock(EmailMailboxService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('send');
        });

        $this->actingAs($admin)->from(route('email.compose'))->post(route('email.send'), [
            'recipients' => ['not-an-email'],
            'subject' => 'Hello',
            'body' => 'Message',
            'attachments' => [UploadedFile::fake()->create('script.php', 2, 'text/plain')],
        ])->assertRedirect(route('email.compose'))
            ->assertSessionHasErrors(['recipients.0', 'attachments.0']);

        $this->actingAs($admin)->from(route('email.compose'))->post(route('email.send'), [
            'recipients' => ['customer@example.test', 'CUSTOMER@example.test'],
            'subject' => 'Hello',
            'body' => 'Message',
        ])->assertRedirect(route('email.compose'))
            ->assertSessionHasErrors('recipients.1');
    }

    public function test_outgoing_mail_uses_the_users_mailbox_smtp_credentials(): void
    {
        $admin = $this->admin();
        $admin->emailMailbox()->create([
            'email' => 'admin@example.test',
            'password' => 'personal-mail-secret',
            'signature' => "Jane Doe\nCustomer Support",
        ]);
        $settings = new EmailServerSetting($this->settingsData());
        $settings->id = 'primary';
        $settings->save();

        $attachment = UploadedFile::fake()->create('document.pdf', 12, 'application/pdf');
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('from')->once()->with('admin@example.test', $admin->name)->andReturnSelf();
        $message->shouldReceive('to')->once()->with('customer@example.test')->andReturnSelf();
        $message->shouldReceive('to')->once()->with('manager@example.test')->andReturnSelf();
        $message->shouldReceive('subject')->once()->with('A document')->andReturnSelf();
        $message->shouldReceive('attach')->once()->with(
            $attachment->getRealPath(),
            ['as' => 'document.pdf'],
        )->andReturnSelf();

        $mailer = Mockery::mock(Mailer::class);
        $mailer->shouldReceive('raw')->once()
            ->with("Please see attached.\n\n--\nJane Doe\nCustomer Support", Mockery::type(\Closure::class))
            ->andReturnUsing(function (string $body, \Closure $callback) use ($message): void {
                $callback($message);
            });

        Mail::shouldReceive('build')
            ->once()
            ->with(Mockery::on(function (array $config) use ($admin): bool {
                $this->assertSame('user-mailbox-'.$admin->id, $config['name']);
                $this->assertSame('smtp.example.test', $config['host']);
                $this->assertSame(587, $config['port']);
                $this->assertSame('admin@example.test', $config['username']);
                $this->assertSame('personal-mail-secret', $config['password']);
                $this->assertSame(parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost', $config['local_domain']);

                return true;
            }))
            ->andReturn($mailer);

        app(EmailMailboxService::class)->send(
            $admin,
            ['customer@example.test', 'manager@example.test'],
            'A document',
            'Please see attached.',
            [$attachment],
        );
    }

    public function test_user_can_apply_bulk_actions_to_selected_messages(): void
    {
        $admin = $this->admin();

        $this->mock(EmailMailboxService::class, function (MockInterface $mock) use ($admin): void {
            $mock->shouldReceive('updateMessages')->once()->with($admin, [25, 26], 'read');
        });

        $this->actingAs($admin)->post(route('email.bulk-action'), [
            'uids' => ['25', '26'],
            'action' => 'read',
        ])
            ->assertRedirect(route('email.inbox'))
            ->assertSessionHas('mailbox_success', '2 email(s) updated.');
    }

    public function test_bulk_actions_reject_duplicate_or_unsupported_message_ids(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->from(route('email.inbox'))->post(route('email.bulk-action'), [
            'uids' => [25, 25],
            'action' => 'read',
        ])->assertRedirect(route('email.inbox'))
            ->assertSessionHasErrors('uids.1');

        $this->actingAs($admin)->from(route('email.inbox'))->post(route('email.bulk-action'), [
            'uids' => [25],
            'action' => 'snooze',
        ])->assertRedirect(route('email.inbox'))
            ->assertSessionHasErrors('action');
    }

    public function test_admin_can_test_imap_connection(): void
    {
        $admin = $this->admin();
        $this->mock(EmailMailboxService::class, function (MockInterface $mock) use ($admin): void {
            $mock->shouldReceive('testConnection')->once()->with($admin);
        });

        $this->actingAs($admin)->post(route('email.settings.test-imap'))
            ->assertRedirect()
            ->assertSessionHas('imap_test_result.success', true);
    }

    public function test_each_user_can_access_only_their_own_mailbox_settings_and_inbox(): void
    {
        Role::findOrCreate('member', 'web');
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->mock(EmailMailboxService::class, function (MockInterface $mock) use ($member): void {
            $mock->shouldReceive('messages')->twice()->with($member)->andReturn([]);
        });

        $this->actingAs($member)
            ->get(route('email.inbox'))
            ->assertOk()
            ->assertSee('My Email Inbox');
        $this->actingAs($member)
            ->getJson(route('email.poll'))
            ->assertOk()
            ->assertJsonPath('messages', []);
        $this->actingAs($member)
            ->get(route('email.mailbox-settings'))
            ->assertOk()
            ->assertSee('My cPanel mailbox')
            ->assertSee('Email signature');
        $this->actingAs($member)
            ->get(route('email.compose'))
            ->assertOk()
            ->assertSee('New message')
            ->assertSee('Configure mailbox');
        $this->actingAs($member)
            ->get(route('email.settings'))
            ->assertForbidden();

        $this->actingAs($member)->put(route('email.mailbox-settings.update'), [
            'email' => 'member@example.test',
            'password' => 'personal-mail-secret',
            'signature' => "Jane Doe\nCustomer Support",
        ])->assertRedirect();

        $otherMember = User::factory()->create();
        $otherMember->assignRole('member');
        $storedMailbox = User::query()->findOrFail($member->id)->emailMailbox;
        $this->assertSame('member@example.test', $storedMailbox->email);
        $this->assertSame('personal-mail-secret', $storedMailbox->password);
        $this->assertSame("Jane Doe\nCustomer Support", $storedMailbox->signature);
        $this->assertNotSame('personal-mail-secret', DB::table('user_email_mailboxes')->where('user_id', $member->id)->value('password'));
        $this->assertNull($otherMember->emailMailbox);
        $this->actingAs(User::query()->findOrFail($member->id))
            ->get(route('email.compose'))
            ->assertOk()
            ->assertSee('Your signature will be added when you send:')
            ->assertSee('Jane Doe')
            ->assertSee('Customer Support');

        $this->actingAs(User::query()->findOrFail($member->id))
            ->put(route('email.mailbox-settings.update'), [
                'email' => 'member@example.test',
                'password' => '',
                'signature' => "Jane Doe\nCustomer Support",
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
        $this->assertSame('personal-mail-secret', User::query()->findOrFail($member->id)->emailMailbox->password);
    }

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function settingsData(): array
    {
        return [
            'smtp_host' => 'smtp.example.test',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => 'smtp-user',
            'smtp_password' => 'smtp-secret',
            'from_address' => 'support@example.test',
            'from_name' => 'OMG Computer',
            'imap_host' => 'imap.example.test',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'imap_folder' => 'INBOX',
            'imap_archive_folder' => 'Archive',
        ];
    }
}
