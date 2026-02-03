<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $botToken;

    public function __construct()
    {
        // Tokeni əvvəlcə bazadan (Admin paneldən), tapmasa .env faylından götürür
        // Cache istifadə etmək yaxşı olardı, amma hələlik birbaşa bazadan oxuyuruq
        $this->botToken = Setting::where('key', 'telegram_bot_token')->value('value') ?? env('TELEGRAM_BOT_TOKEN');
    }

    /**
     * Mesaj göndərmək üçün əsas funksiya
     * * @param string $chatId - Alıcının Telegram ID-si
     * @param string $message - Göndəriləcək mətn (HTML dəstəkləyir)
     * @return boolean - Uğurlu olub-olmaması
     */
    public function sendMessage($chatId, $message)
    {
        // Token və ya ID yoxdursa, əməliyyatı dayandır
        if (empty($this->botToken) || empty($chatId)) {
            // Log::warning('TelegramService: Token və ya Chat ID tapılmadı.');
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

            $response = Http::timeout(5)->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML', // Bold, Italic yazmaq üçün
                'disable_web_page_preview' => true
            ]);

            if ($response->successful()) {
                return true;
            } else {
                // Xəta olarsa loglara yazırıq (storage/logs/laravel.log)
                Log::error("Telegram API Xətası: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Telegram Bağlantı Xətası: " . $e->getMessage());
            return false;
        }
    }
}
