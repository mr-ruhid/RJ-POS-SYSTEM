<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RJUpdaterService
{
    // 1. Tənzimləmələr
    protected $serverUrl = "https://pos.ruhidjavadov.site/api/v1/check";
    protected $apiKey    = "rj_live_982348729384729384";

    // Test üçün versiyanı aşağı saxlayırıq (0.9.0)
    protected $currentVersion = "RJ Pos v2 Build 1.1";

    public function checkUpdate()
    {
        try {
            // Localhost yoxlaması və Domain spoofing
            $domain = request()->getHost();
            if ($domain == 'https://vmi3036725.contaboserver.net' || $domain == 'localhost') {
                $domain = 'local-test-env';
            }

            // 2. API Sorğusunun Hazırlanması
            $response = Http::timeout(30)
                ->asForm()
                ->post($this->serverUrl, [
                    'api_key' => $this->apiKey,
                    'version' => $this->currentVersion,
                    'domain'  => $domain
                ]);

            // 3. Cavabın Alınması
            if ($response->successful()) {
                $data = $response->json();

                // --- DEBUG BLOCK START ---
                // Əgər server "Yeniləmə yoxdur" deyirsə, gələn cavabı ekrana çıxaraq ki, səbəbi görək.
                if (empty($data['update_available'])) {
                    // JSON datanı oxunaqlı formata salırıq
                    $debugInfo = json_encode($data, JSON_UNESCAPED_UNICODE);
                    $data['message'] = "Serverdən cavab gəldi, lakin 'update_available' false oldu. Serverdən gələn data: " . $debugInfo;
                }
                // --- DEBUG BLOCK END ---

                return $data;
            }

            return [
                'update_available' => false,
                'message' => 'Server Xətası (HTTP ' . $response->status() . ')'
            ];

        } catch (\Exception $e) {
            Log::error("RJUpdater Error: " . $e->getMessage());
            return [
                'update_available' => false,
                'message' => 'Bağlantı xətası: ' . $e->getMessage()
            ];
        }
    }

    public function getCurrentVersion()
    {
        return $this->currentVersion;
    }
}https://vmi3036725.contaboserver.net
