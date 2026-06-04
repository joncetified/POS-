<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Support\CafeCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index', [
            'store' => CafeCatalog::store(),
            'settings' => CompanySetting::current(CafeCatalog::defaultStore()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = CompanySetting::current(CafeCatalog::defaultStore());

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'manager_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:160'],
            'contact_phone' => ['nullable', 'string', 'max:80'],
            'contact_whatsapp' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'payment_barcode' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }

            $validated['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        if ($request->hasFile('payment_barcode')) {
            if ($settings->payment_barcode_path) {
                Storage::disk('public')->delete($settings->payment_barcode_path);
            }

            $validated['payment_barcode_path'] = $request->file('payment_barcode')->store('payment-barcodes', 'public');
        }

        unset($validated['logo']);
        unset($validated['payment_barcode']);

        $settings->update($validated);

        return back()->with('status', 'Pengaturan website sudah disimpan.');
    }
}
