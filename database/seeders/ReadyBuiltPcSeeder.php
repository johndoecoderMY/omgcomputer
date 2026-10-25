<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ReadyBuiltPcSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SupplierSeeder::class);

        $supplier = Supplier::query()->where('name', 'Demo PC Components Supply')->firstOrFail();

        $pcs = [
            [
                'sku' => 'DEMO-PC-OFFICE-001',
                'name' => 'Ready-built Office PC',
                'description' => 'Sample ready-built office desktop with AMD Ryzen 5 5600G, 16GB DDR4 memory, 512GB NVMe SSD, Wi-Fi, and Windows 11. Update specifications, price, and stock to match your actual build.',
                'cost_price' => 1450,
                'selling_price' => 1799,
                'stock_quantity' => 2,
                'reorder_level' => 1,
            ],
            [
                'sku' => 'DEMO-PC-GAMING-001',
                'name' => 'Ready-built 1080p Gaming PC',
                'description' => 'Sample ready-built gaming desktop with AMD Ryzen 5 5600, GeForce RTX 4060 8GB, 16GB DDR4 memory, 1TB NVMe SSD, and a 650W power supply. Update specifications, price, and stock to match your actual build.',
                'cost_price' => 2750,
                'selling_price' => 3299,
                'stock_quantity' => 2,
                'reorder_level' => 1,
            ],
            [
                'sku' => 'DEMO-PC-GAMING-002',
                'name' => 'Ready-built Performance Gaming PC',
                'description' => 'Sample ready-built gaming desktop with AMD Ryzen 5 7600, GeForce RTX 4070 SUPER 12GB, 32GB DDR5 memory, 1TB NVMe SSD, and a 750W power supply. Update specifications, price, and stock to match your actual build.',
                'cost_price' => 5250,
                'selling_price' => 5999,
                'stock_quantity' => 1,
                'reorder_level' => 1,
            ],
        ];

        foreach ($pcs as $pcData) {
            $part = InventoryPart::query()->firstOrCreate(
                ['sku' => $pcData['sku']],
                [
                    ...$pcData,
                    'type' => 'ready_built_pc',
                    'supplier_id' => $supplier->id,
                    'is_for_sale' => true,
                ],
            );
            $part->update(['is_for_sale' => true]);

            InventoryMovement::query()->firstOrCreate(
                ['reference' => 'SEED-OPENING-'.$part->sku],
                [
                    'inventory_part_id' => $part->id,
                    'type' => 'received',
                    'quantity_change' => $part->stock_quantity,
                    'quantity_after' => $part->stock_quantity,
                    'note' => 'Sample opening stock from the ready-built PC seeder.',
                ],
            );
        }
    }
}
