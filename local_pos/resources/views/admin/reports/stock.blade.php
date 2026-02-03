@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Stok və Anbar Dəyəri</h1>
        <p class="text-gray-500 mt-1">Anbardakı malların maliyyə analizi və partiya məlumatları</p>
    </div>

    <!-- MALİYYƏ KARTLARI -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

        <!-- 1. Ümumi Maya Dəyəri -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Anbarın Maya Dəyəri</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalCostValue, 2) }} ₼</h3>
                    <p class="text-xs text-gray-500 mt-1">Məhsulların alış qiyməti ilə</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg text-blue-600">
                    <i class="fa-solid fa-warehouse text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- 2. Ümumi Satış Dəyəri -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-indigo-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Anbarın Satış Dəyəri</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalSaleValue, 2) }} ₼</h3>
                    <p class="text-xs text-gray-500 mt-1">Cari satış qiymətləri ilə</p>
                </div>
                <div class="p-3 bg-indigo-50 rounded-lg text-indigo-600">
                    <i class="fa-solid fa-tags text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- 3. Gözlənilən Mənfəət -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-emerald-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Potensial Mənfəət</p>
                    <h3 class="text-2xl font-bold text-emerald-600 mt-1">+{{ number_format($potentialProfit, 2) }} ₼</h3>
                    <p class="text-xs text-gray-500 mt-1">Bütün mallar satılarsa</p>
                </div>
                <div class="p-3 bg-emerald-50 rounded-lg text-emerald-600">
                    <i class="fa-solid fa-sack-dollar text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- PARTİYA SİYAHISI (Detailed List) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">Partiyalar üzrə Qalıqlar</h3>

            <!-- Sadə axtarış və ya filtr yeri ola bilər -->
            <div class="text-xs text-gray-500">
                <i class="fa-solid fa-info-circle mr-1"></i> Ən son daxil olanlar yuxarıdadır
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                        <th class="p-4 border-b">Məhsul Adı</th>
                        <th class="p-4 border-b">Partiya Kodu</th>
                        <th class="p-4 border-b text-center">Qalıq Say</th>
                        <th class="p-4 border-b text-right">Alış Qiyməti</th>
                        <th class="p-4 border-b text-right">Maya Cəmi</th>
                        <th class="p-4 border-b">Tarixçə</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($batches as $batch)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 font-medium text-gray-800">
                                {{ $batch->product->name ?? 'Silinmiş Məhsul' }}
                                @if($batch->product && $batch->product->barcode)
                                    <br><span class="text-xs text-gray-400">{{ $batch->product->barcode }}</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-mono border border-gray-200">
                                    {{ $batch->batch_code }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="font-bold {{ $batch->current_quantity < 5 ? 'text-red-500' : 'text-gray-800' }}">
                                    {{ $batch->current_quantity }}
                                </span>
                            </td>
                            <td class="p-4 text-right text-gray-600">
                                {{ number_format($batch->cost_price, 2) }} ₼
                            </td>
                            <td class="p-4 text-right font-medium text-gray-800">
                                {{ number_format($batch->current_quantity * $batch->cost_price, 2) }} ₼
                            </td>
                            <td class="p-4 text-xs text-gray-500">
                                <div>Daxil olub: {{ $batch->created_at->format('d.m.Y') }}</div>
                                @if($batch->expiration_date)
                                    <div class="{{ $batch->expiration_date < now() ? 'text-red-500 font-bold' : '' }}">
                                        Bitir: {{ \Carbon\Carbon::parse($batch->expiration_date)->format('d.m.Y') }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400">
                                Anbarda aktiv partiya tapılmadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-gray-100">
            {{ $batches->links() }}
        </div>
    </div>

</div>
@endsection
