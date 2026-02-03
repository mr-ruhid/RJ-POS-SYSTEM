<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ServerSyncController;
use App\Http\Controllers\Api\TelegramWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Yeni "Ağıllı Sinxronizasiya" (Bi-Directional Sync) sistemi.
| Kassa (Client) və Server (Master) arasında paket mübadiləsi.
|
*/

Route::prefix('v1')->group(function () {

    // --- SİNXRONİZASİYA (SYNC) ---

    // 1. Yuxarı Axın (UPLOAD): Kassa -> Server
    // Satışları, yeni məhsulları və promokodları bir paketdə qəbul edir.
    // Cavab olaraq real stokları qaytarır.
    Route::post('/sync/upload', [ServerSyncController::class, 'uploadData']);

    // 2. Aşağı Axın (DOWNLOAD): Server -> Kassa
    // Kassada olmayan yeni məlumatları (və ya dəyişən stokları) göndərir.
    Route::get('/sync/download', [ServerSyncController::class, 'downloadData']);

    // 3. Əlaqə Testi (Ping)
    Route::get('/check-connection', function() {
        return response()->json(['status' => 'success', 'message' => 'Bağlantı uğurludur! (v2 Sync)']);
    });


    // --- TELEGRAM BOT WEBHOOK ---
    // Telegram-dan gələn mesajları tutur
    Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

});
