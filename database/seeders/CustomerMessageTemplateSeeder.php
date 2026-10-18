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
                'body' => 'Hi {{customer_name}}, we’ve received your {{device_model}} under ticket {{ticket_number}}. We’ll inspect the reported issue and contact you with our findings before starting chargeable work. Reply here if you have any questions.',
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
                'body' => 'Hi {{customer_name}}, just checking that you received the estimate of {{estimate_total}} for ticket {{ticket_number}}. There’s no pressure to proceed. Let us know if you’d like anything clarified or want us to close the request.',
            ],
            [
                'slug' => 'parts-delay-update',
                'name' => 'Parts delay update',
                'stage' => 'parts',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Hi {{customer_name}}, a quick update on {{ticket_number}}: the required part is taking longer than expected. We’re checking the revised availability and will update you before changing the expected completion date. Questions? Call {{business_phone}}.',
            ],
            [
                'slug' => 'ready-for-pickup',
                'name' => 'Repair ready for pickup',
                'stage' => 'ready',
                'channel' => 'whatsapp',
                'subject' => null,
                'body' => 'Hi {{customer_name}}, your {{device_model}} for ticket {{ticket_number}} is ready for pickup. Please contact us at {{business_phone}} to confirm our current opening hours before travelling. We’ll explain the completed work and any remaining notes when you collect it.',
            ],
            [
                'slug' => 'post-service-follow-up',
                'name' => 'Post-service check-in',
                'stage' => 'follow_up',
                'channel' => 'email',
                'subject' => 'How is your device working?',
                'body' => "Hi {{customer_name}},\n\nWe’re checking in after the {{service_name}} on your {{device_model}} (ticket {{ticket_number}}). Is everything working as expected?\n\nIf the issue returns or you have a question about the work, reply to this email or call {{business_phone}} and mention your ticket number.\n\nThanks,\nOMG Computer",
            ],
            [
                'slug' => 'booking-request-received-ms',
                'name' => 'Permintaan janji temu diterima (Bahasa Melayu)',
                'stage' => 'booking',
                'channel' => 'email',
                'subject' => 'Permintaan janji temu anda telah diterima',
                'body' => "Hai {{customer_name}},\n\nTerima kasih kerana memohon janji temu {{service_name}} pada {{appointment_date}} jam {{appointment_time}}. Ini ialah permintaan dan belum merupakan janji temu yang disahkan. Pasukan kami akan menghubungi anda sebentar lagi untuk mengesahkan masa dan mendapatkan maklumat yang diperlukan.\n\nJika rancangan anda berubah, balas mesej ini atau hubungi kami di {{business_phone}}.\n\nSalam hormat,\nOMG Computer",
            ],
            [
                'slug' => 'device-received-ms',
                'name' => 'Peranti telah diterima (Bahasa Melayu)',
                'stage' => 'intake',
                'channel' => 'whatsapp',
                'subject' => null,
                'body' => 'Hai {{customer_name}}, kami telah menerima {{device_model}} anda dengan nombor tiket {{ticket_number}}. Kami akan memeriksa masalah yang dilaporkan dan menghubungi anda dengan hasil pemeriksaan sebelum memulakan kerja yang dikenakan bayaran. Balas mesej ini jika anda mempunyai sebarang pertanyaan.',
            ],
            [
                'slug' => 'diagnosis-and-approval-ms',
                'name' => 'Diagnosis dan permintaan kelulusan (Bahasa Melayu)',
                'stage' => 'diagnosis',
                'channel' => 'email',
                'subject' => 'Diagnosis dan anggaran untuk {{ticket_number}}',
                'body' => "Hai {{customer_name}},\n\nPemeriksaan awal {{device_model}} anda telah selesai. Kerja yang dicadangkan: {{service_name}}. Anggaran semasa ialah {{estimate_total}}.\n\nSila balas untuk meluluskan atau menolak kerja tersebut. Kami tidak akan memulakan pembaikan yang dianggarkan tanpa kelulusan anda. Jumlah akhir mungkin berubah jika kerosakan lain ditemui; kami akan mendapatkan persetujuan anda terlebih dahulu.\n\nSalam hormat,\nOMG Computer\n{{business_phone}}",
            ],
            [
                'slug' => 'quotation-follow-up-ms',
                'name' => 'Susulan sebut harga (Bahasa Melayu)',
                'stage' => 'quotation',
                'channel' => 'whatsapp',
                'subject' => null,
                'body' => 'Hai {{customer_name}}, kami ingin memastikan anda telah menerima anggaran {{estimate_total}} untuk tiket {{ticket_number}}. Tiada paksaan untuk meneruskan kerja. Beritahu kami jika anda perlukan penjelasan atau mahu permintaan ini ditutup.',
            ],
            [
                'slug' => 'parts-delay-update-ms',
                'name' => 'Makluman kelewatan alat ganti (Bahasa Melayu)',
                'stage' => 'parts',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Hai {{customer_name}}, kemas kini ringkas tentang {{ticket_number}}: alat ganti yang diperlukan mengambil masa lebih lama daripada jangkaan. Kami sedang menyemak tarikh ketersediaan baharu dan akan memaklumkan anda sebelum mengubah tarikh siap yang dijangka. Hubungi {{business_phone}} jika ada pertanyaan.',
            ],
            [
                'slug' => 'ready-for-pickup-ms',
                'name' => 'Pembaikan sedia untuk diambil (Bahasa Melayu)',
                'stage' => 'ready',
                'channel' => 'whatsapp',
                'subject' => null,
                'body' => 'Hai {{customer_name}}, {{device_model}} anda bagi tiket {{ticket_number}} sedia untuk diambil. Sila hubungi kami di {{business_phone}} untuk mengesahkan waktu operasi terkini sebelum datang. Kami akan menerangkan kerja yang telah disiapkan dan sebarang nota yang masih berkaitan semasa anda mengambil peranti.',
            ],
            [
                'slug' => 'post-service-follow-up-ms',
                'name' => 'Susulan selepas servis (Bahasa Melayu)',
                'stage' => 'follow_up',
                'channel' => 'email',
                'subject' => 'Bagaimanakah keadaan peranti anda?',
                'body' => "Hai {{customer_name}},\n\nKami ingin membuat susulan selepas {{service_name}} pada {{device_model}} anda (tiket {{ticket_number}}). Adakah semuanya berfungsi seperti yang dijangka?\n\nJika masalah berulang atau anda mempunyai soalan tentang kerja tersebut, balas e-mel ini atau hubungi {{business_phone}} dan berikan nombor tiket anda.\n\nTerima kasih,\nOMG Computer",
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
