<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicContactTest extends TestCase
{
    public function test_robots_txt_allows_public_pages_and_points_to_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robots);
        $this->assertStringContainsString("User-agent: *\nAllow: /\n", str_replace("\r\n", "\n", $robots));
        $this->assertStringNotContainsString("Disallow: /\n", str_replace("\r\n", "\n", $robots));
        $this->assertStringContainsString('Disallow: /admin/', $robots);
        $this->assertStringContainsString('Sitemap: https://omgcomputer.my/sitemap.xml', $robots);
    }

    public function test_public_contact_section_shows_configured_email_as_a_mailto_link(): void
    {
        config(['mail.from.address' => 'support@omgcomputer.my']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Computer Repair, Website Design &amp; Development | OMG Computer</title>', false)
            ->assertSee('<meta name="description" content="Computer repair, upgrades, custom PCs, and website design in Malaysia.', false)
            ->assertSee('<link rel="canonical" href="'.url('/').'">', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertSee('support@omgcomputer.my')
            ->assertSee('mailto:support@omgcomputer.my', false)
            ->assertSee('Message us on WhatsApp')
            ->assertSee(route('bookings.create'), false)
            ->assertSee('Book a service')
            ->assertSee('href="'.route('bookings.create').'" class="btn btn-primary rounded-pill px-4">Book a service</a>', false)
            ->assertSee(route('repairs.track.form'), false)
            ->assertSee('Track your repair')
            ->assertSee('Have your service number ready to check repair progress.');
    }

    public function test_public_contact_section_hides_default_placeholder_email(): void
    {
        config(['mail.from.address' => 'hello@example.com']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('mailto:hello@example.com', false);
    }

    public function test_guest_navigation_stays_outside_the_scrolling_main_content(): void
    {
        $content = $this->get('/')->assertOk()->getContent();
        $navbarPosition = strpos($content, '<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top py-1">');
        $mainPosition = strpos($content, '<main class="main" id="top">');

        $this->assertNotFalse($navbarPosition);
        $this->assertNotFalse($mainPosition);
        $this->assertLessThan($mainPosition, $navbarPosition);
    }

    public function test_guest_pages_show_back_link_without_breadcrumb_except_welcome_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('aria-label="Breadcrumb"', false)
            ->assertDontSee('Back to OMG Computer');

        $this->get(route('privacy'))
            ->assertOk()
            ->assertDontSee('aria-label="Breadcrumb"', false)
            ->assertSee('bi bi-arrow-left me-2', false)
            ->assertSee('Back to OMG Computer')
            ->assertSee('href="'.url('/').'"', false);

        $this->get(route('repairs.track.form'))
            ->assertOk()
            ->assertDontSee('aria-label="Breadcrumb"', false)
            ->assertSee('Back to OMG Computer');
    }

    public function test_public_policies_match_current_site_flows_and_use_compact_spacing(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('booking requests, messages, and information about your device')
            ->assertSee('Online shop checkout is handled through CHiP')
            ->assertSee('data deletion instructions')
            ->assertSee('py-3 py-lg-4')
            ->assertSee('pb-3 pb-lg-4')
            ->assertSee('p-3 p-lg-4');

        $this->get(route('refund-policy'))
            ->assertOk()
            ->assertSee('A booking request is subject to confirmation')
            ->assertSee('does not limit rights or remedies')
            ->assertSee('website contact section')
            ->assertSee('py-3 py-lg-4')
            ->assertSee('pb-3 pb-lg-4');

        $this->get(route('shipping-policy'))
            ->assertOk()
            ->assertSee('does not calculate a delivery charge')
            ->assertSee('optional delivery address')
            ->assertSee('does not remove any rights')
            ->assertSee('py-3 py-lg-4')
            ->assertSee('pb-3 pb-lg-4');

        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('A booking is a request')
            ->assertSee('Online shop payments are processed through CHiP')
            ->assertSee('Website previews are examples of design work')
            ->assertSee('py-3 py-lg-4')
            ->assertSee('pb-3 pb-lg-4');
    }

    public function test_booking_and_repair_tracking_links_are_in_the_computer_support_section(): void
    {
        $content = $this->get('/')->assertOk()->getContent();
        $servicesSectionStart = strpos($content, 'id="services"');
        $computerSectionStart = strpos($content, 'id="how-it-works"');
        $contactSectionStart = strpos($content, 'id="contact"');
        $faqSectionStart = strpos($content, 'id="faq"');
        $aboutSectionStart = strpos($content, 'id="about"');
        $websiteSectionStart = strpos($content, 'id="website-pricing"');
        $bookingLinkPosition = strpos($content, route('bookings.create'), $computerSectionStart);
        $trackingLinkPosition = strpos($content, route('repairs.track.form'), $computerSectionStart);

        $this->assertNotFalse($servicesSectionStart);
        $this->assertNotFalse($computerSectionStart);
        $this->assertNotFalse($contactSectionStart);
        $this->assertNotFalse($faqSectionStart);
        $this->assertNotFalse($aboutSectionStart);
        $this->assertNotFalse($websiteSectionStart);
        $this->assertNotFalse($bookingLinkPosition);
        $this->assertNotFalse($trackingLinkPosition);
        $this->assertLessThan($computerSectionStart, $servicesSectionStart);
        $this->assertLessThan($websiteSectionStart, $computerSectionStart);
        $this->assertLessThan($faqSectionStart, $websiteSectionStart);
        $this->assertLessThan($contactSectionStart, $faqSectionStart);
        $this->assertLessThan($aboutSectionStart, $computerSectionStart);
        $this->assertLessThan($bookingLinkPosition, $computerSectionStart);
        $this->assertLessThan($trackingLinkPosition, $computerSectionStart);
    }
}
