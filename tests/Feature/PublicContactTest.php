<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicContactTest extends TestCase
{
    public function test_public_contact_section_shows_configured_email_as_a_mailto_link(): void
    {
        config(['mail.from.address' => 'support@omgcomputer.my']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Computer Repair, Website Design &amp; Development | OMG Computer</title>', false)
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
