<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        foreach (config('products.catalog', []) as $product) {
            if (($product['category'] ?? null) !== 'laptop') {
                continue;
            }

            DB::table('product_services')->updateOrInsert(
                ['id' => $product['id']],
                [
                    ...$product,
                    'is_active' => true,
                    'deleted_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            );
        }
    }

    public function down(): void
    {
        foreach (config('products.catalog', []) as $product) {
            if (($product['category'] ?? null) === 'laptop') {
                DB::table('product_services')->where('id', $product['id'])->delete();
            }
        }
    }
};
