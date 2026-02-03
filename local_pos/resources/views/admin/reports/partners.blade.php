@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Partnyorlar və Promokodlar</h1>
        <p class="text-gray-500 mt-1">Əməkdaşlıq performansı və marketinq kodlarının analizi</p>
    </div>

    <!-- 1. PARTNYOR SİYAHISI -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Partnyor Performansı</h3>
                <p class="text-xs text-gray-500">Aktiv partnyorlar və onların nəticələri</p>
            </div>
            <div class="p-2 bg-white rounded border border-gray-200 text-sm text-gray-600">
                Cəmi: <b>{{ $partners->count() }}</b> partnyor
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white text-gray-600 text-xs uppercase font-semibold border-b">
                        <th class="p-4">Partnyor Adı</th>
                        <th class="p-4">Telefon</th>
                        <th class="p-4 text-center">Promokod Sayı</th>
                        <th class="p-4 text-right">Gətirdiyi Satış</th>
                        <th class="p-4 text-right">Qazandığı (Balans)</th>
                        <th class="p-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-sm">
                    @forelse($partners as $partner)
                        @php
                            // Bu partnyorun bütün promokodları üzrə cəmi satış
                            $totalSales = $partner->promocodes->sum(function($promo) {
                                return $promo->orders_sum_grand_total ?? 0;
                            });

                            // İstifadə sayı
                            $usageCount = $partner->promocodes->sum('orders_count');
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 font-medium text-gray-800">
                                {{ $partner->name }}
                                <div class="text-xs text-gray-400 mt-0.5">Telegram ID: {{ $partner->telegram_chat_id ?? '-' }}</div>
                            </td>
                            <td class="p-4 text-gray-600">
                                {{ $partner->phone }}
                            </td>
                            <td class="p-4 text-center">
                                <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs font-bold">
                                    {{ $partner->promocodes->count() }} kod
                                </span>
                                <div class="text-[10px] text-gray-400 mt-1">{{ $usageCount }} istifadə</div>
                            </td>
                            <td class="p-4 text-right font-medium text-gray-800">
                                {{ number_format($totalSales, 2) }} ₼
                            </td>
                            <td class="p-4 text-right">
                                <span class="font-bold text-green-600">+{{ number_format($partner->balance, 2) }} ₼</span>
                            </td>
                            <td class="p-4 text-center">
                                @if($partner->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Aktiv
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Bloklanıb
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400">
                                Hələ heç bir partnyor əlavə edilməyib.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. TOP PROMOKODLAR -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sol tərəf: Statistika -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                <i class="fa-solid fa-ranking-star text-yellow-500 mr-2"></i> Ən Çox İstifadə Olunan Kodlar
            </h3>

            <div class="space-y-4">
                @foreach($promocodes->take(5) as $promo)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded bg-white border border-gray-200 flex items-center justify-center font-mono font-bold text-purple-600 shadow-sm">
                                {{ substr($promo->code, 0, 2) }}..
                            </div>
                            <div class="ml-3">
                                <div class="font-bold text-gray-800">{{ $promo->code }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $promo->discount_type == 'percent' ? $promo->discount_value.'%' : $promo->discount_value.' ₼' }} endirim
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-gray-800">{{ $promo->orders_count }} <span class="text-xs font-normal text-gray-500">dəfə</span></div>
                            <div class="text-xs text-green-600 font-medium">Aktivdir</div>
                        </div>
                    </div>
                @endforeach

                @if($promocodes->isEmpty())
                    <div class="text-center text-gray-400 py-4">Promokod tapılmadı.</div>
                @endif
            </div>
        </div>

        <!-- Sağ tərəf: Məlumat -->
        <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-xl shadow-lg p-6 text-white">
            <h3 class="text-xl font-bold mb-2">Marketinq İpucu</h3>
            <p class="text-purple-100 text-sm mb-6">
                Partnyorlarınızın gətirdiyi müştərilər sizin biznesinizin böyüməsinə kömək edir. Aktiv partnyorları mükafatlandırmağı unutmayın.
            </p>

            <div class="bg-white/10 rounded-lg p-4 mb-4">
                <div class="text-xs text-purple-200 uppercase tracking-wider mb-1">Cəmi Promokod</div>
                <div class="text-3xl font-bold">{{ $promocodes->count() }}</div>
            </div>

            <a href="{{ route('partners.index') }}" class="block w-full bg-white text-purple-700 text-center font-bold py-2 rounded-lg hover:bg-purple-50 transition shadow-md">
                Partnyorları İdarə Et
            </a>
        </div>
    </div>

</div>
@endsection
