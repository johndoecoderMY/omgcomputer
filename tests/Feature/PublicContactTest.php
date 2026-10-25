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
            ->assertSee('support@omgcomputer.my')
            ->assertSee('mailto:support@omgcomputer.my', false)
            ->assertSee('Message us on WhatsApp');
    }

    public function test_public_contact_section_hides_default_placeholder_email(): void
    {
        config(['mail.from.address' => 'hello@example.com']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('mailto:hello@example.com', false);
    }
}
