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
