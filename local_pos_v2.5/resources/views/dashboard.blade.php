@extends('layouts.admin')

@section('content')

    {{-- --- RJ UPDATER BİLDİRİŞLƏRİ (YENİ) --- --}}
    @if(!empty($updateInfo))

        {{-- 1. YENİ VERSİYA BİLDİRİŞİ --}}
        @if(isset($updateInfo['update_available']) && $updateInfo['update_available'] == true)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-download text-yellow-600"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-yellow-800">Yeni Sistem Yeniləməsi!</h3>
                        <div class="mt-1 text-sm text-yellow-700">
                            <p>
                                Hörmətli istifadəçi, sistemin yeni versiyası (<b>{{ $updateInfo['new_version'] ?? 'N/A' }}</b>) mövcuddur.
                                <br>
                                Zəhmət olmasa yeniləmək üçün texniki dəstək ilə əlaqə saxlayın.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- 2. ADMİN BİLDİRİŞİ (Notification) --}}
        @if(!empty($updateInfo['notification']))
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-info text-blue-600"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-blue-800">Bildiriş</h3>
                        <div class="mt-1 text-sm text-blue-700">
                            <p>{{ $updateInfo['notification']['message'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    @endif
    {{-- --------------------------------------- --}}

    <!-- Başlıq və Sync Düyməsi -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">İdarəetmə Paneli</h1>
            <div class="flex items-center mt-1">
                @if($systemMode == 'server')
                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-0.5 rounded border border-blue-200">
                        <i class="fa-solid fa-server mr-1"></i> SERVER REJİMİ
                    </span>
                @elseif($systemMode == 'client')
                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-0.5 rounded border border-green-200">
                        <i class="fa-solid fa-store mr-1"></i> MAĞAZA REJİMİ
                    </span>
                @else
                    <span class="bg-gray-100 text-gray-800 text-xs font-bold px-2.5 py-0.5 rounded border border-gray-200">
                        LOKAL REJİM
                    </span>
                @endif
                <span class="text-gray-500 text-sm ml-2">{{ date('d F Y') }}</span>
            </div>
        </div>

        <div class="flex space-x-2">
            @if($systemMode == 'client')
                <form action="{{ route('dashboard.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center">
                        <i class="fa-solid fa-rotate mr-2"></i> Sinxronizasiya Et
                    </button>
                </form>
            @endif

            <a href="{{ route('pos.index') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center">
                <i class="fa-solid fa-cash-register mr-2"></i> Kassaya Keç
            </a>
        </div>
    </div>

    <!-- Bildirişlər -->
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm whitespace-pre-line">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Statistik Kartlar -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">

        <!-- Kart 1: Günlük Satış -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bugünkü Satış</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalSalesToday, 2) }} ₼</h3>
                </div>
                <div class="p-2 bg-blue-50 rounded-lg">
                    <i class="fa-solid fa-sack-dollar text-blue-600 text-xl"></i>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-4">{{ $totalOrdersToday }} çek</p>
        </div>

        <!-- Kart 2: Təxmini Mənfəət -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bugünkü Mənfəət</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalProfitToday, 2) }} ₼</h3>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <i class="fa-solid fa-chart-line text-green-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-4">Satış - (Maya + Vergi + Komissiya)</p>
        </div>

        <!-- Kart 3: Kritik Stok -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Kritik Stok</p>
                    <h3 class="text-2xl font-bold text-red-600 mt-1">{{ $lowStockProducts->count() }}</h3>
                </div>
                <div class="p-2 bg-red-50 rounded-lg">
                    <i class="fa-solid fa-triangle-exclamation text-red-600 text-xl"></i>
                </div>
            </div>
            <p class="text-sm text-gray-500 mt-4">Məhsul bitmək üzrədir</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Son Satışlar -->
        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h2 class="font-bold text-gray-800">Son Satışlar</h2>
                <a href="{{ route('sales.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Hamsı</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($recentOrders as $order)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-3 font-medium text-gray-900">#{{ $order->receipt_code }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $order->created_at->format('H:i') }}</td>
                            <td class="px-6 py-3 font-bold text-gray-800 text-right">{{ number_format($order->grand_total, 2) }} ₼</td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('sales.show', $order->id) }}" class="text-gray-400 hover:text-blue-600"><i class="fa-solid fa-eye"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400">Bu gün satış olmayıb</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kritik Stok Cədvəli -->
        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h2 class="font-bold text-gray-800">Bitmək Üzrə Olanlar</h2>
                <a href="{{ route('stocks.market') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Stoka Bax</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($lowStockProducts as $product)
                        <tr class="hover:bg-red-50 transition">
                            <td class="px-6 py-3">
                                <p class="font-medium text-gray-900">{{ $product->name }}</p>
                                <p class="text-xs text-gray-500">{{ $product->barcode }}</p>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold">{{ $product->total_stock }} əd</span>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('stocks.create') }}" class="text-green-600 hover:underline text-xs font-bold">Artır +</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-green-600">
                                <i class="fa-solid fa-check-circle text-2xl mb-2"></i>
                                <p>Stok vəziyyəti əladır!</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
