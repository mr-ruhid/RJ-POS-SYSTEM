@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">API İnteqrasiyaları</h1>
            <p class="text-sm text-gray-500 mt-1">Xarici sistemlər və Botlar üçün tənzimləmələr</p>
        </div>
        <button type="submit" form="apiForm" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-bold shadow-md transition flex items-center">
            <i class="fa-solid fa-floppy-disk mr-2"></i> Yadda Saxla
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">Uğurlu!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <form id="apiForm" action="{{ route('settings.api.update') }}" method="POST">
        @csrf

        <!-- Telegram Bölməsi -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fa-brands fa-telegram text-2xl text-blue-500 mr-3"></i>
                    <h3 class="font-bold text-gray-800">Telegram Bot</h3>
                </div>
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded border border-blue-200">Partnyor Bildirişləri</span>
            </div>

            <div class="p-6 space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Bot Token -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bot Token (API Key)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fa-solid fa-key text-gray-400"></i>
                            </span>
                            <input type="text" name="telegram_bot_token" value="{{ $settings['telegram_bot_token'] ?? '' }}"
                                   class="w-full pl-10 pr-4 py-2 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm font-mono text-sm"
                                   placeholder="Məs: 123456789:AAH-xX... (BotFather-dan alınan kod)">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Telegram-da <b>@BotFather</b> vasitəsilə yeni bot yaradın və verilən Tokeni bura yapışdırın.
                        </p>
                    </div>

                    <!-- Admin ID (Test üçün) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Admin Chat ID (Test üçün)</label>
                        <input type="text" name="telegram_admin_id" value="{{ $settings['telegram_admin_id'] ?? '' }}"
                               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                               placeholder="Məs: 987654321">
                        <p class="text-xs text-gray-500 mt-1">Öz ID-nizi öyrənmək üçün <b>@userinfobot</b> istifadə edə bilərsiniz.</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Gələcək API-lər (Placeholder) -->
        <div class="bg-gray-50 rounded-xl border border-gray-200 border-dashed p-6 text-center opacity-75">
            <i class="fa-brands fa-whatsapp text-3xl text-green-500 mb-2"></i>
            <h3 class="font-bold text-gray-600">WhatsApp API</h3>
            <p class="text-sm text-gray-400 mt-1">RJ POS v3 versiyasında aktiv olacaq.</p>
        </div>

    </form>
</div>
@endsection
