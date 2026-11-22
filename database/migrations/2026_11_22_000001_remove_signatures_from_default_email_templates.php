<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REPLACEMENTS = [
        'booking-request-received' => [
            [
                "If your plans change, reply to this message or call us at {{business_phone}}.\n\nRegards,\nOMG Computer",
                'If your plans change, reply to this email.',
            ],
        ],
        'diagnosis-and-approval' => [
            [
                "we’ll check with you first.\n\nRegards,\nOMG Computer\n{{business_phone}}",
                'we’ll check with you first.',
            ],
        ],
        'post-service-follow-up' => [
            [
                "If the issue returns or you have a question about the work, reply to this email or call {{business_phone}} and mention your ticket number.\n\nThanks,\nOMG Computer",
                'If the issue returns or you have a question about the work, reply to this email and mention your ticket number.',
            ],
        ],
        'booking-request-received-ms' => [
            [
                "Jika rancangan anda berubah, balas mesej ini atau hubungi kami di {{business_phone}}.\n\nSalam hormat,\nOMG Computer",
                'Jika rancangan anda berubah, balas e-mel ini.',
            ],
        ],
        'diagnosis-and-approval-ms' => [
            [
                "kami akan mendapatkan persetujuan anda terlebih dahulu.\n\nSalam hormat,\nOMG Computer\n{{business_phone}}",
                'kami akan mendapatkan persetujuan anda terlebih dahulu.',
            ],
        ],
        'post-service-follow-up-ms' => [
            [
                "Jika masalah berulang atau anda mempunyai soalan tentang kerja tersebut, balas e-mel ini atau hubungi {{business_phone}} dan berikan nombor tiket anda.\n\nTerima kasih,\nOMG Computer",
                'Jika masalah berulang atau anda mempunyai soalan tentang kerja tersebut, balas e-mel ini dan berikan nombor tiket anda.',
            ],
        ],
    ];

    public function up(): void
    {
        $this->replaceBodies(self::REPLACEMENTS);
    }

    public function down(): void
    {
        $replacements = [];
        foreach (self::REPLACEMENTS as $slug => $pairs) {
            $replacements[$slug] = array_map(
                static fn (array $pair): array => [$pair[1], $pair[0]],
                $pairs,
            );
        }

        $this->replaceBodies($replacements);
    }

    private function replaceBodies(array $replacements): void
    {
        foreach ($replacements as $slug => $pairs) {
            $template = DB::table('customer_message_templates')
                ->where('slug', $slug)
                ->where('channel', 'email')
                ->first(['id', 'body']);

            if (! $template) {
                continue;
            }

            $body = $template->body;
            foreach ($pairs as [$old, $new]) {
                $body = str_replace($old, $new, $body);
            }

            if ($body !== $template->body) {
                DB::table('customer_message_templates')
                    ->where('id', $template->id)
                    ->update(['body' => $body]);
            }
        }
    }
};
