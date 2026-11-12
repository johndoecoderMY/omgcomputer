<?php

namespace Database\Seeders;

use App\Models\CustomPcComponent;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PcPartsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SupplierSeeder::class);

        $componentsSupplier = Supplier::query()->where('name', 'Demo PC Components Supply')->firstOrFail();
        $storageSupplier = Supplier::query()->where('name', 'Demo Storage and Memory Supply')->firstOrFail();
        $peripheralsSupplier = Supplier::query()->where('name', 'Demo Peripherals Supply')->firstOrFail();

        $parts = [
            ['sku' => 'DEMO-CPU-001', 'name' => 'AMD Ryzen 5 7600 processor', 'type' => 'cpu', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 650, 'selling_price' => 799, 'stock_quantity' => 5, 'reorder_level' => 2, 'is_for_sale' => true, 'description' => "Socket: AM5\nCores: 6\nThreads: 12\nBase clock: 3.8 GHz\nMax boost clock: Up to 5.1 GHz\nDefault TDP: 65 W\nMemory support: DDR5\nIncluded cooler: Wraith Stealth"],
            ['sku' => 'DEMO-CPU-002', 'name' => 'Intel Core i5-12400F processor', 'type' => 'cpu', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 520, 'selling_price' => 649, 'stock_quantity' => 4, 'reorder_level' => 1, 'is_for_sale' => true, 'description' => "Socket: LGA1700\nCores: 6\nThreads: 12\nMax turbo frequency: Up to 4.4 GHz\nProcessor base power: 65 W\nMaximum turbo power: 117 W\nMemory support: DDR4 or DDR5, motherboard dependent\nIntegrated graphics: None"],
            ['sku' => 'DEMO-GPU-001', 'name' => 'Graphics card', 'type' => 'gpu', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 2200, 'selling_price' => 2599, 'stock_quantity' => 3, 'reorder_level' => 1, 'is_for_sale' => true, 'description' => "Demo reference specifications; confirm the exact manufacturer model before ordering.\nMemory: 8 GB GDDR6\nInterface: PCI Express 4.0 x16\nMemory bus: 128-bit\nCooling: Dual-fan\nPower connector: 1 x 8-pin\nRecommended power supply: 650 W"],
            ['sku' => 'DEMO-RAM-001', 'name' => 'DDR5 memory kit 32GB', 'type' => 'ram', 'supplier_id' => $storageSupplier->id, 'cost_price' => 320, 'selling_price' => 399, 'stock_quantity' => 8, 'reorder_level' => 3, 'is_for_sale' => true, 'description' => "Demo reference specifications; confirm the exact manufacturer model before ordering.\nCapacity: 32 GB (2 x 16 GB)\nMemory type: DDR5 UDIMM\nRated speed: 6000 MT/s\nCAS latency: CL36\nVoltage: 1.35 V\nProfile: AMD EXPO / Intel XMP, kit dependent"],
            ['sku' => 'DEMO-MB-001', 'name' => 'B650M AM5 motherboard (DDR5)', 'type' => 'motherboard', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 580, 'selling_price' => 699, 'stock_quantity' => 4, 'reorder_level' => 1, 'is_for_sale' => true, 'description' => "Socket: AM5\nChipset: AMD B650\nForm factor: Micro-ATX\nMemory: DDR5, 4 DIMM slots\nExpansion: PCIe x16 graphics slot\nStorage: M.2 NVMe support\nNetworking: 2.5 Gb Ethernet\nCompatibility: AMD Ryzen desktop processors for socket AM5"],
            ['sku' => 'DEMO-MB-002', 'name' => 'B760M LGA1700 motherboard (DDR5)', 'type' => 'motherboard', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 540, 'selling_price' => 679, 'stock_quantity' => 3, 'reorder_level' => 1, 'is_for_sale' => true, 'description' => "Socket: LGA1700\nChipset: Intel B760\nForm factor: Micro-ATX\nMemory: DDR5, 4 DIMM slots\nExpansion: PCIe x16 graphics slot\nStorage: M.2 NVMe support\nNetworking: 2.5 Gb Ethernet\nCompatibility: Intel Core 12th, 13th, and 14th Gen desktop processors; BIOS support may vary"],
            ['sku' => 'DEMO-NVME-001', 'name' => '512GB NVMe SSD', 'type' => 'storage', 'supplier_id' => $storageSupplier->id, 'cost_price' => 180, 'selling_price' => 260, 'stock_quantity' => 8, 'reorder_level' => 2, 'is_for_sale' => true, 'description' => "Demo reference specifications; confirm the exact manufacturer model before ordering.\nCapacity: 512 GB\nForm factor: M.2 2280\nInterface: PCIe 4.0 x4 NVMe\nSequential read: Up to 5,000 MB/s, model dependent\nSequential write: Up to 4,000 MB/s, model dependent"],
            ['sku' => 'DEMO-HDD-001', 'name' => '2TB hard drive', 'type' => 'storage', 'supplier_id' => $storageSupplier->id, 'cost_price' => 210, 'selling_price' => 289, 'stock_quantity' => 4, 'reorder_level' => 2, 'is_for_sale' => true, 'description' => "Demo reference specifications; confirm the exact manufacturer model before ordering.\nCapacity: 2 TB\nForm factor: 3.5-inch\nInterface: SATA 6 Gb/s\nSpindle speed: 7,200 RPM, model dependent\nCache: 256 MB, model dependent"],
            ['sku' => 'DEMO-PSU-001', 'name' => '750W power supply', 'type' => 'power_supply', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 350, 'selling_price' => 449, 'stock_quantity' => 3, 'reorder_level' => 1, 'is_for_sale' => true, 'description' => "Demo reference specifications; confirm the exact manufacturer model before ordering.\nContinuous output: 750 W\nForm factor: ATX\nEfficiency: 80 PLUS Bronze, model dependent\nCabling: Non-modular, model dependent\nPCIe power: 2 x 6+2-pin connectors\nProtection: OVP, OPP, SCP, model dependent"],
            ['sku' => 'DEMO-CASE-001', 'name' => 'Mid-tower PC case', 'type' => 'case', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 280, 'selling_price' => 359, 'stock_quantity' => 2, 'reorder_level' => 1, 'is_for_sale' => true, 'description' => "Demo reference specifications; confirm the exact manufacturer model before ordering.\nForm factor: Mid-tower\nMotherboard support: ATX, Micro-ATX, Mini-ITX, model dependent\nFront I/O: USB-A and audio, model dependent\nDrive bays: 2 x 2.5-inch and 2 x 3.5-inch, model dependent\nCooling: Multiple 120 mm fan mounts"],
            ['sku' => 'DEMO-COOL-001', 'name' => 'Tower CPU air cooler', 'type' => 'cooling', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 140, 'selling_price' => 199, 'stock_quantity' => 5, 'reorder_level' => 2, 'is_for_sale' => true, 'description' => "Demo reference specifications; confirm the exact manufacturer model and included mounting kit before ordering.\nType: Single-tower air cooler\nHeat pipes: 4 x 6 mm copper, model dependent\nFan: 120 mm PWM\nHeight: Approximately 155 mm, model dependent\nSocket support: Check the included kit and cooler revision"],
            ['sku' => 'DEMO-FAN-001', 'name' => '120mm case fan', 'type' => 'cooling', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 25, 'selling_price' => 39, 'stock_quantity' => 10, 'reorder_level' => 4, 'is_for_sale' => true, 'description' => "Demo reference specifications; confirm the exact manufacturer model before ordering.\nSize: 120 x 120 x 25 mm\nConnector: 4-pin PWM, model dependent\nBearing: Hydraulic, model dependent\nSpeed range: 600-1,800 RPM, model dependent\nUse: PC case airflow"],
            ['sku' => 'DEMO-OS-W11H-001', 'name' => 'Microsoft Windows 11 Home license (demo estimate)', 'type' => 'operating_system', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 450, 'selling_price' => 599, 'stock_quantity' => 2, 'reorder_level' => 1, 'is_for_sale' => true, 'description' => "Demo price and stock only; not a license key or proof of genuine license inventory.\nProduct: Windows 11 Home\nLicense type: Confirm genuine retail or authorized OEM channel before quoting\nArchitecture: 64-bit\nInstallation: License and installation method depend on the verified product channel"],
            ['sku' => 'DEMO-OS-W11P-001', 'name' => 'Microsoft Windows 11 Pro license (demo estimate)', 'type' => 'operating_system', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 650, 'selling_price' => 849, 'stock_quantity' => 2, 'reorder_level' => 1, 'is_for_sale' => true, 'description' => "Demo price and stock only; not a license key or proof of genuine license inventory.\nProduct: Windows 11 Pro\nLicense type: Confirm genuine retail or authorized OEM channel before quoting\nArchitecture: 64-bit\nInstallation: License and installation method depend on the verified product channel"],
            ['sku' => 'DEMO-SW-M365-001', 'name' => 'Microsoft 365 Personal subscription (demo estimate)', 'type' => 'software', 'supplier_id' => $componentsSupplier->id, 'cost_price' => 280, 'selling_price' => 349, 'stock_quantity' => 2, 'reorder_level' => 1, 'is_for_sale' => true, 'description' => "Demo price and stock only; not a license key or proof of genuine subscription inventory.\nProduct: Microsoft 365 Personal\nLicense type: Subscription; verify current term, region, account, and authorized reseller channel before quoting\nApps: Microsoft 365 apps vary by subscription and platform\nDelivery: Verify activation and redemption terms with the authorized distributor"],
            ['sku' => 'DEMO-KB-001', 'name' => 'USB keyboard', 'type' => 'peripheral', 'supplier_id' => $peripheralsSupplier->id, 'cost_price' => 55, 'selling_price' => 89, 'stock_quantity' => 6, 'reorder_level' => 2, 'is_for_sale' => true],
            ['sku' => 'DEMO-MOUSE-001', 'name' => 'USB optical mouse', 'type' => 'peripheral', 'supplier_id' => $peripheralsSupplier->id, 'cost_price' => 30, 'selling_price' => 49, 'stock_quantity' => 7, 'reorder_level' => 2, 'is_for_sale' => true],
            ['sku' => 'DEMO-MON-001', 'name' => '24-inch monitor', 'type' => 'peripheral', 'supplier_id' => $peripheralsSupplier->id, 'cost_price' => 420, 'selling_price' => 529, 'stock_quantity' => 2, 'reorder_level' => 1, 'is_for_sale' => true],
            ['sku' => 'DEMO-USB-001', 'name' => 'USB-C adapter', 'type' => 'other', 'supplier_id' => $peripheralsSupplier->id, 'cost_price' => 35, 'selling_price' => 59, 'stock_quantity' => 5, 'reorder_level' => 2, 'is_for_sale' => true],
            ['sku' => 'DEMO-USB-EXT-001', 'name' => 'USB 3.0 extension cable (2 m)', 'type' => 'peripheral', 'supplier_id' => $peripheralsSupplier->id, 'cost_price' => 12, 'selling_price' => 19, 'stock_quantity' => 10, 'reorder_level' => 3, 'is_for_sale' => true, 'description' => "Demo reference specifications; confirm exact manufacturer and cable rating before quoting.\nConnector: USB-A male to USB-A female\nStandard: USB 3.0, model dependent\nLength: 2 m\nUse: Extend the reach of a compatible USB device"],
        ];

        foreach ($parts as $partData) {
            $description = $partData['description'] ?? 'Sample seeded PC component. Update details and stock to match your actual inventory.';
            $supplierId = $partData['supplier_id'];
            unset($partData['stock_quantity'], $partData['reorder_level'], $partData['is_for_sale'], $partData['supplier_id']);
            $part = CustomPcComponent::query()->updateOrCreate(
                ['sku' => $partData['sku']],
                [
                    ...$partData,
                    'description' => $description,
                ],
            );
            $part->update([
                'name' => $partData['name'],
                'type' => $partData['type'],
                'description' => $description,
            ]);
            $part->suppliers()->syncWithoutDetaching([$supplierId]);
        }

        $this->call(CsvPcPartsSeeder::class);
    }
}
