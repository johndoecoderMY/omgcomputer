<?php

namespace Tests\Feature;

use Database\Seeders\CompanyProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAboutTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_is_guest_accessible_and_shows_company_information(): void
    {
        $this->seed(CompanyProfileSeeder::class);

        $this->assertGuest();
        $this->assertSame(url('/about'), route('about'));

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('<title>About OMG Computer | OMG Computer</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('about').'">', false)
            ->assertSee('Technology should make life easier.')
            ->assertSee('Clear advice')
            ->assertSee('Your approval first')
            ->assertSee('Computer repair & upgrades')
            ->assertSee('SSM registration no.')
            ->assertSee('OMG Computer is owned by OMG Empire.')
            ->assertSee('202603286108')
            ->assertSee('mailto:support@omgcomputer.my', false)
            ->assertSee('Message us on WhatsApp')
            ->assertDontSee('Contact OMG Computer')
            ->assertSee(asset('assets/img/omg/hero-technician-768.webp'), false)
            ->assertSee(route('bookings.create'), false)
            ->assertSee(route('shop.public'), false)
            ->assertSee('href="'.route('about').'"', false);
    }
}
