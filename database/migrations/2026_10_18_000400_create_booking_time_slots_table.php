<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_time_slots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->timestamps();
            $table->unique(['day_of_week', 'start_time']);
        });

        $timestamp = now();
        foreach (range(1, 6) as $day) {
            foreach (range(9, 17) as $hour) {
                DB::table('booking_time_slots')->insert([
                    'day_of_week' => $day,
                    'start_time' => sprintf('%02d:00:00', $hour),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_time_slots');
    }
};
