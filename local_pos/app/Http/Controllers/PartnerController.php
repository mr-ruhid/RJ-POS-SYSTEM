<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Setting; // Ayarları yoxlamaq üçün
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function index()
    {
        // Sistemin rejimini yoxlayırıq
        $systemMode = Setting::where('key', 'system_mode')->value('value') ?? 'standalone';

        // Əgər Standart rejimdirsə, boş siyahı göndər (View-da xəbərdarlıq çıxacaq)
        // Amma Server/Client rejimindədirsə, partnyorları gətir
        $partners = ($systemMode === 'standalone')
                    ? collect([]) // Boş kolleksiya
                    : Partner::latest()->paginate(20);

        return view('admin.partners.index', compact('partners', 'systemMode'));
    }

    // Yeni Partnyor
    public function store(Request $request)
    {
        // Standart rejimdə əməliyyatı qadağan edirik
        $systemMode = Setting::where('key', 'system_mode')->value('value') ?? 'standalone';
        if($systemMode === 'standalone') {
            return back()->with('error', 'Lokal rejimdə partnyor əlavə edilə bilməz!');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'telegram_chat_id' => 'nullable|string',
        ]);

        Partner::create($request->all());

        return back()->with('success', 'Partnyor uğurla əlavə edildi!');
    }

    // Silmək
    public function destroy(Partner $partner)
    {
        $partner->delete();
        return back()->with('success', 'Partnyor silindi.');
    }
}
