<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\CompanyProfileSeeder;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_profile_seeder_creates_primary_profile_and_preserves_saved_settings(): void
    {
        $this->seed(CompanyProfileSeeder::class);
        $this->assertDatabaseHas('company_profiles', [
            'id' => 'primary',
            'legal_name' => 'OMG Computer',
            'ssm_registration_number' => '202603286108',
            'address' => "2237 Jalan Seksyen 2/7\nTaman Bandar Barat",
            'phone' => '0104224350',
            'email' => 'support@omgcomputer.my',
            'website_url' => 'https://omgcomputer.my',
        ]);

        CompanyProfile::query()->whereKey('primary')->update([
            'legal_name' => 'OMG Computer Sdn Bhd',
            'phone' => '+60312345678',
        ]);

        $this->seed(CompanyProfileSeeder::class);

        $this->assertDatabaseCount('company_profiles', 1);
        $this->assertDatabaseHas('company_profiles', [
            'id' => 'primary',
            'legal_name' => 'OMG Computer Sdn Bhd',
            'phone' => '+60312345678',
        ]);
    }

    public function test_guest_homepage_does_not_show_call_buttons(): void
    {
        CompanyProfile::query()->create([
            'id' => 'primary',
            'phone' => '0104224350',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Discuss a website or online shop project')
            ->assertDontSee('Call us')
            ->assertSee('Browse website previews')
            ->assertDontSee('href="tel:0104224350"', false);
    }

    public function test_admin_can_manage_company_registration_tax_and_facebook_details(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get(route('admin.company-profile.edit'))
            ->assertOk()
            ->assertSee('SSM registration number')
            ->assertSee('Tax identification number (TIN)')
            ->assertSee('Facebook page URL')
            ->assertSee('Instagram profile URL')
            ->assertSee('TikTok profile URL')
            ->assertSee('Bank name')
            ->assertSee('Bank account number');

        $this->actingAs($admin)->put(route('admin.company-profile.update'), [
            'legal_name' => 'OMG Computer Sdn Bhd',
            'ssm_registration_number' => '202601001234',
            'tax_identification_number' => 'C1234567890',
            'sst_registration_number' => 'B16-1234-12345678',
            'address' => 'Kuala Lumpur, Malaysia',
            'phone' => '+60312345678',
            'email' => 'hello@omgcomputer.my',
            'website_url' => 'https://omgcomputer.my',
            'facebook_url' => 'https://www.facebook.com/omgcomputer',
            'instagram_url' => 'https://www.instagram.com/omgcomputer/',
            'tiktok_url' => 'https://www.tiktok.com/@omgcomputer',
            'bank_name' => 'Example Bank',
            'bank_account_number' => '1234567890',
        ])->assertRedirect(route('admin.company-profile.edit'))
            ->assertSessionHas('success', 'Company profile saved.');

        $this->assertDatabaseHas('company_profiles', [
            'id' => 'primary',
            'legal_name' => 'OMG Computer Sdn Bhd',
            'ssm_registration_number' => '202601001234',
            'tax_identification_number' => 'C1234567890',
            'sst_registration_number' => 'B16-1234-12345678',
            'facebook_url' => 'https://www.facebook.com/omgcomputer',
            'instagram_url' => 'https://www.instagram.com/omgcomputer/',
            'tiktok_url' => 'https://www.tiktok.com/@omgcomputer',
            'bank_name' => 'Example Bank',
        ]);
        $profile = CompanyProfile::current();
        $this->assertSame('1234567890', $profile->bank_account_number);
        $this->assertNotSame(
            '1234567890',
            \Illuminate\Support\Facades\DB::table('company_profiles')->where('id', 'primary')->value('bank_account_number'),
        );
    }

    public function test_company_profile_settings_are_admin_only(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('member', 'web');
        $user->assignRole('member');

        $this->actingAs($user)->get(route('admin.company-profile.edit'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.company-profile.update'), [])->assertForbidden();
    }

    public function test_only_facebook_page_urls_are_accepted(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->put(route('admin.company-profile.update'), [
            'facebook_url' => 'https://not-facebook.example/page',
        ])->assertSessionHasErrors('facebook_url');

        $this->assertDatabaseMissing('company_profiles', ['id' => 'primary']);
    }

    public function test_company_bank_details_are_not_rendered_on_public_pages_or_invoice_pdfs(): void
    {
        CompanyProfile::query()->create([
            'id' => 'primary',
            'bank_name' => 'PRIVATE-BANK-NAME',
            'bank_account_number' => 'PRIVATE-BANK-ACCOUNT',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('PRIVATE-BANK-NAME')
            ->assertDontSee('PRIVATE-BANK-ACCOUNT');

        $document = BillingDocument::query()->create([
            'number' => 'INV-BANK-PRIVACY',
            'status' => 'draft',
            'customer_name' => 'Invoice Customer',
            'issue_date' => now()->toDateString(),
        ]);
        $this->view('billing.pdf', [
            'document' => $document,
            'logoData' => null,
            'companyProfile' => CompanyProfile::current(),
        ])->assertDontSee('PRIVATE-BANK-NAME')
            ->assertDontSee('PRIVATE-BANK-ACCOUNT');
    }

    public function test_public_website_shows_business_registration_and_facebook_but_not_tax_numbers(): void
    {
        CompanyProfile::query()->create([
            'id' => 'primary',
            'legal_name' => 'OMG Computer Sdn Bhd',
            'ssm_registration_number' => '202601001234',
            'tax_identification_number' => 'PRIVATE-TIN-123',
            'sst_registration_number' => 'PRIVATE-SST-123',
            'bank_name' => 'PRIVATE-BANK-NAME',
            'bank_account_number' => 'PRIVATE-BANK-ACCOUNT',
            'facebook_url' => 'https://www.facebook.com/omgcomputer',
            'instagram_url' => 'https://www.instagram.com/omgcomputer/',
            'tiktok_url' => 'https://www.tiktok.com/@omgcomputer',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('OMG Empire (OMG Computer)')
            ->assertSee('OMG Computer Sdn Bhd')
            ->assertSee('202601001234')
            ->assertSee('https://www.facebook.com/omgcomputer')
            ->assertSee('https://www.instagram.com/omgcomputer/')
            ->assertSee('aria-label="Facebook"', false)
            ->assertSee('aria-label="Instagram"', false)
            ->assertSee('rounded-circle')
            ->assertDontSee('PRIVATE-TIN-123')
            ->assertDontSee('PRIVATE-SST-123')
            ->assertDontSee('PRIVATE-BANK-NAME')
            ->assertDontSee('PRIVATE-BANK-ACCOUNT');

        $this->actingAs($this->adminUser())
            ->get(route('admin.company-profile.edit'))
            ->assertOk()
            ->assertSee('OMG Empire (OMG Computer)')
            ->assertSee('SSM registration no.: 202601001234');

        $document = BillingDocument::query()->create([
            'number' => 'INV-TEST-001',
            'status' => 'draft',
            'customer_name' => 'Invoice Customer',
            'issue_date' => now()->toDateString(),
        ]);
        $this->view('billing.pdf', [
            'document' => $document,
            'logoData' => null,
            'companyProfile' => CompanyProfile::current(),
        ])->assertSee('202601001234')
            ->assertSee('PRIVATE-TIN-123')
            ->assertSee('PRIVATE-SST-123');
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        return $admin;
    }
}
