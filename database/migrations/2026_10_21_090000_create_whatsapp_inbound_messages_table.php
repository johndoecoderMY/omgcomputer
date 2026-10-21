<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_inbound_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('meta_message_id')->unique();
            $table->string('phone_number_id')->index();
            $table->string('wa_id', 32)->index();
            $table->string('contact_name')->nullable();
            $table->string('message_type', 32);
            $table->text('message_body')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_inbound_messages');
    }
};
