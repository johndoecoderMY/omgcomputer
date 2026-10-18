<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_services', function (Blueprint $table): void {
            $table->string('id', 80)->primary();
            $table->string('category', 80)->index();
            $table->string('name');
            $table->text('scope');
            $table->string('audience');
            $table->string('price');
            $table->decimal('billing_price', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        $timestamp = now();
        foreach (config('products.catalog', []) as $product) {
            DB::table('product_services')->insert([
                ...$product,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_services');
    }
};
