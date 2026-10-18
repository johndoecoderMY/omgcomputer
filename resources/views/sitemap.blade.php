<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ([url('/'), route('bookings.create'), route('privacy'), route('toc'), route('terms')] as $page)
        <url>
            <loc>{{ $page }}</loc>
            <lastmod>{{ now()->toDateString() }}</lastmod>
        </url>
    @endforeach
</urlset>
