@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    <!-- Başlıq və Tarix Filteri -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Satış Hesabatı</h1>
            <p class="text-gray-500 mt-1">Dövriyyə, çek sayı və ödəniş növləri üzrə analiz</p>
        </div>

        <form action="{{ route('reports.sales') }}" method="GET" class="bg-white p-2 rounded-lg shadow-sm border border-gray-200 flex items-center space-x-2">
            <div class="relative">
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="pl-2 pr-2 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <span class="text-gray-400">-</span>
            <div class="relative">
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="pl-2 pr-2 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-1.5 rounded-md text-sm font-medium hover:bg-blue-700 transition">
                <i class="fa-solid fa-filter mr-1"></i> Göstər
            </button>
        </form>
    </div>

    @php
        // Hesablamalar (View daxilində sadə riyaziyyat)
        $totalSales = $paymentStats->sum('total');
        $totalCount = $paymentStats->sum('count');
        $averageBasket = $totalCount > 0 ? $totalSales / $totalCount : 0;
    @endphp

    <!-- 1. ƏSAS GÖSTƏRİCİLƏR (Kartlar) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <!-- Ümumi Dövriyyə -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-b-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ümumi Satış</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalSales, 2) }} ₼</h3>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg text-blue-600">
                    <i class="fa-solid fa-coins text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Çek Sayı -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-b-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Çek Sayı</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ $totalCount }}</h3>
                </div>
                <div class="p-3 bg-purple-50 rounded-lg text-purple-600">
                    <i class="fa-solid fa-receipt text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Orta Səbət -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-b-4 border-orange-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Orta Səbət</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($averageBasket, 2) }} ₼</h3>
                </div>
                <div class="p-3 bg-orange-50 rounded-lg text-orange-600">
                    <i class="fa-solid fa-basket-shopping text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. ÖDƏNİŞ NÖVLƏRİ ANALİZİ -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Qrafik/Statistika -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Ödəniş Növləri Üzrə</h3>
            <div class="space-y-4">
                @foreach($paymentStats as $stat)
                    @php
                        $percent = $totalSales > 0 ? ($stat->total / $totalSales) * 100 : 0;
                        $colorClass = match($stat->payment_method) {
                            'cash' => 'bg-green-500',
                            'card' => 'bg-blue-500',
                            'bonus' => 'bg-purple-500',
                            default => 'bg-gray-500'
                        };
                        $icon = match($stat->payment_method) {
                            'cash' => 'fa-money-bill-wave',
                            'card' => 'fa-credit-card',
                            'bonus' => 'fa-star',
                            default => 'fa-wallet'
                        };
                        $label = match($stat->payment_method) {
                            'cash' => 'Nağd',
                            'card' => 'Kart',
                            'bonus' => 'Bonus',
                            default => ucfirst($stat->payment_method)
                        };
                    @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium flex items-center text-gray-700">
                                <i class="fa-solid {{ $icon }} mr-2 text-gray-400"></i> {{ $label }}
                            </span>
                            <span class="font-bold text-gray-800">{{ number_format($stat->total, 2) }} ₼</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="{{ $colorClass }} h-2.5 rounded-full" style="width: {{ $percent }}%"></div>
                        </div>
                        <div class="text-xs text-gray-400 mt-1 text-right">{{ $stat->count }} çek ({{ round($percent, 1) }}%)</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Məlumat Qutusu -->
        <div class="bg-blue-50 rounded-xl border border-blue-100 p-6 flex flex-col justify-center">
            <div class="flex items-start mb-4">
                <i class="fa-solid fa-circle-info text-blue-500 text-xl mt-1 mr-3"></i>
                <div>
                    <h4 class="font-bold text-blue-800">Hesabat Haqqında</h4>
                    <p class="text-sm text-blue-700 mt-1">Bu hesabat seçilmiş tarix aralığındakı ({{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}) bütün tamamlanmış satışları əks etdirir.</p>
                </div>
            </div>
            <div class="flex items-start">
                <i class="fa-solid fa-lightbulb text-yellow-500 text-xl mt-1 mr-3"></i>
                <div>
                    <h4 class="font-bold text-blue-800">İpucu</h4>
                    <p class="text-sm text-blue-700 mt-1">Kartla ödənişlərin çoxluğu nağdsız dövriyyənin artdığını, bonus ödənişləri isə sadiq müştərilərin aktivliyini göstərir.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. SATIŞ TARİXÇƏSİ CƏDVƏLİ -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Satış Jurnalı</h3>
            <span class="text-xs text-gray-500">Son 15 əməliyyat</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                        <th class="p-4 border-b">Qəbz №</th>
                        <th class="p-4 border-b">Tarix</th>
                        <th class="p-4 border-b">Kassir</th>
                        <th class="p-4 border-b text-center">Ödəniş</th>
                        <th class="p-4 border-b text-right">Məbləğ</th>
                        <th class="p-4 border-b text-center">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 font-medium text-blue-600">
                                #{{ $order->receipt_code ?? $order->id }}
                            </td>
                            <td class="p-4 text-gray-600">
                                {{ $order->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="p-4 text-gray-800">
                                <div class="flex items-center">
                                    <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-xs mr-2">
                                        {{ substr($order->user->name ?? 'U', 0, 1) }}
                                    </div>
                                    {{ $order->user->name ?? 'Naməlum' }}
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                @php
                                    $badgeColor = match($order->payment_method) {
                                        'cash' => 'bg-green-100 text-green-700 border-green-200',
                                        'card' => 'bg-blue-100 text-blue-700 border-blue-200',
                                        'bonus' => 'bg-purple-100 text-purple-700 border-purple-200',
                                        default => 'bg-gray-100 text-gray-700 border-gray-200'
                                    };
                                    $methodName = match($order->payment_method) {
                                        'cash' => 'Nağd',
                                        'card' => 'Kart',
                                        'bonus' => 'Bonus',
                                        default => $order->payment_method
                                    };
                                @endphp
                                <span class="px-2 py-1 rounded text-xs font-bold border {{ $badgeColor }}">
                                    {{ $methodName }}
                                </span>
                            </td>
                            <td class="p-4 text-right font-bold text-gray-800">
                                {{ number_format($order->grand_total, 2) }} ₼
                            </td>
                            <td class="p-4 text-center">
                                <a href="{{ route('sales.show', $order->id) }}" class="text-gray-500 hover:text-blue-600 transition" title="Ətraflı Bax">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400">
                                Seçilən tarixdə satış tapılmadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-100">
            {{ $orders->appends(request()->query())->links() }}
        </div>
    </div>

</div>
@endsection
