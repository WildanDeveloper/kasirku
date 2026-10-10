<?php

namespace App\Http\Controllers;

use App\Models\Setting;
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
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, (string) ($value ?? ''));
        }

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
