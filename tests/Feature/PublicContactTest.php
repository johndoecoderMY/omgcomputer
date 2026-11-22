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
            ->assertSee('Book and drop off')
            ->assertSee('Review the diagnosis and quote')
            ->assertSee('Collect your device')
            ->assertSee('We’ll confirm the diagnostic fee with you before booking or assessment.')
            ->assertSee('Warranty coverage and duration vary by repair and parts.')
            ->assertSee('Please back up important data before handing over your device.')
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

    public function test_homepage_uses_responsive_images_and_only_public_scripts(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('hero-technician-640.webp')
            ->assertSee('hero-technician-768.webp')
            ->assertSee('repair-board-work-480.webp')
            ->assertSee('repair-board-work-768.webp')
            ->assertSee('logoex2w-120.webp')
            ->assertSee('assets/css/local-fonts.css')
            ->assertDontSee('fonts.googleapis.com')
            ->assertDontSee('fonts.gstatic.com')
            ->assertSee('media="print" onload="this.media=\'all\'"', false)
            ->assertSee('vendors/popper/popper.min.js')
            ->assertSee('vendors/bootstrap/bootstrap.min.js')
            ->assertDontSee('custom-pc-builder-visual')
            ->assertDontSee('three-pc-scene')
            ->assertDontSee('vendors/echarts/echarts.min.js')
            ->assertDontSee('vendors/datatables.net/dataTables.min.js')
            ->assertDontSee('vendors/select2/select2.full.min.js');

        $content = $response->getContent();
        $headEnd = strpos($content, '</head>');
        $criticalStyles = strpos($content, '.landing-hero');
        $popperScript = strpos($content, 'vendors/popper/popper.min.js');
        $bootstrapScript = strpos($content, 'vendors/bootstrap/bootstrap.min.js');

        $this->assertNotFalse($headEnd);
        $this->assertNotFalse($criticalStyles);
        $this->assertLessThan($headEnd, $criticalStyles);
        $this->assertNotFalse($popperScript);
        $this->assertNotFalse($bootstrapScript);
        $this->assertLessThan($bootstrapScript, $popperScript);

        $this->assertFileExists(public_path('assets/img/omg/hero-technician-640.webp'));
        $this->assertFileExists(public_path('assets/img/omg/hero-technician-768.webp'));
        $this->assertFileExists(public_path('assets/img/omg/repair-board-work-480.webp'));
        $this->assertFileExists(public_path('assets/img/omg/repair-board-work-768.webp'));
        $this->assertFileExists(public_path('assets/img/omg/logoex2w-120.webp'));
        $this->assertFileExists(public_path('assets/css/local-fonts.css'));
        $this->assertFileExists(public_path('assets/fonts/open-sans-300-800-latin.woff2'));
        $this->assertFileExists(public_path('assets/fonts/poppins-400-latin.woff2'));
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

    public function test_public_language_selector_persists_english_bahasa_melayu_and_mandarin(): void
    {
        $this->post(route('language.update'), ['locale' => 'ms'])
            ->assertRedirect('/')
            ->assertSessionHas('public_locale', 'ms');

        $this->get('/')
            ->assertOk()
            ->assertSee('<html data-bs-theme="light" lang="ms"', false)
            ->assertSee('Kedai')
            ->assertSee('Komputer bermasalah?')
            ->assertSee('Tempah perkhidmatan')
            ->assertSee('Bahasa Melayu');

        $this->post(route('language.update'), ['locale' => 'zh_CN'])
            ->assertRedirect('/')
            ->assertSessionHas('public_locale', 'zh_CN');

        $this->get('/')
            ->assertOk()
            ->assertSee('<html data-bs-theme="light" lang="zh-CN"', false)
            ->assertSee('商店')
            ->assertSee('电脑出问题了？')
            ->assertSee('预约服务')
            ->assertSee('中文');

        $this->post(route('language.update'), ['locale' => 'en'])
            ->assertRedirect('/')
            ->assertSessionHas('public_locale', 'en');

        $this->get('/')
            ->assertOk()
            ->assertSee('<html data-bs-theme="light" lang="en"', false)
            ->assertSee('Computer trouble?')
            ->assertSee('Book a service')
            ->assertSee('id="guest-settings-menu"', false)
            ->assertSee('value="light"', false)
            ->assertSee('value="dark"', false)
            ->assertSee('assets/js/config.js', false)
            ->assertSee('assets/js/theme.js', false)
            ->assertSee("localStorage.setItem('theme', resolvedTheme)", false)
            ->assertSee("root.setAttribute('data-bs-theme', resolvedTheme)", false);
    }

    public function test_public_languages_cover_additional_guest_pages(): void
    {
        $this->withSession(['public_locale' => 'ms'])
            ->get(route('repairs.track.form'))
            ->assertOk()
            ->assertSee('Penjejakan pembaikan tetamu')
            ->assertSee('Masukkan nombor servis anda');

        $this->withSession(['public_locale' => 'ms'])
            ->get(route('custom-pc-builder.public'))
            ->assertOk()
            ->assertSee('Pratonton gaya PC anda.')
            ->assertSee('Mulakan dengan keutamaan anda');

        $this->withSession(['public_locale' => 'ms'])
            ->get(route('privacy'))
            ->assertOk()
            ->assertSee('Polisi Privasi')
            ->assertSee('Maklumat yang kami kumpulkan');

        $this->withSession(['public_locale' => 'ms'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('<html data-bs-theme="light" lang="ms"', false)
            ->assertSee('Selamat kembali')
            ->assertSee('Log masuk untuk mengurus pembaikan, projek dan akaun anda.')
            ->assertSee('dropdown-toggle');

        $this->withSession(['public_locale' => 'zh_CN'])
            ->get(route('repairs.track.form'))
            ->assertOk()
            ->assertSee('访客维修进度查询')
            ->assertSee('请输入服务编号');

        $this->withSession(['public_locale' => 'zh_CN'])
            ->get(route('custom-pc-builder.public'))
            ->assertOk()
            ->assertSee('预览您喜欢的电脑外观')
            ->assertSee('从您的优先需求开始');

        $this->withSession(['public_locale' => 'zh_CN'])
            ->get(route('privacy'))
            ->assertOk()
            ->assertSee('隐私政策')
            ->assertSee('我们收集的信息');

        $this->withSession(['public_locale' => 'zh_CN'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('<html data-bs-theme="light" lang="zh-CN"', false)
            ->assertSee('欢迎回来')
            ->assertSee('登录以管理您的维修、项目和账户。');
    }

    public function test_custom_pc_builder_page_is_public_and_homepage_has_no_custom_build_cta(): void
    {
        $this->assertSame(url('/custom-pc'), route('custom-pc-builder.public'));

        $this->get(route('custom-pc-builder.public'))
            ->assertOk()
            ->assertSee('<title>Custom PC Builder | OMG Computer</title>', false)
            ->assertSee('Preview your PC style. We’ll help plan the build.')
            ->assertSee('visual sample, not a live part selector')
            ->assertSee('No component knowledge is needed.')
            ->assertSee('data-pc-visual', false)
            ->assertSee(asset('assets/img/omg/ready-built-pc-900.webp'), false)
            ->assertSee('data-pc-canvas', false)
            ->assertSee('Interactive 3D preview', false)
            ->assertSee('scroll or pinch to zoom', false)
            ->assertSee('custom-pc-builder-visual-', false)
            ->assertSee('Request a quote on WhatsApp')
            ->assertSee('wa.me/60104224350?text=', false)
            ->assertSee('A few details are enough to get started.')
            ->assertSee(asset('licenses/threejs-MIT.txt'), false)
            ->assertSee(route('shop.public', ['type' => 'ready_built_pc']), false);

        $this->assertFileExists(public_path('licenses/threejs-MIT.txt'));
        $this->assertFileExists(public_path('assets/img/omg/ready-built-pc-900.webp'));

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Plan a custom build');

        $this->get('/custom-pc-builder')
            ->assertStatus(301)
            ->assertRedirect(route('custom-pc-builder.public'));
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
            ->assertSee('Devices and customer data')
            ->assertSee('We access files only when needed for the requested work and with your permission.')
            ->assertSee('data deletion instructions')
            ->assertSee(asset('assets/img/icons/shield.png'), false)
            ->assertDontSee('computer-repair-placeholder.svg')
            ->assertSee('py-3 py-lg-4')
            ->assertSee('pb-3 pb-lg-4')
            ->assertSee('p-3 p-lg-4');

        $this->get(route('refund-policy'))
            ->assertOk()
            ->assertSee('A booking request is subject to confirmation')
            ->assertSee('does not limit rights or remedies')
            ->assertSee('website contact section')
            ->assertSee(asset('assets/img/icons/docs.png'), false)
            ->assertSee('py-3 py-lg-4')
            ->assertSee('pb-3 pb-lg-4');

        $this->get(route('shipping-policy'))
            ->assertOk()
            ->assertSee('does not calculate a delivery charge')
            ->assertSee('optional delivery address')
            ->assertSee('does not remove any rights')
            ->assertSee(asset('assets/img/website-preview-concepts/store-packaging.svg'), false)
            ->assertDontSee('computer-repair-placeholder.svg')
            ->assertSee('py-3 py-lg-4')
            ->assertSee('pb-3 pb-lg-4');

        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('A booking is a request')
            ->assertSee('Online shop payments are processed through CHiP')
            ->assertSee('Website previews are examples of design work')
            ->assertSee(asset('assets/img/icons/docs.png'), false)
            ->assertSee('py-3 py-lg-4')
            ->assertSee('pb-3 pb-lg-4');
    }

    public function test_policy_hero_images_fit_inside_their_panels(): void
    {
        foreach ([
            route('privacy'),
            route('refund-policy'),
            route('shipping-policy'),
            route('terms'),
            route('data-deletion.instructions'),
        ] as $policyUrl) {
            $this->get($policyUrl)
                ->assertOk()
                ->assertSee('style="height: 220px; object-fit: contain;"', false)
                ->assertDontSee('object-fit-cover', false);
        }
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
