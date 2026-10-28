<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        foreach ([
            [
                'id' => 'website-design-development',
                'name' => 'Website design & development',
                'category' => 'website',
                'scope' => 'Design, development, responsive layouts and content setup',
                'audience' => 'Businesses and organisations',
                'price' => 'Quoted per package',
                'billing_price' => 0,
            ],
            [
                'id' => 'website-server-setup',
                'name' => 'Web server setup & configuration',
                'category' => 'website',
                'scope' => 'Server provisioning, application deployment, security and configuration',
                'audience' => 'Website owners',
                'price' => 'Quoted per server',
                'billing_price' => 0,
            ],
            [
                'id' => 'website-domain-registration',
                'name' => 'Domain registration / renewal',
                'category' => 'website',
                'scope' => 'Domain name purchase or renewal and DNS configuration',
                'audience' => 'Website owners',
                'price' => 'Based on registrar pricing',
                'billing_price' => 0,
            ],
            [
                'id' => 'website-hosting',
                'name' => 'Website hosting setup',
                'category' => 'website',
                'scope' => 'Hosting account setup, website deployment and renewal',
                'audience' => 'Website owners',
                'price' => 'Quoted by hosting plan',
                'billing_price' => 0,
            ],
            [
                'id' => 'website-ssl-certificate',
                'name' => 'SSL certificate setup',
                'category' => 'website',
                'scope' => 'HTTPS certificate installation and renewal setup',
                'audience' => 'Website owners',
                'price' => 'Based on certificate plan',
                'billing_price' => 0,
            ],
        ] as $service) {
            DB::table('product_services')->updateOrInsert(
                ['id' => $service['id']],
                [...$service, 'is_active' => 1, 'deleted_at' => null, 'created_at' => DB::raw('COALESCE(created_at, '.$this->databaseTimestamp($timestamp).')'), 'updated_at' => $timestamp],
            );
        }
    }

    public function down(): void
    {
        DB::table('product_services')->whereIn('id', [
            'website-design-development',
            'website-server-setup',
            'website-domain-registration',
            'website-hosting',
            'website-ssl-certificate',
        ])->delete();
    }

    private function databaseTimestamp($timestamp): string
    {
        return DB::getPdo()->quote($timestamp->format('Y-m-d H:i:s'));
    }
};
