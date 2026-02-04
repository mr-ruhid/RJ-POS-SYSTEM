<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Promocode;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SyncService;

class PartnerController extends Controller
{
    /**
     * Partnyorların Siyahısı
     */
    public function index()
    {
        $systemMode = Setting::where('key', 'system_mode')->value('value') ?? 'standalone';
        $partners = Partner::with('promocodes')->latest()->paginate(20);

        return view('admin.partners.index', compact('partners', 'systemMode'));
    }

    /**
     * 1. Serverdən (TELEGRAM API-dən) Gözləyən İstəkləri Çəkmək (AJAX)
     */
    public function fetchTelegramRequests()
    {
        // Tənzimləmələrdən Telegram API linkini götürürük
        $telegramApiUrl = Setting::where('key', 'server_telegram_api')->value('value');
        $apiKey = Setting::where('key', 'client_api_key')->value('value');

        if (!$telegramApiUrl) {
            return response()->json(['error' => 'Telegram API linki təyin edilməyib (Tənzimləmələrə baxın).'], 400);
        }

        try {
            // URL Məntiqi:
            // Əgər istifadəçi ".../api/telegram-sync" yazıbsa, onu ".../api/pending-partners" ilə əvəz edirik.
            // Əgər sadəcə domen yazıbsa, sonuna əlavə edirik.

            $url = $telegramApiUrl;

            if (str_contains($url, '/telegram-sync')) {
                $url = str_replace('/telegram-sync', '/pending-partners', $url);
            } else {
                $url = rtrim($url, '/') . '/api/pending-partners';
            }

            // Node.js serverinə GET sorğusu
            $response = Http::timeout(5)->get($url, [
                'api_key' => $apiKey
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Telegram API cavab vermədi: ' . $response->status()], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Bağlantı xətası: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 2. Telegram İstəyini Təsdiqləyib Partnyor + Promokod Yaratmaq
     */
    public function createFromTelegram(Request $request, SyncService $syncService)
    {
        $request->validate([
            'telegram_chat_id' => 'required|string|unique:partners,telegram_chat_id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'promo_code' => 'required|string|unique:promocodes,code',
            'discount_value' => 'required|numeric|min:0',
            'discount_type' => 'required|in:percent,fixed',
            'commission_percent' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            // A. Partnyor Yaradılır
            $partner = Partner::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'telegram_chat_id' => $request->telegram_chat_id,
                'commission_percent' => $request->commission_percent,
                'balance' => 0,
                'is_active' => true
            ]);

            // B. Promokod Yaradılır
            Promocode::create([
                'code' => strtoupper($request->promo_code),
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'partner_id' => $partner->id,
                'is_active' => true,
                'used_count' => 0, // [DÜZƏLİŞ] Sütun adı used_count
                'usage_limit' => null
            ]);

            DB::commit();

            // C. Telegram API-yə Xoşgəldin Mesajı Göndərmək
            $syncService->sendPartnerWelcome(
                $partner,
                $request->promo_code,
                $request->discount_value . ($request->discount_type == 'percent' ? '%' : ' AZN'),
                $request->commission_percent
            );

            return back()->with('success', 'Partnyor uğurla yaradıldı və Telegrama bildiriş göndərildi!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Xəta: ' . $e->getMessage());
        }
    }

    /**
     * 3. Partnyor Parametrlərini Yeniləmək (Edit)
     */
    public function updateConfig(Request $request, Partner $partner)
    {
        $request->validate([
            'commission_percent' => 'required|numeric|min:0|max:100',
            'promo_code' => 'required|string',
            'discount_value' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $partner->update(['commission_percent' => $request->commission_percent]);

            $promo = $partner->promocodes()->first();
            if ($promo) {
                $promo->update([
                    'code' => strtoupper($request->promo_code),
                    'discount_value' => $request->discount_value
                ]);
            } else {
                Promocode::create([
                    'partner_id' => $partner->id,
                    'code' => strtoupper($request->promo_code),
                    'discount_type' => 'percent',
                    'discount_value' => $request->discount_value,
                    'is_active' => true,
                    'used_count' => 0
                ]);
            }

            DB::commit();
            return back()->with('success', 'Tənzimləmələr yeniləndi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Yeniləmə xətası: ' . $e->getMessage());
        }
    }

    /**
     * 4. Ödəniş Etmək
     */
    public function payout(Request $request, Partner $partner)
    {
        $request->validate(['amount' => 'required|numeric|min:0.01|max:' . $partner->balance]);

        // Balansı azaldırıq
        $partner->decrement('balance', $request->amount);

        // Ödəniş tarixçəsi (Transaction) varsa bura əlavə edilə bilər

        return back()->with('success', number_format($request->amount, 2) . ' AZN ödəniş edildi.');
    }

    /**
     * 5. Silmək
     */
    public function destroy(Partner $partner)
    {
        $partner->delete();
        return back()->with('success', 'Partnyor silindi.');
    }

    /**
     * 6. Əllə Partnyor Yaratmaq
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        Partner::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'balance' => 0,
            'is_active' => true,
            'commission_percent' => 0 // Əllə yaradanda 0 olur, sonra editlənir
        ]);

        return back()->with('success', 'Partnyor əlavə edildi. Zəhmət olmasa tənzimləmələri edin.');
    }

    public function getStats(Partner $partner)
    {
        return response()->json([
            'balance' => $partner->balance,
            'commission_percent' => $partner->commission_percent,
            'promocodes' => $partner->promocodes
        ]);
    }
}
