<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RJUpdaterService;

class SystemUpdateController extends Controller
{
    protected $updater;

    public function __construct(RJUpdaterService $updater)
    {
        $this->updater = $updater;
    }

    public function index()
    {
        // 1. Hazırkı versiyanı alırıq
        $currentVersion = $this->updater->getCurrentVersion();

        // 2. API-dən cavabı alırıq
        $apiResponse = $this->updater->checkUpdate();

        // Dəyişənləri hazırlayırıq
        $isUpdateAvailable = false;
        $globalNotification = null;
        $updateData = null;
        $serverMessage = $apiResponse['message'] ?? '';

        // A. Qlobal Bildiriş Varmı? (Sizin PHP kodundakı 'global_notification')
        if (isset($apiResponse['global_notification']) && !empty($apiResponse['global_notification'])) {
            $globalNotification = $apiResponse['global_notification'];
        }

        // B. Yeni Versiya Varmı? (Sizin PHP kodundakı 'update_available')
        if (isset($apiResponse['update_available']) && $apiResponse['update_available'] === true) {
            $isUpdateAvailable = true;
            // Məlumatlar 'data' massivinin içində gəlir (version, notes, download_url)
            $updateData = $apiResponse['data'] ?? [];
        }

        return view('system.updates.index', compact(
            'currentVersion',
            'isUpdateAvailable',
            'globalNotification',
            'updateData',
            'serverMessage'
        ));
    }
}
