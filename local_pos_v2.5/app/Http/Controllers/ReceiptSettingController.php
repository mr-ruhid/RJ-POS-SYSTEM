<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class ReceiptSettingController extends Controller
{
    public function index()
    {
        // Mövcud ayarları çəkirik
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('admin.settings.receipt', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'receipt_template' => 'required|string',
        ]);

        // Şablon seçimini yadda saxlayırıq
        Setting::updateOrCreate(
            ['key' => 'receipt_template'],
            ['value' => $request->receipt_template]
        );

        // Digər opsiyalar (Checkboxlar)
        $options = ['receipt_show_logo', 'receipt_show_qr', 'receipt_show_currency_symbol'];

        foreach ($options as $option) {
            Setting::updateOrCreate(
                ['key' => $option],
                ['value' => $request->has($option) ? '1' : '0']
            );
        }

        return back()->with('success', 'Qəbz tənzimləmələri yeniləndi!');
    }
}
