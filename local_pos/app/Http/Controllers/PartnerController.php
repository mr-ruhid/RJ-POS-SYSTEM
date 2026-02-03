<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Promocode;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerController extends Controller
{
    /**
     * Partnyorların Siyahısı
     */
    public function index()
    {
        $systemMode = Setting::where('key', 'system_mode')->value('value') ?? 'standalone';

        // Balansa görə sıralaya bilərik və ya son əlavə olunanlara görə
        $partners = Partner::with('promocodes')->latest()->paginate(20);

        return view('admin.partners.index', compact('partners', 'systemMode'));
    }

    /**
     * 1. Serverdən (Node.js) Gözləyən Telegram İstəklərini Çəkmək (AJAX)
     */
    public function fetchTelegramRequests()
    {
        $serverUrl = Setting::where('key', 'server_url')->value('value');
        $apiKey = Setting::where('key', 'client_api_key')->value('value');

        if (!$serverUrl) {
            return response()->json(['error' => 'Server URL təyin edilməyib.'], 400);
        }

        try {
            // Node.js API-ə sorğu göndəririk
            // URL: https://vmi.../api/pending-partners
            $response = Http::timeout(5)->get(rtrim($serverUrl, '/') . '/api/pending-partners', [
                'api_key' => $apiKey
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Serverdən cavab alınmadı: ' . $response->status()], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bağlantı xətası: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 2. Telegram İstəyini Təsdiqləyib Partnyor və Promokod Yaratmaq
     */
    public function createFromTelegram(Request $request)
    {
        // Validasiya
        $request->validate([
            'telegram_chat_id' => 'required|string|unique:partners,telegram_chat_id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',

            // Promokod Məlumatları
            'promo_code' => 'required|string|unique:promocodes,code',
            'discount_value' => 'required|numeric|min:0', // Endirim faizi və ya məbləği
            'discount_type' => 'required|in:percent,fixed',

            // Partnyor Qazancı
            'commission_percent' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            // A. Partnyoru Yaradırıq
            $partner = Partner::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'telegram_chat_id' => $request->telegram_chat_id,
                'commission_percent' => $request->commission_percent, // Partnyorun qazancı (məs: 5%)
                'balance' => 0,
                'is_active' => true
            ]);

            // B. Promokodu Yaradırıq və Partnyora Bağlayırıq
            Promocode::create([
                'code' => strtoupper($request->promo_code),
                'discount_type' => $request->discount_type, // 'percent' və ya 'fixed'
                'discount_value' => $request->discount_value, // Müştəriyə ediləcək endirim
                'partner_id' => $partner->id, // Əlaqə
                'is_active' => true,
                'usage_limit' => null, // Sonsuz limit
                'orders_count' => 0
            ]);

            // C. Serverə (Node.js) təsdiq mesajı göndərmək
            // Biz burada Socket emit edə bilmirik (PHP-dir), amma bu məlumat
            // növbəti sinxronizasiyada serverə gedəcək və server özü başa düşəcək.

            DB::commit();
            return back()->with('success', 'Partnyor və Promokod uğurla yaradıldı!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Partner Create Error: ' . $e->getMessage());
            return back()->with('error', 'Xəta baş verdi: ' . $e->getMessage());
        }
    }

    /**
     * 3. Partnyora Ödəniş Etmək (Balansdan Çıxmaq)
     */
    public function payout(Request $request, Partner $partner)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $partner->balance,
            'note' => 'nullable|string'
        ]);

        // Balansı azaldırıq (Sadə yanaşma)
        // Daha təkmil sistemdə 'transactions' cədvəlinə də yazmaq lazımdır (History üçün)
        $partner->decrement('balance', $request->amount);

        // Qeyd: Bu dəyişiklik də növbəti sinxronizasiyada serverə gedəcək

        return back()->with('success', number_format($request->amount, 2) . ' AZN ödəniş edildi. Balans yeniləndi.');
    }

    /**
     * 4. Partnyor Parametrlərini Yeniləmək (Edit)
     */
    public function updateConfig(Request $request, Partner $partner)
    {
        $request->validate([
            'commission_percent' => 'required|numeric|min:0|max:100',
            'promo_code' => 'required|string', // Promokod dəyişə bilər
            'discount_value' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Partnyor komissiyasını yenilə
            $partner->update(['commission_percent' => $request->commission_percent]);

            // Promokodu yenilə (İlk tapılan promokodu)
            $promo = $partner->promocodes()->first();
            if ($promo) {
                $promo->update([
                    'code' => strtoupper($request->promo_code),
                    'discount_value' => $request->discount_value
                ]);
            } else {
                // Yoxdursa yarat
                Promocode::create([
                    'partner_id' => $partner->id,
                    'code' => strtoupper($request->promo_code),
                    'discount_type' => 'percent',
                    'discount_value' => $request->discount_value,
                    'is_active' => true
                ]);
            }

            DB::commit();
            return back()->with('success', 'Partnyor parametrləri yeniləndi.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Yeniləmə xətası: ' . $e->getMessage());
        }
    }

    // Silmək
    public function destroy(Partner $partner)
    {
        $partner->delete(); // Cascade ilə promokodlar da silinəcək (əgər migration düzgündürsə)
        return back()->with('success', 'Partnyor silindi.');
    }

    // AJAX ilə statistika (Opsional - Modalda göstərmək üçün)
    public function getStats(Partner $partner)
    {
        // Partnyorun ümumi qazancı (ödənişlər daxil olmadan)
        // Bunu order tarixçəsindən hesablamaq lazımdır
        // Hələlik sadə balans qaytarırıq
        return response()->json([
            'balance' => $partner->balance,
            'commission_percent' => $partner->commission_percent,
            'promocodes' => $partner->promocodes
        ]);
    }
}
