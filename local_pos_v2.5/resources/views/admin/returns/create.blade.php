@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Qaytarma Təsdiqi</h1>
            <p class="text-sm text-gray-500 mt-1">Çek: <span class="font-mono font-bold text-blue-600">#{{ $order->receipt_code }}</span> | Tarix: {{ $order->created_at->format('d.m.Y H:i') }}</p>
        </div>
        <a href="{{ route('returns.index') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition shadow-sm">
            İmtina Et
        </a>
    </div>

    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('returns.store', $order->id) }}" method="POST">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-bold text-gray-800">Satılan Məhsullar</h2>
                <p class="text-xs text-gray-500">Qaytarmaq istədiyiniz məhsulların sayını daxil edin</p>
            </div>

            <table class="w-full text-left">
                <thead class="bg-gray-100 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-6 py-3">Məhsul</th>
                        <th class="px-6 py-3 text-center">Satılan Say</th>
                        <th class="px-6 py-3 text-center text-red-500">Qaytarılıb</th>
                        <th class="px-6 py-3 text-center text-blue-600">Qaytarılacaq Say</th>
                        <th class="px-6 py-3 text-right">Məbləğ (1 ədəd)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($order->items as $item)
                        @php
                            $maxReturnable = $item->quantity - $item->returned_quantity;
                            $unitPrice = $item->total / $item->quantity; // Endirimli faktiki satış qiyməti
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $maxReturnable == 0 ? 'opacity-50 bg-gray-50' : '' }}">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">{{ $item->product_name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $item->product_barcode }}</div>
                            </td>
                            <td class="px-6 py-4 text-center font-medium">{{ $item->quantity }}</td>
                            <td class="px-6 py-4 text-center text-red-500 font-medium">{{ $item->returned_quantity }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($maxReturnable > 0)
                                    <input type="number" name="items[{{ $item->id }}]" min="0" max="{{ $maxReturnable }}" value="0"
                                           class="w-20 text-center border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 shadow-sm font-bold text-blue-700">
                                    <div class="text-[10px] text-gray-400 mt-1">Max: {{ $maxReturnable }}</div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Tam qaytarılıb</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-gray-700">
                                {{ number_format($unitPrice, 2) }} ₼
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transform hover:-translate-y-0.5 transition duration-150 flex items-center">
                <i class="fa-solid fa-check-circle mr-2"></i>
                Qaytarmanı Təsdiqlə
            </button>
        </div>
    </form>
</div>
@endsection
