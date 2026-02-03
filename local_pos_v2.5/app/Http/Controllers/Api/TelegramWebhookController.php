<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramService $telegram)
    {
        $update = $request->all();

        // Log::info('Telegram Update:', $update); // Debug üçün

        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';

            // İstifadəçi məlumatları
            $firstName = $message['from']['first_name'] ?? 'Naməlum';
            $username = $message['from']['username'] ?? null;
            $fullName = $firstName . ($username ? " (@$username)" : "");

            // Yalnız /start əmrinə reaksiya veririk
            if ($text === '/start') {

                // Bazada bu ID varmı?
                $partner = Partner::where('telegram_chat_id', $chatId)->first();

                if (!$partner) {
                    // Yoxdursa, yeni partnyor kimi qeydiyyata alırıq
                    Partner::create([
                        'name' => $fullName,
                        'telegram_chat_id' => $chatId,
                        'balance' => 0,
                        'is_active' => true
                    ]);

                    $responseMsg = "Salam, <b>$firstName</b>! 👋\n";
                    $responseMsg .= "Siz sistemdə qeydiyyata alındınız.\n";
                    $responseMsg .= "Admin sizə xüsusi <b>Promokod</b> təyin etdikdən sonra burada bildiriş alacaqsınız.";

                    $telegram->sendMessage($chatId, $responseMsg);
                } else {
                    $telegram->sendMessage($chatId, "Siz artıq qeydiyyatdan keçmisiniz. Balansınız: " . $partner->balance . " ₼");
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
