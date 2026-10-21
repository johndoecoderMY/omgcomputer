<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_server_settings', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_encryption', 10)->default('tls');
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->string('imap_host')->nullable();
            $table->unsignedSmallInteger('imap_port')->nullable();
            $table->string('imap_encryption', 10)->default('ssl');
            $table->string('imap_username')->nullable();
            $table->text('imap_password')->nullable();
            $table->string('imap_folder')->default('INBOX');
            $table->string('imap_archive_folder')->default('Archive');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_server_settings');
    }
};
