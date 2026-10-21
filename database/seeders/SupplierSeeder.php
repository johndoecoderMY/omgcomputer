<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'name' => 'Demo PC Components Supply',
                'contact_name' => 'Components Sales',
                'email' => 'components@example.test',
                'phone' => '+60000000001',
                'website' => 'https://example.com/pc-components',
                'notes' => 'Sample supplier for seeded PC components.',
            ],
            [
                'name' => 'Demo Storage and Memory Supply',
                'contact_name' => 'Storage Sales',
                'email' => 'storage@example.test',
                'phone' => '+60000000002',
                'website' => 'https://example.com/storage-memory',
                'notes' => 'Sample supplier for seeded storage and memory parts.',
            ],
            [
                'name' => 'Demo Peripherals Supply',
                'contact_name' => 'Peripherals Sales',
                'email' => 'peripherals@example.test',
                'phone' => '+60000000003',
                'website' => 'https://example.com/peripherals',
                'notes' => 'Sample supplier for seeded PC accessories.',
            ],
        ] as $supplier) {
            Supplier::query()->firstOrCreate(
                ['name' => $supplier['name']],
                $supplier,
            );
        }
    }
}
