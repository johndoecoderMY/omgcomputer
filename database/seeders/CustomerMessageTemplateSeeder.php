<?php

namespace Database\Seeders;

use App\Models\CustomerMessageTemplate;
use Illuminate\Database\Seeder;

class CustomerMessageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'booking-request-received',
                'name' => 'Booking request received',
                'stage' => 'booking',
                'channel' => 'email',
                'subject' => 'We received your appointment request',
                'body' => "Hi {{customer_name}},\n\nThanks for requesting a {{service_name}} appointment for {{appointment_date}} at {{appointment_time}}. This is a request, not a confirmed appointment yet. Our team will contact you shortly to confirm the time and any details we need.\n\nIf your plans change, reply to this message or call us at {{business_phone}}.\n\nRegards,\nOMG Computer",
            ],
            [
                'slug' => 'device-received',
                'name' => 'Device received',
                'stage' => 'intake',
                'channel' => 'whatsapp',
                'subject' => null,
                'body' => "Hi {{customer_name}}, we’ve received your {{device_model}} under ticket {{ticket_number}}. We’ll inspect the reported issue and contact you with our findings before starting chargeable work. Reply here if you have any questions.",
            ],
            [
                'slug' => 'diagnosis-and-approval',
                'name' => 'Diagnosis and approval request',
                'stage' => 'diagnosis',
                'channel' => 'email',
                'subject' => 'Diagnosis and estimate for {{ticket_number}}',
                'body' => "Hi {{customer_name}},\n\nWe’ve completed the initial diagnosis of your {{device_model}}. The proposed work is: {{service_name}}. The current estimate is {{estimate_total}}.\n\nPlease reply to approve or decline the work. We won’t proceed with the quoted repair until we have your approval. The final amount may change if additional faults are found; we’ll check with you first.\n\nRegards,\nOMG Computer\n{{business_phone}}",
            ],
            [
                'slug' => 'quotation-follow-up',
                'name' => 'Quotation follow-up',
                'stage' => 'quotation',
                'channel' => 'whatsapp',
                'subject' => null,
                'body' => "Hi {{customer_name}}, just checking that you received the estimate of {{estimate_total}} for ticket {{ticket_number}}. There’s no pressure to proceed. Let us know if you’d like anything clarified or want us to close the request.",
            ],
            [
                'slug' => 'parts-delay-update',
                'name' => 'Parts delay update',
                'stage' => 'parts',
                'channel' => 'sms',
                'subject' => null,
                'body' => "Hi {{customer_name}}, a quick update on {{ticket_number}}: the required part is taking longer than expected. We’re checking the revised availability and will update you before changing the expected completion date. Questions? Call {{business_phone}}.",
            ],
            [
                'slug' => 'ready-for-pickup',
                'name' => 'Repair ready for pickup',
                'stage' => 'ready',
                'channel' => 'whatsapp',
                'subject' => null,
                'body' => "Hi {{customer_name}}, your {{device_model}} for ticket {{ticket_number}} is ready for pickup. Please contact us at {{business_phone}} to confirm our current opening hours before travelling. We’ll explain the completed work and any remaining notes when you collect it.",
            ],
            [
                'slug' => 'post-service-follow-up',
                'name' => 'Post-service check-in',
                'stage' => 'follow_up',
                'channel' => 'email',
                'subject' => 'How is your device working?',
                'body' => "Hi {{customer_name}},\n\nWe’re checking in after the {{service_name}} on your {{device_model}} (ticket {{ticket_number}}). Is everything working as expected?\n\nIf the issue returns or you have a question about the work, reply to this email or call {{business_phone}} and mention your ticket number.\n\nThanks,\nOMG Computer",
            ],
        ];

        foreach ($templates as $template) {
            CustomerMessageTemplate::query()->firstOrCreate(
                ['slug' => $template['slug']],
                [...$template, 'is_active' => true],
            );
        }
    }
}