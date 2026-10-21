<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppInboundMessage extends Model
{
    protected $table = 'whatsapp_inbound_messages';

    protected $fillable = [
        'meta_message_id',
        'phone_number_id',
        'wa_id',
        'contact_name',
        'message_type',
        'message_body',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
        ];
    }
}
