@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto"
     x-data="{
        mode: '{{ old('system_mode', $settings['system_mode'] ?? 'standalone') }}',
        copySuccess: false,
        testing: false,
        testResult: null,
        testMessage: '',

        copyApiKey() {
            const key = document.getElementById('apiKeyInput').value;
            navigator.clipboard.writeText(key);
            this.copySuccess = true;
            setTimeout(() => this.copySuccess = false, 2000);
        },

        async checkConnection() {
            this.testing = true;
            this.testResult = null;
            this.testMessage = '';

            try {
                // Mövcud 'dashboard.sync' routuna sorğu göndəririk
                // QEYD: Bu funksiya bazada YADDA SAXLANILMIŞ məlumatları yoxlayır.
                const response = await fetch('{{ route('dashboard.sync') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest' // <--- VACİB: Laravel-in AJAX olduğunu anlaması üçün
                    }
                });

                // Serverdən gələn xam mətni alırıq (JSON olmaya bilər)
                const rawText = await response.text();
                let data;

                try {
                    data = JSON.parse(rawText);
                } catch (e) {
                    // Əgər JSON deyilsə, deməli serverdə ciddi xəta var (HTML və ya 404)
                    throw new Error('Serverdən düzgün cavab gəlmədi. Gələn cavab:\n' + rawText.substring(0, 300) + '...');
                }

                if (response.ok && data.success) {
                    this.testResult = 'success';
                    this.testMessage = 'Bağlantı uğurludur! \n' + data.message;
                } else {
                    this.testResult = 'error';
                    this.testMessage = 'Xəta: ' + (data.message || 'Naməlum xəta') + '\n(Status: ' + response.status + ')';
                }
            } catch (error) {
                this.testResult = 'error';
                this.testMessage = 'Sistem Xətası: ' + error.message;
            } finally {
                this.testing = false;
            }
        }
     }">

    <form action="{{ route('settings.server.update') }}" method="POST">
        @csrf

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Server və Sinxronizasiya</h1>
                <p class="text-sm text-gray-500 mt-1">Sistemin işləmə rejimini təyin edin</p>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-bold shadow-md transition flex items-center">
                <i class="fa-solid fa-floppy-disk mr-2"></i> Yadda Saxla
            </button>
        </div>

        <!-- Uğurlu Mesaj -->
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center">
                <i class="fa-solid fa-check-circle mr-2"></i>
                <div>
                    <p class="font-bold">Uğurlu!</p>
                    <p>{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Xəta Mesajları -->
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0"><i class="fa-solid fa-circle-exclamation text-red-400"></i></div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Diqqət! Xəta baş verdi:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Rejim Seçimi -->
            <div class="md:col-span-1 space-y-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-bold text-gray-800 mb-4">İşləmə Rejimi</h3>

                    <div class="space-y-3">
                        <!-- Standart -->
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer transition hover:bg-gray-50"
                               :class="mode === 'standalone' ? 'border-gray-500 ring-1 ring-gray-500 bg-gray-50' : 'border-gray-200'">
                            <input type="radio" name="system_mode" value="standalone" x-model="mode"
                                   class="mt-1 text-gray-600 focus:ring-gray-500"
                                   {{ old('system_mode', $settings['system_mode'] ?? 'standalone') == 'standalone' ? 'checked' : '' }}>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Standart (Lokal)</span>
                                <span class="block text-xs text-gray-500">İnternetsiz, tək cihaz.</span>
                            </div>
                        </label>

                        <!-- Server -->
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer transition hover:bg-blue-50"
                               :class="mode === 'server' ? 'border-blue-600 ring-1 ring-blue-600 bg-blue-50' : 'border-gray-200'">
                            <input type="radio" name="system_mode" value="server" x-model="mode"
                                   class="mt-1 text-blue-600 focus:ring-blue-500"
                                   {{ old('system_mode', $settings['system_mode'] ?? '') == 'server' ? 'checked' : '' }}>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Əsas Server (Master)</span>
                                <span class="block text-xs text-gray-500">Mərkəzi baza rolunu oynayır.</span>
                            </div>
                        </label>

                        <!-- Kliyent -->
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer transition hover:bg-green-50"
                               :class="mode === 'client' ? 'border-green-600 ring-1 ring-green-600 bg-green-50' : 'border-gray-200'">
                            <input type="radio" name="system_mode" value="client" x-model="mode"
                                   class="mt-1 text-green-600 focus:ring-green-500"
                                   {{ old('system_mode', $settings['system_mode'] ?? '') == 'client' ? 'checked' : '' }}>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Mağaza (Client)</span>
                                <span class="block text-xs text-gray-500">Serverə məlumat göndərir.</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tənzimləmələr (Dinamik) -->
            <div class="md:col-span-2">

                <!-- STANDART REJİM -->
                <div x-show="mode === 'standalone'" class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center h-full flex flex-col justify-center items-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-desktop text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Lokal Rejim</h3>
                    <p class="text-gray-500 mt-2 max-w-sm">
                        Bu rejimdə sistem heç bir yerə qoşulmur. Bütün məlumatlar yalnız bu kompüterdə qalır.
                    </p>
                </div>

                <!-- SERVER REJİMİ -->
                <div x-show="mode === 'server'" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-full" x-cloak>
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fa-solid fa-server text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Server Parametrləri</h3>
                            <p class="text-sm text-gray-500">Bu məlumatları mağazadakı kompüterlərə daxil edin</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sizin API Açarınız (Secret Key)</label>
                            <div class="relative">
                                <input type="text" id="apiKeyInput" readonly value="{{ $settings['server_api_key'] ?? 'Yaradılır...' }}"
                                       class="w-full pl-4 pr-24 py-3 rounded-lg border-gray-300 bg-gray-50 font-mono text-sm text-gray-600 focus:ring-0">

                                <button type="button" @click="copyApiKey()"
                                        class="absolute right-1 top-1 bottom-1 px-3 bg-white border border-gray-200 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:border-blue-300 transition flex items-center">
                                    <i class="fa-regular" :class="copySuccess ? 'fa-circle-check text-green-500' : 'fa-copy'"></i>
                                    <span class="ml-2" x-text="copySuccess ? 'Kopyalandı!' : 'Kopyala'"></span>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2 flex items-center">
                                <i class="fa-solid fa-triangle-exclamation text-yellow-500 mr-1"></i>
                                Bu açarı heç kimlə paylaşmayın!
                            </p>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-lg text-sm text-blue-800 border border-blue-100">
                            <strong>Qeyd:</strong> Bu rejimdə sistem məlumatları qəbul edir və mərkəzi baza rolunu oynayır.
                        </div>
                    </div>
                </div>

                <!-- KLİYENT (MAĞAZA) REJİMİ -->
                <div x-show="mode === 'client'" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-full" x-cloak>
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fa-solid fa-store text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Mağaza Qoşulması</h3>
                            <p class="text-sm text-gray-500">Mərkəzi serverə qoşulmaq üçün məlumatları daxil edin</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Server Ünvanı (URL)</label>
                            <input type="url" name="server_url" value="{{ old('server_url', $settings['server_url'] ?? '') }}"
                                   :disabled="mode !== 'client'"
                                   class="w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500 shadow-sm"
                                   placeholder="https://pos.sizin-saytiniz.com">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">API Açarı</label>
                            <input type="password" name="client_api_key" value="{{ old('client_api_key', $settings['client_api_key'] ?? '') }}"
                                   :disabled="mode !== 'client'"
                                   class="w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500 shadow-sm font-mono"
                                   placeholder="rj_xxxxxxxxxxxxxxxxxxxx">
                        </div>

                        <!-- Bağlantını Yoxla Düyməsi -->
                        <div class="pt-2 border-t border-gray-100 mt-4">
                            <button type="button"
                                    @click="checkConnection()"
                                    :disabled="testing"
                                    class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">

                                <span x-show="!testing"><i class="fa-solid fa-plug-circle-bolt mr-2 text-green-600"></i> Bağlantını Yoxla</span>
                                <span x-show="testing"><i class="fa-solid fa-circle-notch fa-spin mr-2 text-blue-500"></i> Yoxlanılır...</span>
                            </button>

                            <!-- Test Nəticəsi (Genişləndirilib) -->
                            <div x-show="testResult" class="mt-3 p-3 rounded-md text-sm break-words whitespace-pre-wrap"
                                 :class="testResult === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
                                <div class="flex items-start">
                                    <i class="fa-solid mt-0.5 mr-2" :class="testResult === 'success' ? 'fa-check-circle' : 'fa-circle-xmark'"></i>
                                    <span x-text="testMessage"></span>
                                </div>
                            </div>

                            <p class="text-xs text-gray-400 mt-2 text-center">
                                Qeyd: Yoxlamaq üçün əvvəlcə "Yadda Saxla" düyməsini sıxın.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection
