@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Hesabatlar Paneli</h1>
            <p class="text-gray-500 mt-1">Biznesinizin cari vəziyyəti və statistikalar</p>
        </div>
        <div class="text-right text-sm text-gray-500">
            <p>{{ date('d.m.Y') }}</p>
        </div>
    </div>

    <!-- 1. STATİSTİKA KARTLARI (Üst Hissə) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

        <!-- Bu günkü Satış -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-b-4 border-green-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Bu günkü Satış</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($todaySales, 2) }} ₼</h3>
                </div>
                <div class="p-3 bg-green-50 rounded-lg text-green-600">
                    <i class="fa-solid fa-cash-register text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Kritik Stok -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-b-4 border-red-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Kritik Stok</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ $criticalStockCount }} <span class="text-sm font-normal text-gray-500">məhsul</span></h3>
                </div>
                <div class="p-3 bg-red-50 rounded-lg text-red-600">
                    <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                </div>
            </div>
            @if($criticalStockCount > 0)
                <a href="{{ route('stocks.market') }}" class="text-xs text-red-500 hover:text-red-700 mt-3 inline-block font-medium">Siyahıya bax &rarr;</a>
            @endif
        </div>

        <!-- Ümumi Məhsullar -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-b-4 border-blue-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Məhsul Çeşidi</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ $totalProducts }}</h3>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg text-blue-600">
                    <i class="fa-solid fa-box text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Sistem Nüsxəsi (Backup) -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-b-4 border-purple-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Sistem Nüsxəsi</p>
                    <h3 class="text-lg font-bold text-gray-800 mt-1">{{ $backupCount }} fayl</h3>
                    <p class="text-[10px] text-gray-500 mt-1">Son: {{ $lastBackup ?? 'Yoxdur' }}</p>
                </div>
                <div class="p-3 bg-purple-50 rounded-lg text-purple-600">
                    <i class="fa-solid fa-database text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. DETALLI HESABATLAR (Keçidlər) -->
    <h2 class="text-lg font-bold text-gray-700 mb-4 border-l-4 border-blue-600 pl-3">Detallı Hesabatlar</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Mənfəət Hesabatı -->
        <a href="{{ route('reports.profit') }}" class="group bg-white rounded-xl shadow-sm hover:shadow-md transition-all p-6 border border-gray-100 flex items-center">
            <div class="h-14 w-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div class="ml-5">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-emerald-600 transition-colors">Mənfəət və Zərər</h3>
                <p class="text-sm text-gray-500 mt-1">Maya dəyəri, vergilər və xalis gəlirin analizi.</p>
            </div>
            <div class="ml-auto text-gray-300 group-hover:text-emerald-500">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </a>

        <!-- Satış Hesabatı -->
        <a href="{{ route('reports.sales') }}" class="group bg-white rounded-xl shadow-sm hover:shadow-md transition-all p-6 border border-gray-100 flex items-center">
            <div class="h-14 w-14 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="ml-5">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-600 transition-colors">Satış Hesabatı</h3>
                <p class="text-sm text-gray-500 mt-1">Satış tarixçəsi, qəbzlər və ödəniş növləri.</p>
            </div>
            <div class="ml-auto text-gray-300 group-hover:text-blue-500">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </a>

        <!-- Stok və Anbar -->
        <a href="{{ route('reports.stock') }}" class="group bg-white rounded-xl shadow-sm hover:shadow-md transition-all p-6 border border-gray-100 flex items-center">
            <div class="h-14 w-14 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div class="ml-5">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-orange-600 transition-colors">Stok və Anbar Dəyəri</h3>
                <p class="text-sm text-gray-500 mt-1">Anbardakı malların maya və satış dəyərləri.</p>
            </div>
            <div class="ml-auto text-gray-300 group-hover:text-orange-500">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </a>

        <!-- Partnyor və Promokod -->
        <a href="{{ route('reports.partners') }}" class="group bg-white rounded-xl shadow-sm hover:shadow-md transition-all p-6 border border-gray-100 flex items-center">
            <div class="h-14 w-14 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <div class="ml-5">
                <h3 class="text-lg font-bold text-gray-800 group-hover:text-purple-600 transition-colors">Partnyorlar və Promokodlar</h3>
                <p class="text-sm text-gray-500 mt-1">Partnyor satışları, komissiyalar və promokod istifadəsi.</p>
            </div>
            <div class="ml-auto text-gray-300 group-hover:text-purple-500">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </a>

    </div>
</div>
@endsection
