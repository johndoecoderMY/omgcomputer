<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;

class CompanyProfileSeeder extends Seeder
{
    public function run(): void
    {
        CompanyProfile::query()->firstOrCreate(
            ['id' => 'primary'],
            [
                'legal_name' => 'OMG Computer',
                'ssm_registration_number' => '202603286108',
                'address' => "2237 Jalan Seksyen 2/7\nTaman Bandar Barat",
                'phone' => '0104224350',
                'email' => 'support@omgcomputer.my',
                'website_url' => 'https://omgcomputer.my',
            ],
        );
    }
}
