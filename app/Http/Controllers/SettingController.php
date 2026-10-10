<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('settings.edit');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:500'],
            'store_phone' => ['nullable', 'string', 'max:20'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'printer_width' => ['nullable', 'in:58,80,a4'],
            'printer_auto_print' => ['nullable', 'in:0,1'],
            'loyalty_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'loyalty_point_value' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'loyalty_max_redeem_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'wa_url' => ['nullable', 'url', 'max:255'],
            'wa_token' => ['nullable', 'string', 'max:255'],
            'wa_admin_number' => ['nullable', 'string', 'max:20'],
            'low_stock_alert' => ['nullable', 'in:0,1'],
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, (string) ($value ?? ''));
        }

        ActivityLogger::record('settings.updated', 'Mengubah pengaturan toko');

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
