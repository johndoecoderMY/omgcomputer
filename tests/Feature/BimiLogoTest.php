<?php

namespace Tests\Feature;

use DOMDocument;
use Tests\TestCase;

class BimiLogoTest extends TestCase
{
    public function test_bimi_logo_is_a_small_svg_tiny_ps_vector_asset(): void
    {
        $path = public_path('bimi/logo.svg');
        $this->assertFileExists($path);
        $this->assertLessThanOrEqual(32 * 1024, filesize($path));

        $document = new DOMDocument;
        $this->assertTrue($document->load($path, LIBXML_NONET));
        $root = $document->documentElement;

        $this->assertSame('tiny-ps', $root->getAttribute('baseProfile'));
        $this->assertSame('1.2', $root->getAttribute('version'));
        $this->assertSame('512px', $root->getAttribute('width'));
        $this->assertSame('512px', $root->getAttribute('height'));
        $this->assertSame(1, $document->getElementsByTagName('title')->length);
        $this->assertSame(1, $document->getElementsByTagName('desc')->length);
        $this->assertSame(0, $document->getElementsByTagName('image')->length);
        $this->assertSame(0, $document->getElementsByTagName('script')->length);
    }
}
