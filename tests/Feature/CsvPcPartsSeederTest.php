<?php

namespace Tests\Feature;

use App\Models\CustomPcComponent;
use Database\Seeders\CsvPcPartsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvPcPartsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_relevant_pc_generations_instead_of_legacy_specs(): void
    {
        CustomPcComponent::query()->create([
            'sku' => 'CSV-CPU-000161',
            'name' => 'Intel Pentium G640',
            'type' => 'cpu',
        ]);

        $this->seed(CsvPcPartsSeeder::class);

        $this->assertDatabaseHas('custom_pc_components', [
            'name' => 'AMD Ryzen 7 9800X3D',
            'type' => 'cpu',
        ]);
        $this->assertDatabaseMissing('custom_pc_components', [
            'name' => 'Intel Pentium G640',
        ]);

        $this->assertDatabaseMissing('custom_pc_components', [
            'name' => 'Silicon Power SP016GLLTU160N22 16 GB',
        ]);
        $this->assertDatabaseHas('custom_pc_components', [
            'name' => 'Corsair Vengeance RGB 32 GB',
            'type' => 'ram',
        ]);

        $this->assertDatabaseMissing('custom_pc_components', [
            'name' => 'ASRock G41M-S',
        ]);
        $this->assertTrue(
            CustomPcComponent::query()
                ->where('type', 'motherboard')
                ->where('description', 'like', '%Socket: AM5%')
                ->exists(),
        );

        $this->assertFalse(
            CustomPcComponent::query()
                ->where('type', 'gpu')
                ->where('description', 'like', '%Chipset: GeForce GTX 1080 Ti%')
                ->exists(),
        );
        $this->assertTrue(
            CustomPcComponent::query()
                ->where('type', 'gpu')
                ->where('description', 'like', '%Chipset: GeForce RTX 5070 Ti%')
                ->exists(),
        );
    }
}
