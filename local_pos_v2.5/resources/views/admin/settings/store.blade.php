@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Mağaza Məlumatları</h1>
            <p class="text-sm text-gray-500 mt-1">Çeklərdə və sənədlərdə görünəcək rəsmi məlumatlar</p>
        </div>
        <button type="submit" form="settingsForm" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-bold shadow-md transition flex items-center">
            <i class="fa-solid fa-floppy-disk mr-2"></i> Yadda Saxla
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">Uğurlu!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <form id="settingsForm" action="{{ route('settings.store.update') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Əsas Məlumatlar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-full">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center">
                    <i class="fa-solid fa-shop mr-2 text-blue-500"></i> Əsas Məlumatlar
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mağaza Adı (Brend)</label>
                        <input type="text" name="store_name" value="{{ $settings['store_name'] ?? '' }}" required
                               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                               placeholder="Məs: RJ POS Market">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ünvan</label>
                        <textarea name="store_address" rows="2"
                                  class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                  placeholder="Məs: Bakı şəhəri, Nizami küç. 123">{{ $settings['store_address'] ?? '' }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Əlaqə Telefonu</label>
                        <input type="text" name="store_phone" value="{{ $settings['store_phone'] ?? '' }}"
                               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                               placeholder="+994 50 000 00 00">
                    </div>
                </div>
            </div>

            <!-- Rəsmi Rekvizitlər -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-full">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center">
                    <i class="fa-solid fa-file-contract mr-2 text-purple-500"></i> Rəsmi Rekvizitlər
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">VÖEN</label>
                        <input type="text" name="store_voen" value="{{ $settings['store_voen'] ?? '' }}"
                               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm font-mono"
                               placeholder="1234567890">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Obyekt Kodu</label>
                        <input type="text" name="object_code" value="{{ $settings['object_code'] ?? '' }}"
                               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm font-mono"
                               placeholder="000000">
                    </div>
                </div>

                <div class="mt-6 p-3 bg-blue-50 rounded text-xs text-blue-700">
                    <i class="fa-solid fa-info-circle mr-1"></i> Bu məlumatlar rəsmi qəbzlərdə (Forma 10 MQ) çap olunacaq.
                </div>
            </div>

            <!-- Çek Dizaynı -->
            <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2 flex items-center">
                    <i class="fa-solid fa-receipt mr-2 text-gray-600"></i> Çek Məlumatları (Footer)
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Çek Başlığı (Sloqan)</label>
                        <input type="text" name="receipt_header" value="{{ $settings['receipt_header'] ?? '' }}"
                               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                               placeholder="Məs: Ən sərfəli qiymətlər!">
                        <p class="text-xs text-gray-400 mt-1">Çekin ən yuxarısında görünəcək.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Çek Sonu (Təşəkkür mesajı)</label>
                        <input type="text" name="receipt_footer" value="{{ $settings['receipt_footer'] ?? '' }}"
                               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                               placeholder="Məs: Bizi seçdiyiniz üçün təşəkkürlər!">
                        <p class="text-xs text-gray-400 mt-1">Çekin ən aşağısında görünəcək.</p>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection
