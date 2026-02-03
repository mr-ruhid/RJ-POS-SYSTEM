@extends('layouts.admin')

@section('content')
<div class="max-w-5xl mx-auto py-8">

    <!-- Başlıq -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-800">
                <i class="fa-solid fa-cloud-arrow-down text-blue-600 mr-2"></i> Sistem Yeniləmələri
            </h2>
            <p class="text-gray-500 mt-1">Sisteminizi güncel saxlayın.</p>
        </div>

        <!-- Yeniləməni Yoxla Düyməsi -->
        <a href="{{ route('system.updates') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg shadow-sm transition font-medium">
            <i class="fa-solid fa-rotate mr-2"></i> Yenidən Yoxla
        </a>
    </div>

    <!-- A. QLOBAL BİLDİRİŞ (Sarı Qutu) -->
    @if($globalNotification)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r shadow-sm flex items-start">
            <div class="flex-shrink-0">
                <i class="fa-solid fa-bullhorn text-yellow-500 text-xl mt-1"></i>
            </div>
            <div class="ml-3 w-full">
                <h3 class="text-lg font-bold text-yellow-800">
                    {{ $globalNotification['title'] ?? 'Bildiriş' }}
                </h3>
                <div class="mt-1 text-sm text-yellow-700">
                    {{ $globalNotification['message'] ?? '' }}
                </div>
                @if(!empty($globalNotification['url']))
                    <div class="mt-2">
                        <a href="{{ $globalNotification['url'] }}" target="_blank" class="font-bold text-yellow-800 hover:text-yellow-900 underline">
                            Ətraflı Bax <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Sol Tərəf: Cari Versiya Kartı -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-xl shadow-md p-6 text-center border-t-4 border-blue-500 relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-4 -mr-4 text-gray-100">
                    <i class="fa-solid fa-cube text-9xl"></i>
                </div>

                <h3 class="text-gray-500 font-medium text-sm uppercase tracking-wider relative z-10">Hazırkı Versiya</h3>
                <h1 class="text-5xl font-extrabold text-gray-800 my-4 relative z-10">{{ $currentVersion }}</h1>

                @if($isUpdateAvailable)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 relative z-10">
                        <span class="w-2 h-2 mr-1 bg-red-500 rounded-full animate-pulse"></span>
                        Köhnəlib
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 relative z-10">
                        <i class="fa-solid fa-check mr-1"></i> Aktiv
                    </span>
                @endif
            </div>

            <!-- Əlavə Məlumat -->
            <div class="bg-gray-50 rounded-xl shadow-sm p-5 mt-4 border border-gray-200">
                <h4 class="font-bold text-gray-700 mb-2 text-sm">Sistem Məlumatları</h4>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li class="flex justify-between">
                        <span>Lisenziya:</span>
                        <span class="font-mono font-bold text-blue-600">FREE</span>
                    </li>
                    <li class="flex justify-between">
                        <span>PHP Versiya:</span>
                        <span class="font-mono">{{ phpversion() }}</span>
                    </li>
                    <li class="flex justify-between">
                        <span>Laravel:</span>
                        <span class="font-mono">{{ app()->version() }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Sağ Tərəf: Yeniləmə Statusu -->
        <div class="md:col-span-2 space-y-6">

            @if($isUpdateAvailable)
                <!-- B. YENİ VERSİYA VAR -->
                <div class="bg-white rounded-xl shadow-lg border border-green-200 overflow-hidden">
                    <div class="bg-green-600 px-6 py-4 text-white flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="p-2 bg-white/20 rounded-lg mr-4">
                                <i class="fa-solid fa-rocket text-xl animate-bounce"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg">Yeni Versiya Mövcuddur!</h3>
                                <p class="text-green-100 text-sm">v{{ $updateData['version'] ?? '???' }}</p>
                            </div>
                        </div>
                        <span class="bg-white text-green-700 text-xs font-bold px-3 py-1 rounded-full shadow-sm">
                            Tövsiyə olunan
                        </span>
                    </div>

                    <div class="p-6">
                        <div class="prose max-w-none text-gray-600 text-sm mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <h4 class="font-bold text-gray-800 mb-2"><i class="fa-solid fa-list-check mr-1"></i> Qeydlər:</h4>
                            <!-- API-dən gələn qeydlər (notes) -->
                            <p>{!! nl2br(e($updateData['notes'] ?? 'Yeniliklər barədə məlumat yoxdur.')) !!}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 bg-green-50 p-4 rounded-lg border border-green-100">
                            <!-- Endirmə Düyməsi -->
                            @if(!empty($updateData['download_url']))
                                <a href="{{ $updateData['download_url'] }}" target="_blank" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all transform hover:scale-105">
                                    <i class="fa-solid fa-download mr-2"></i> İndi Yüklə (v{{ $updateData['version'] ?? '' }})
                                </a>
                            @endif

                            <!-- Təfərrüatlara Bax -->
                            @if(!empty($updateData['action_url']))
                                <a href="{{ $updateData['action_url'] }}" target="_blank" class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all">
                                    <i class="fa-solid fa-external-link-alt mr-2"></i> Təfərrüatlara Bax
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

            @elseif($serverMessage)
                 <!-- ⚠️ XƏTA/MƏLUMAT (Məsələn: API xətası) -->
                 <div class="bg-white rounded-xl shadow-md border-l-4 border-yellow-500 p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fa-solid fa-triangle-exclamation text-yellow-500 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Məlumat</h3>
                            <div class="mt-2 text-sm text-gray-500">
                                <p>{{ $serverMessage }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                <!-- 🟢 ƏN SON VERSİYA -->
                <div class="bg-white rounded-xl shadow-md border-l-4 border-green-500 p-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fa-solid fa-circle-check text-green-500 text-3xl"></i>
                        </div>
                        <div class="ml-4 w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900">Sistem Aktualdır</h3>
                            <div class="mt-2 text-sm text-gray-600">
                                <p>Təbriklər! Siz hazırda ən son versiyanı istifadə edirsiniz. Heç bir yeniləmə tələb olunmur.</p>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-100 flex justify-between items-center">
                                <span class="text-xs text-gray-400">Son yoxlanış: {{ now()->format('d.m.Y H:i') }}</span>
                                <i class="fa-solid fa-shield-halved text-green-200 text-4xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
