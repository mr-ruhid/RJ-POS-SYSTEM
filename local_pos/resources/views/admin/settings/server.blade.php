@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto"
     x-data="{
        mode: '{{ old('system_mode', $settings['system_mode'] ?? 'standalone') }}',
        testing: false,
        testResult: null,
        testMessage: '',

        async checkConnection() {
            this.testing = true;
            this.testResult = null;
            this.testMessage = '';

            try {
                // 'dashboard.sync' routuna sorğu göndəririk (Cari ayarları yoxlamaq üçün)
                const response = await fetch('{{ route('dashboard.sync') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const rawText = await response.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (e) {
                    throw new Error('Serverdən düzgün cavab gəlmədi: ' + rawText.substring(0, 100));
                }

                if (response.ok && data.success) {
                    this.testResult = 'success';
                    this.testMessage = 'Bağlantı uğurludur! ' + data.message;
                } else {
                    this.testResult = 'error';
                    this.testMessage = 'Xəta: ' + (data.message || 'Naməlum xəta');
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

        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center">
                <i class="fa-solid fa-check-circle mr-2"></i> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
                <ul class="list-disc pl-5 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Rejim Seçimi -->
            <div class="md:col-span-1 space-y-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-bold text-gray-800 mb-4">İşləmə Rejimi</h3>

                    <div class="space-y-3">
                        <!-- Offline (Lokal) -->
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer transition hover:bg-gray-50"
                               :class="mode === 'standalone' ? 'border-gray-500 ring-1 ring-gray-500 bg-gray-50' : 'border-gray-200'">
                            <input type="radio" name="system_mode" value="standalone" x-model="mode"
                                   class="mt-1 text-gray-600 focus:ring-gray-500"
                                   {{ old('system_mode', $settings['system_mode'] ?? 'standalone') == 'standalone' ? 'checked' : '' }}>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Offline (Lokal)</span>
                                <span class="block text-xs text-gray-500">İnternetsiz rejim. Məlumat göndərilmir.</span>
                            </div>
                        </label>

                        <!-- Mağaza (Client) -->
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer transition hover:bg-green-50"
                               :class="mode === 'client' ? 'border-green-600 ring-1 ring-green-600 bg-green-50' : 'border-gray-200'">
                            <input type="radio" name="system_mode" value="client" x-model="mode"
                                   class="mt-1 text-green-600 focus:ring-green-500"
                                   {{ old('system_mode', $settings['system_mode'] ?? '') == 'client' ? 'checked' : '' }}>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Mağaza (Client)</span>
                                <span class="block text-xs text-gray-500">Mərkəzi serverə qoşulur.</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tənzimləmələr (Dinamik) -->
            <div class="md:col-span-2">

                <!-- OFFLINE REJİM -->
                <div x-show="mode === 'standalone'" class="bg-white rounded-xl shadow-sm border border-gray-200 p-10 text-center h-full flex flex-col justify-center items-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fa-solid fa-desktop text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Lokal Rejim</h3>
                    <p class="text-gray-500 mt-2 max-w-sm">
                        Sistem tamamilə avtonom işləyir. Heç bir məlumat kənara çıxmır.
                    </p>
                </div>

                <!-- CLIENT REJİMİ -->
                <div x-show="mode === 'client'" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-full" x-cloak>
                    <div class="flex items-center mb-6">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                            <i class="fa-solid fa-link text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Server Bağlantısı</h3>
                            <p class="text-sm text-gray-500">Node.js serveri və Telegram API əlaqəsi</p>
                        </div>
                    </div>

                    <div class="space-y-5">

                        <!-- Server URL -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Server Monitor URL</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fa-solid fa-globe"></i>
                                </span>
                                <input type="url" name="server_url" value="{{ old('server_url', $settings['server_url'] ?? '') }}"
                                       :disabled="mode !== 'client'"
                                       class="w-full pl-10 rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500 shadow-sm"
                                       placeholder="https://monitor.sizin-sayt.com">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Nümunə: <code>https://vmi3036725.contaboserver.net/monitor</code></p>
                        </div>

                        <!-- Server Telegram API (YENİ) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Server Telegram API</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-blue-400">
                                    <i class="fa-brands fa-telegram"></i>
                                </span>
                                <input type="url" name="server_telegram_api" value="{{ old('server_telegram_api', $settings['server_telegram_api'] ?? '') }}"
                                       :disabled="mode !== 'client'"
                                       class="w-full pl-10 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                       placeholder="https://api.sizin-server.com/telegram">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Telegram bot xidməti üçün API ünvanı.</p>
                        </div>

                        <!-- Bağlantını Yoxla Düyməsi -->
                        <div class="pt-4 border-t border-gray-100 mt-2">
                            <button type="button"
                                    @click="checkConnection()"
                                    :disabled="testing"
                                    class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">

                                <span x-show="!testing"><i class="fa-solid fa-plug-circle-bolt mr-2 text-green-600"></i> Bağlantını Yoxla</span>
                                <span x-show="testing"><i class="fa-solid fa-circle-notch fa-spin mr-2 text-blue-500"></i> Yoxlanılır...</span>
                            </button>

                            <!-- Test Nəticəsi -->
                            <div x-show="testResult" class="mt-3 p-3 rounded-md text-sm break-words whitespace-pre-wrap transition-all"
                                 :class="testResult === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'">
                                <div class="flex items-start">
                                    <i class="fa-solid mt-0.5 mr-2" :class="testResult === 'success' ? 'fa-check-circle' : 'fa-circle-xmark'"></i>
                                    <span x-text="testMessage"></span>
                                </div>
                            </div>

                            <p class="text-xs text-gray-400 mt-2 text-center">
                                Qeyd: Dəyişiklikləri yoxlamaq üçün əvvəlcə <b>"Yadda Saxla"</b> düyməsini sıxın.
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection
