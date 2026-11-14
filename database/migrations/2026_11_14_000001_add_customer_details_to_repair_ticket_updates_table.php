<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_ticket_updates', function (Blueprint $table): void {
            $table->text('customer_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('repair_ticket_updates', function (Blueprint $table): void {
            $table->dropColumn('customer_details');
        });
    }
};
