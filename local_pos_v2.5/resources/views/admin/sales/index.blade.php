@extends('layouts.admin')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Satış Tarixçəsi</h1>
            <p class="text-sm text-gray-500 mt-1">Sistemdə həyata keçirilən bütün əməliyyatlar</p>
        </div>
        <!-- Gələcəkdə bura tarix filteri qoyacağıq -->
        <div class="flex gap-2">
            <button class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                <i class="fa-solid fa-filter mr-2"></i> Filter
            </button>
        </div>
    </div>

    <!-- Statistik Məlumat (Sadə) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
            <p class="text-xs text-gray-500 uppercase font-bold">Toplam Satış (Bu Səhifə)</p>
            <p class="text-xl font-bold text-blue-600 mt-1">{{ number_format($orders->sum('grand_total'), 2) }} ₼</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
            <p class="text-xs text-gray-500 uppercase font-bold">Çek Sayı</p>
            <p class="text-xl font-bold text-gray-800 mt-1">{{ $orders->count() }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
            <p class="text-xs text-gray-500 uppercase font-bold">Ortalama Çek</p>
            <p class="text-xl font-bold text-green-600 mt-1">
                {{ $orders->count() > 0 ? number_format($orders->sum('grand_total') / $orders->count(), 2) : '0.00' }} ₼
            </p>
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
                                @if($order->payment_method == 'cash')
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">
                                        <i class="fa-solid fa-money-bill mr-1"></i> Nəğd
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-700">
                                        <i class="fa-regular fa-credit-card mr-1"></i> Kart
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-800">
                                {{ number_format($order->grand_total, 2) }} ₼
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{-- DÜZƏLİŞ: href="#" əvəzinə real route qoyuldu --}}
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
@endsection
