<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.company', [
            'company' => CompanySetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $company = CompanySetting::current();

        $data = [
            'name' => $validated['name'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
        ];

        if ($request->boolean('remove_logo') && $company->logo_path) {
            $path = public_path($company->logo_path);
            if (is_file($path)) {
                @unlink($path);
            }
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                $old = public_path($company->logo_path);
                if (is_file($old)) {
                    @unlink($old);
                }
            }

            $file = $request->file('logo');
            if (! is_dir(public_path('logos'))) {
                @mkdir(public_path('logos'), 0755, true);
            }
            $name = 'logo_'.time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('logos'), $name);
            $data['logo_path'] = 'logos/'.$name;
        }

        $company->update($data);

        return redirect()->route('settings.company')->with('success', 'Company profile updated.');
    }

    public function removeLogo(): RedirectResponse
    {
        $company = CompanySetting::current();

        if ($company->logo_path) {
            $path = public_path($company->logo_path);
            if (is_file($path)) {
                @unlink($path);
            }
            $company->update(['logo_path' => null]);
        }

        return redirect()->route('settings.company')->with('success', 'Company logo removed.');
    }
}
