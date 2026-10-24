<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_email_mailboxes', function (Blueprint $table): void {
            $table->text('signature')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_email_mailboxes', function (Blueprint $table): void {
            $table->dropColumn('signature');
        });
    }
};
