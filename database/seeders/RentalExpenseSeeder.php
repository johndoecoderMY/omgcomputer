<?php

namespace Database\Seeders;

use App\Models\BusinessExpense;
use Illuminate\Database\Seeder;

class RentalExpenseSeeder extends Seeder
{
    public function run(): void
    {
        BusinessExpense::query()->firstOrCreate(
            ['category' => 'rental', 'name' => 'Shop rental'],
            [
                'amount' => 900,
                'frequency' => 'monthly',
                'due_date' => now()->startOfMonth()->addMonth()->toDateString(),
                'cycle_paid_amount' => 0,
                'notes' => 'Sample monthly shop rental expense.',
            ],
        );
    }
}
