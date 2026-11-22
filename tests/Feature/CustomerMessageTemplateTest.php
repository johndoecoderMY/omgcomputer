<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\CustomerMessageTemplate;
use App\Models\User;
use Database\Seeders\CustomerMessageTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerMessageTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_preview_edit_and_delete_a_customer_message_template(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('customer-messages.store'), [
            'name' => 'Repair status update',
            'stage' => 'intake',
            'channel' => 'whatsapp',
            'language' => 'en',
            'subject' => '',
            'body' => 'Hi {{customer_name}}, your device is with us under {{ticket_number}}.',
            'is_active' => '1',
        ])->assertRedirect();

        $template = CustomerMessageTemplate::query()->firstOrFail();
        $this->assertSame('repair-status-update-'.substr($template->slug, -6), $template->slug);
        $this->get(route('customer-messages.show', $template))
            ->assertOk()
            ->assertSee('Preview values are examples')
            ->assertSee('Hi Avery, your device is with us under RT-2026-000123.')
            ->assertSee('Copy complete message')
            ->assertSee('Copy for WhatsApp')
            ->assertSee('Open WhatsApp draft');

        $this->put(route('customer-messages.update', $template), [
            'name' => 'Repair update',
            'stage' => 'diagnosis',
            'channel' => 'email',
            'language' => 'ms',
            'subject' => 'Update for {{ticket_number}}',
            'body' => 'We have completed the diagnosis for {{device_model}}.',
        ])->assertRedirect(route('customer-messages.show', $template));
        $this->assertSame('Repair update', $template->fresh()->name);
        $this->assertSame('ms', $template->fresh()->language);
        $this->assertFalse($template->fresh()->is_active);

        $this->delete(route('customer-messages.destroy', $template))
            ->assertRedirect(route('customer-messages.index'));
        $this->assertDatabaseMissing('customer_message_templates', ['id' => $template->id]);
    }

    public function test_seeded_customer_messages_are_visible_in_the_library(): void
    {
        $this->seed();
        $this->actingAs(User::factory()->create());

        $this->get(route('customer-messages.index'))
            ->assertOk()
            ->assertSee('data-datatable', false)
            ->assertSee('Filter by language')
            ->assertSee('Language');
        $this->getJson(route('customer-messages.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()
            ->assertSee('text-bg-success')
            ->assertSee('Booking request received')
            ->assertSee('Diagnosis and approval request')
            ->assertSee('Parts delay update')
            ->assertSee('Permintaan janji temu diterima (Bahasa Melayu)')
            ->assertSee('Makluman kelewatan alat ganti (Bahasa Melayu)');
        $this->getJson(route('customer-messages.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'language' => 'ms',
        ]))->assertOk()
            ->assertSee('Bahasa Melayu')
            ->assertDontSee('Booking request received');
    }

    public function test_email_templates_leave_signature_to_the_mailbox_and_use_real_business_phone_examples(): void
    {
        $this->seed(CustomerMessageTemplateSeeder::class);
        $emailTemplates = CustomerMessageTemplate::query()->where('channel', 'email')->get();

        $this->assertNotEmpty($emailTemplates);
        foreach ($emailTemplates as $template) {
            $this->assertStringNotContainsString('{{business_phone}}', $template->body);
            $this->assertStringNotContainsString('Regards,', $template->body);
            $this->assertStringNotContainsString('Salam hormat,', $template->body);
            $this->assertStringNotContainsString('OMG Computer', $template->body);
        }

        CompanyProfile::query()->create([
            'id' => 'primary',
            'phone' => '0104224350',
        ]);
        $phoneTemplate = new CustomerMessageTemplate;
        $this->assertSame(
            'Call +60 10-422 4350.',
            $phoneTemplate->render('Call {{business_phone}}.'),
        );
    }

    public function test_existing_default_email_templates_are_migrated_without_their_duplicate_signatures(): void
    {
        $legacyBody = "Hi Avery,\n\nIf your plans change, reply to this message or call us at {{business_phone}}.\n\nRegards,\nOMG Computer";
        $template = CustomerMessageTemplate::query()->create([
            'slug' => 'booking-request-received',
            'name' => 'Booking request received',
            'stage' => 'booking',
            'channel' => 'email',
            'language' => 'en',
            'body' => $legacyBody,
            'is_active' => true,
        ]);
        $migration = require database_path('migrations/2026_11_22_000001_remove_signatures_from_default_email_templates.php');

        $migration->up();

        $updatedBody = "Hi Avery,\n\nIf your plans change, reply to this email.";
        $this->assertSame($updatedBody, $template->fresh()->body);

        $migration->down();
        $this->assertSame($legacyBody, $template->fresh()->body);
    }

    public function test_staff_can_load_active_copywriting_suggestions_by_channel(): void
    {
        $this->actingAs(User::factory()->create());

        CustomerMessageTemplate::query()->create([
            'slug' => 'email-confirmation',
            'name' => 'Booking confirmation',
            'stage' => 'booking',
            'channel' => 'email',
            'language' => 'en',
            'subject' => 'Booking confirmed for {{customer_name}}',
            'body' => 'Hi {{customer_name}}, your appointment is confirmed.',
            'is_active' => true,
        ]);
        CustomerMessageTemplate::query()->create([
            'slug' => 'whatsapp-confirmation',
            'name' => 'WhatsApp booking confirmation',
            'stage' => 'booking',
            'channel' => 'whatsapp',
            'language' => 'en',
            'body' => 'Hi {{customer_name}}, your appointment is confirmed.',
            'is_active' => true,
        ]);
        CustomerMessageTemplate::query()->create([
            'slug' => 'email-confirmation-ms',
            'name' => 'Pengesahan tempahan',
            'stage' => 'booking',
            'channel' => 'email',
            'language' => 'ms',
            'subject' => 'Tempahan disahkan',
            'body' => 'Hai {{customer_name}}, janji temu anda disahkan.',
            'is_active' => true,
        ]);
        CustomerMessageTemplate::query()->create([
            'slug' => 'archived-email',
            'name' => 'Archived email',
            'stage' => 'general',
            'channel' => 'email',
            'language' => 'en',
            'body' => 'This template is archived.',
            'is_active' => false,
        ]);

        $this->getJson(route('customer-messages.suggestions', ['channel' => 'email', 'language' => 'en']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(1, 'templates')
            ->assertJsonPath('templates.0.name', 'Booking confirmation')
            ->assertJsonPath('templates.0.language_label', 'English')
            ->assertJsonPath('templates.0.subject', 'Booking confirmed for Avery')
            ->assertJsonPath('templates.0.body', 'Hi Avery, your appointment is confirmed.');

        $this->getJson(route('customer-messages.suggestions', ['channel' => 'email', 'language' => 'ms']))
            ->assertOk()
            ->assertJsonCount(1, 'templates')
            ->assertJsonPath('templates.0.name', 'Pengesahan tempahan')
            ->assertJsonPath('templates.0.language_label', 'Bahasa Melayu');
        $this->getJson(route('customer-messages.suggestions', ['channel' => 'email', 'language' => 'zh_CN']))
            ->assertOk()
            ->assertJsonCount(0, 'templates');
        $this->getJson(route('customer-messages.suggestions', ['channel' => 'whatsapp']))
            ->assertOk()
            ->assertJsonCount(1, 'templates')
            ->assertJsonPath('templates.0.name', 'WhatsApp booking confirmation');
    }
}
