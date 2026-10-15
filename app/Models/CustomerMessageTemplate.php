<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CustomerMessageTemplate extends Model
{
    public const STAGES = [
        'booking' => 'Booking',
        'intake' => 'Device intake',
        'diagnosis' => 'Diagnosis and approval',
        'quotation' => 'Quotation follow-up',
        'parts' => 'Parts delay',
        'ready' => 'Ready for pickup',
        'follow_up' => 'Follow-up',
        'general' => 'General',
    ];

    public const CHANNELS = [
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
        'sms' => 'SMS',
        'phone' => 'Phone script',
    ];

    public const PLACEHOLDERS = [
        'customer_name' => 'Customer name',
        'ticket_number' => 'Repair ticket number',
        'device_model' => 'Device make and model',
        'service_name' => 'Service or repair',
        'appointment_date' => 'Appointment date',
        'appointment_time' => 'Appointment time',
        'estimate_total' => 'Estimate total in RM',
        'business_phone' => 'Business phone number',
    ];

    protected $fillable = [
        'slug',
        'name',
        'stage',
        'channel',
        'subject',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function render(string $content): string
    {
        $examples = [
            'customer_name' => 'Avery',
            'ticket_number' => 'RT-2026-000123',
            'device_model' => 'ThinkPad T14',
            'service_name' => 'Laptop cleaning',
            'appointment_date' => 'Monday, 19 October',
            'appointment_time' => '10:00 AM MYT',
            'estimate_total' => 'RM 120.00',
            'business_phone' => '+60 5-000 0000',
        ];

        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', function (array $matches) use ($examples): string {
            return $examples[$matches[1]] ?? $matches[0];
        }, $content) ?? $content;
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }
}