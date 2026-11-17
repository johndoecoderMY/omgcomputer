@extends('layouts.app')

@section('title', 'Generate QR Code With Logo')
@section('meta_description', 'Create a branded QR code with your logo, custom colors, and downloadable PNG output for your website, business card, or social profile.')

@section('content')
    <div class="container-fluid py-3" data-qr-default-logo="{{ asset('assets/img/omg/logo.png') }}">
        <x-page-header eyebrow="Tools" title="Generate QR code with logo"
            description="Turn any URL or text into a branded QR image with your company logo included. Download the final PNG in one click." />

        <div class="row g-3 align-items-stretch">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 p-xl-5">
                        <h2 class="h4 fw-bold text-1000 mb-4">Customize your QR code</h2>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="qr-text">Content or URL</label>
                            <textarea id="qr-text" class="form-control" rows="4">https://omgcomputer.my</textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="qr-color">QR color</label>
                                <input id="qr-color" type="color" value="#172033" class="form-control form-control-color w-100" aria-label="QR color">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="qr-bg">Background</label>
                                <input id="qr-bg" type="color" value="#ffffff" class="form-control form-control-color w-100" aria-label="Background color">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="qr-size">Size</label>
                                <input id="qr-size" type="range" min="220" max="700" step="10" value="420" class="form-range">
                                <div class="small text-700" id="qr-size-label">420 px</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="qr-margin">Margin</label>
                                <input id="qr-margin" type="range" min="0" max="10" step="1" value="4" class="form-range">
                                <div class="small text-700" id="qr-margin-label">4 modules</div>
                                <div class="form-text">Four modules are recommended; smaller margins may reduce scan reliability.</div>
                            </div>
                        </div>

                        <div class="row g-3 mt-1 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="logo-size">Logo size</label>
                                <input id="logo-size" type="range" min="8" max="20" step="1" value="12" class="form-range">
                                <div class="small text-700" id="logo-size-label">12%</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="logo-input">Logo image</label>
                            <input id="logo-input" class="form-control" type="file" accept="image/*">
                            <div class="form-text">Optional. A smaller logo generally scans more reliably.</div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button id="generate-btn" type="button" class="btn btn-primary px-4">Generate QR</button>
                            <button id="download-btn" type="button" class="btn btn-outline-primary px-4">Download PNG</button>
                        </div>
                        <div id="qr-error" class="alert alert-danger d-none mt-3 mb-0" role="alert"></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100 overflow-hidden">
                    <div class="card-body p-3 p-lg-4 d-flex justify-content-center align-items-center min-vh-40">
                        <div class="qr-stage">
                            <canvas id="qr-canvas" width="420" height="420" aria-label="Generated QR code"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @vite('resources/js/qr-generator.js')
@endsection
