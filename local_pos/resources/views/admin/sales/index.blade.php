@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Satış Tarixçəsi</h1>
            <p class="text-sm text-gray-500 mt-1">Sistemdəki bütün satış və qaytarma əməliyyatları</p>
        </div>

        <!-- Filter Forması -->
        <form action="{{ route('reports.sales') }}" method="GET" class="flex gap-2">
            <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            <button type="submit" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                <i class="fa-solid fa-filter mr-2"></i> Filter
            </button>
        </form>
    </div>

    <!-- Statistik Məlumat (Ödəniş Növlərinə Görə) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        @php
            $totalNetSales = 0;
            $totalCount = 0;
        @endphp

        @foreach($paymentStats as $stat)
            @php
                $totalNetSales += $stat->total;
                $totalCount += $stat->count;
                $icon = match($stat->payment_method) {
                    'cash' => 'fa-money-bill-wave',
                    'card' => 'fa-credit-card',
                    'bonus' => 'fa-star',
                    default => 'fa-wallet'
                };
                $color = match($stat->payment_method) {
                    'cash' => 'text-green-600 border-green-500',
                    'card' => 'text-blue-600 border-blue-500',
                    'bonus' => 'text-purple-600 border-purple-500',
                    default => 'text-gray-600 border-gray-500'
                };
            @endphp
            <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 {{ $color }}">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold">{{ ucfirst($stat->payment_method) }}</p>
                        <p class="text-xl font-bold mt-1">{{ number_format($stat->total, 2) }} ₼</p>
                        <p class="text-xs text-gray-400">{{ $stat->count }} əməliyyat</p>
                    </div>
                    <i class="fa-solid {{ $icon }} text-2xl opacity-20"></i>
                </div>
            </div>
        @endforeach

        <!-- Cəmi -->
        <div class="bg-white p-4 rounded-xl shadow-sm border-l-4 border-gray-800">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs text-gray-500 uppercase font-bold">Cəmi Xalis Satış</p>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ number_format($totalNetSales, 2) }} ₼</p>
                    <p class="text-xs text-gray-400">{{ $totalCount }} çek</p>
                </div>
                <i class="fa-solid fa-calculator text-2xl opacity-20"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Çek No</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Tarix</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Kassir</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Ödəniş</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-right">Məbləğ</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-right">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4 font-mono text-sm font-medium text-blue-600">
                                #{{ $order->receipt_code }}
                                @if($order->refunded_amount > 0)
                                    <span class="ml-2 bg-red-100 text-red-600 text-[10px] px-1.5 py-0.5 rounded font-bold" title="Bu çekdə qaytarma var">
                                        <i class="fa-solid fa-rotate-left"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $order->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="flex items-center">
                                    <div class="h-6 w-6 rounded-full bg-gray-200 flex items-center justify-center text-[10px] mr-2 text-gray-600 font-bold">
                                        {{ substr($order->user->name ?? 'U', 0, 1) }}
                                    </div>
                                    {{ $order->user->name ?? 'Naməlum' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $badgeClass = match($order->payment_method) {
                                        'cash' => 'bg-green-100 text-green-700',
                                        'card' => 'bg-blue-100 text-blue-700',
                                        'bonus' => 'bg-purple-100 text-purple-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                    $icon = match($order->payment_method) {
                                        'cash' => 'fa-money-bill',
                                        'card' => 'fa-credit-card',
                                        'bonus' => 'fa-star',
                                        default => 'fa-wallet'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium {{ $badgeClass }}">
                                    <i class="fa-solid {{ $icon }} mr-1"></i> {{ ucfirst($order->payment_method) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="font-bold text-gray-800">
                                    {{ number_format($order->grand_total, 2) }} ₼
                                </div>

                                {{-- Geri Qaytarma Varsa Göstər --}}
                                @if($order->refunded_amount > 0)
                                    <div class="text-xs text-red-500 mt-1" title="Geri Qaytarılan Məbləğ">
                                        <i class="fa-solid fa-arrow-rotate-left mr-1"></i> -{{ number_format($order->refunded_amount, 2) }} ₼
                                    </div>
                                    <div class="text-xs text-green-600 font-bold border-t border-gray-100 mt-1 pt-1" title="Xalis Satış">
                                        Net: {{ number_format($order->grand_total - $order->refunded_amount, 2) }} ₼
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('sales.show', $order->id) }}" class="text-blue-600 hover:text-blue-800 transition bg-blue-50 p-2 rounded-lg" title="Çekə Bax">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-receipt text-3xl text-gray-300 mb-3"></i>
                                    <p>Hələ heç bir satış edilməyib.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
