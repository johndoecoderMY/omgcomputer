<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_company_registration_tax_and_facebook_details(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->get(route('admin.company-profile.edit'))
            ->assertOk()
            ->assertSee('SSM registration number')
            ->assertSee('Tax identification number (TIN)')
            ->assertSee('Facebook page URL');

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
        ])->assertRedirect(route('admin.company-profile.edit'))
            ->assertSessionHas('success', 'Company profile saved.');

        $this->assertDatabaseHas('company_profiles', [
            'id' => 'primary',
            'legal_name' => 'OMG Computer Sdn Bhd',
            'ssm_registration_number' => '202601001234',
            'tax_identification_number' => 'C1234567890',
            'sst_registration_number' => 'B16-1234-12345678',
            'facebook_url' => 'https://www.facebook.com/omgcomputer',
        ]);
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

    public function test_public_website_shows_business_registration_and_facebook_but_not_tax_numbers(): void
    {
        CompanyProfile::query()->create([
            'id' => 'primary',
            'legal_name' => 'OMG Computer Sdn Bhd',
            'ssm_registration_number' => '202601001234',
            'tax_identification_number' => 'PRIVATE-TIN-123',
            'sst_registration_number' => 'PRIVATE-SST-123',
            'facebook_url' => 'https://www.facebook.com/omgcomputer',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('OMG Computer Sdn Bhd')
            ->assertSee('202601001234')
            ->assertSee('https://www.facebook.com/omgcomputer')
            ->assertDontSee('PRIVATE-TIN-123')
            ->assertDontSee('PRIVATE-SST-123');

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
