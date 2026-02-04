@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    <!-- Başlıq və Tarix Filteri -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Mənfəət və Zərər</h1>
            <p class="text-gray-500 mt-1">Dəqiq maliyyə analizi (Qaytarmalar və Komissiyalar daxil)</p>
        </div>

        <form action="{{ route('reports.profit') }}" method="GET" class="bg-white p-2 rounded-lg shadow-sm border border-gray-200 flex items-center space-x-2">
            <div class="relative">
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="pl-2 pr-2 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <span class="text-gray-400">-</span>
            <div class="relative">
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="pl-2 pr-2 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-1.5 rounded-md text-sm font-medium hover:bg-blue-700 transition">
                <i class="fa-solid fa-filter mr-1"></i> Hesabla
            </button>
        </form>
    </div>

    <!-- NƏTİCƏ KARTLARI -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <!-- 1. Xalis Satış (Net Revenue) -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-blue-500">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Xalis Satış</p>
            <h3 class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($netRevenue, 2) }} ₼</h3>
            <p class="text-xs text-blue-600 mt-1 flex items-center" title="Ümumi satışdan geri qaytarmalar çıxılıb">
                <i class="fa-solid fa-circle-info mr-1"></i> Brutto: {{ number_format($grossRevenue, 2) }} ₼
            </p>
        </div>

        <!-- 2. Ümumi Xərclər -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-orange-500">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ümumi Xərclər</p>
            <h3 class="text-2xl font-bold text-gray-800 mt-2">{{ number_format($netCost + $totalTax + $totalCommission, 2) }} ₼</h3>
            <div class="flex items-center text-xs text-gray-500 mt-1 gap-2">
                <span title="Xalis Maya Dəyəri">M: <b>{{ number_format($netCost, 2) }}</b></span> |
                <span title="Vergi">V: <b>{{ number_format($totalTax, 2) }}</b></span> |
                <span title="Partnyor Komissiyası">K: <b>{{ number_format($totalCommission, 2) }}</b></span>
            </div>
        </div>

        <!-- 3. Xalis Mənfəət -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 {{ $netProfit >= 0 ? 'border-green-500' : 'border-red-500' }}">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Xalis Mənfəət</p>
            <h3 class="text-3xl font-bold {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
                {{ $netProfit >= 0 ? '+' : '' }}{{ number_format($netProfit, 2) }} ₼
            </h3>
            <p class="text-xs text-gray-500 mt-1">
                {{ $netProfit >= 0 ? 'Təbriklər, bu dövr gəlirlidir.' : 'Diqqət! Bu dövr zərərlə işləyib.' }}
            </p>
        </div>
    </div>

    <!-- DETALLI HESABLAMA SXEMİ -->
    <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-700 mb-4 flex items-center">
            <i class="fa-solid fa-calculator mr-2 text-gray-400"></i> Hesablama Detalları
        </h3>

        <div class="flex flex-col space-y-3">

            <!-- 1. GƏLİR HİSSƏSİ -->
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600">Ümumi Satış (Kassa)</span>
                <span class="text-gray-800 font-medium">{{ number_format($grossRevenue, 2) }} ₼</span>
            </div>

            <div class="flex justify-between items-center text-sm text-red-500 border-b border-gray-200 pb-2">
                <span class="pl-2 border-l-2 border-red-300">(-) Geri Qaytarılanlar</span>
                <span>-{{ number_format($totalRefunds, 2) }} ₼</span>
            </div>

            <div class="flex justify-between items-center font-bold text-gray-800 pb-4">
                <span>= Xalis Satış</span>
                <span>{{ number_format($netRevenue, 2) }} ₼</span>
            </div>

            <!-- 2. XƏRC HİSSƏSİ -->
            <div class="space-y-2">
                <div class="flex justify-between items-center text-sm text-red-500">
                    <span class="pl-2 border-l-2 border-red-300" title="Satılan malın mayasından qaytarılan malın mayası çıxılıb">(-) Xalis Maya Dəyəri</span>
                    <span>-{{ number_format($netCost, 2) }} ₼</span>
                </div>

                <div class="flex justify-between items-center text-sm text-red-500">
                    <span class="pl-2 border-l-2 border-red-300">(-) Vergi Öhdəliyi</span>
                    <span>-{{ number_format($totalTax, 2) }} ₼</span>
                </div>

                <div class="flex justify-between items-center text-sm text-red-500 border-b border-gray-200 pb-2">
                    <span class="pl-2 border-l-2 border-red-300">(-) Partnyor Komissiyaları</span>
                    <span>-{{ number_format($totalCommission, 2) }} ₼</span>
                </div>
            </div>

            <!-- YEKUN -->
            <div class="flex justify-between items-center pt-2 bg-gray-200 p-3 rounded-lg mt-2">
                <span class="text-lg font-bold text-gray-800">TƏMİZ QAZANC:</span>
                <span class="text-xl font-black {{ $netProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($netProfit, 2) }} ₼
                </span>
            </div>

            <!-- Məlumat üçün Endirimlər -->
            <div class="text-xs text-gray-400 text-center mt-2">
                <i class="fa-solid fa-info-circle"></i> Bu dövrdə müştərilərə cəmi <b>{{ number_format($totalDiscount, 2) }} ₼</b> endirim edilib (Mənfəət hesabına təsir etmir).
            </div>
        </div>
    </div>

</div>
@endsection
