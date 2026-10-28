<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function edit(): View
    {
        return view('company-profile.edit', [
            'companyProfile' => CompanyProfile::current(),
        ]);
    }

    public function websitePackage(): View
    {
        $companyProfile = CompanyProfile::current();
        $pages = is_array($companyProfile->website_pages) && $companyProfile->website_pages !== []
            ? $companyProfile->website_pages
            : [
                'Home / Landing page',
                'Services and packages',
                'Shop and ready-built PCs',
                'Custom PC builder',
                'Repair booking and tracking',
                'Contact us',
            ];

        return view('company-profile.website-package', compact('companyProfile', 'pages'));
    }

    public function updateWebsitePackage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'website_package_notes' => ['nullable', 'string', 'max:5000'],
            'website_pages' => ['required', 'string', 'max:8000'],
        ]);

        $pages = collect(preg_split('/\r\n|\r|\n/', trim($data['website_pages'])) ?: [])
            ->map(fn (string $page): string => trim($page))
            ->filter()
            ->unique()
            ->take(30)
            ->values()
            ->all();

        if ($pages === []) {
            return back()->withErrors(['website_pages' => 'Add at least one website page.'])->withInput();
        }

        $profile = CompanyProfile::query()->firstOrNew(['id' => 'primary']);
        $profile->fill([
            'website_package_notes' => $data['website_package_notes'] ?: null,
            'website_pages' => $pages,
        ]);
        $profile->save();

        return Redirect::route('admin.website-package.edit')
            ->with('success', 'Website package and structure saved.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'legal_name' => ['nullable', 'string', 'max:255'],
            'ssm_registration_number' => ['nullable', 'string', 'max:100'],
            'tax_identification_number' => ['nullable', 'string', 'max:100'],
            'sst_registration_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'facebook_url' => [
                'nullable',
                'url:http,https',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));
                    if (! in_array($host, ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'fb.me'], true)) {
                        $fail('The Facebook page URL must use facebook.com or fb.me.');
                    }
                },
            ],
        ]);

        $profile = CompanyProfile::query()->firstOrNew(['id' => 'primary']);
        $profile->fill($data);
        $profile->save();

        return Redirect::route('admin.company-profile.edit')
            ->with('success', 'Company profile saved.');
    }
}
