<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StaffPwaController extends Controller
{
    public function manifest(): BinaryFileResponse
    {
        return response()->file(resource_path('pwa/staff.webmanifest'), [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function serviceWorker(): BinaryFileResponse
    {
        return response()->file(resource_path('pwa/staff-service-worker.js'), [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'private, no-store',
            'Service-Worker-Allowed' => '/',
        ]);
    }
}
