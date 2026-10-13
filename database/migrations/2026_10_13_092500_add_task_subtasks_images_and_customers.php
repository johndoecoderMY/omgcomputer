<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 180);
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable()->index();
            $table->timestamps();
        });

        Schema::table('repair_tickets', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->after('created_by')->constrained()->nullOnDelete();
            $table->json('images')->nullable();
        });

        Schema::table('planning_projects', function (Blueprint $table): void {
            $table->json('images')->nullable();
        });

        Schema::table('planning_tasks', function (Blueprint $table): void {
            $table->foreignId('parent_task_id')->nullable()->after('project_id')->constrained('planning_tasks')->cascadeOnDelete();
            $table->index(['parent_task_id', 'status']);
        });

        foreach (DB::table('repair_tickets')->orderBy('id')->get() as $ticket) {
            $email = filled($ticket->customer_email) ? mb_strtolower(trim($ticket->customer_email)) : null;
            $phone = filled($ticket->customer_phone) ? trim($ticket->customer_phone) : null;

            $customer = $email
                ? DB::table('customers')->whereRaw('LOWER(email) = ?', [$email])->first()
                : null;
            $customer ??= $phone
                ? DB::table('customers')->where('phone', $phone)->first()
                : null;

            $customerId = $customer?->id ?? DB::table('customers')->insertGetId([
                'name' => $ticket->customer_name,
                'email' => $email,
                'phone' => $phone,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
            ]);

            DB::table('repair_tickets')->where('id', $ticket->id)->update(['customer_id' => $customerId]);
        }
    }

    public function down(): void
    {
        Schema::table('planning_tasks', function (Blueprint $table): void {
            $table->dropIndex(['parent_task_id', 'status']);
            $table->dropConstrainedForeignId('parent_task_id');
        });

        Schema::table('planning_projects', function (Blueprint $table): void {
            $table->dropColumn('images');
        });

        Schema::table('repair_tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn('images');
        });

        Schema::dropIfExists('customers');
    }
};
