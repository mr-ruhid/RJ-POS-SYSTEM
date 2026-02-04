@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto py-6">

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Qaytarma Əməliyyatı</h1>
        <div class="text-right">
            <p class="text-sm text-gray-500">Çek №: <span class="font-mono font-bold text-black">{{ $order->receipt_code }}</span></p>
            <p class="text-xs text-gray-400">{{ $order->created_at->format('d.m.Y H:i') }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-4 border-l-4 border-red-500">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Alpine Data: refundForm -->
    <form action="{{ route('returns.store', $order->id) }}" method="POST" x-data="refundForm()">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between">
                <span class="font-bold text-gray-700">Məhsullar</span>
                @if($order->promo_code)
                    <span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded border border-purple-200">
                        Promokod: <b>{{ $order->promo_code }}</b>
                    </span>
                @endif
            </div>

            <table class="w-full text-left">
                <thead class="bg-gray-100 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Məhsul</th>
                        <th class="px-4 py-3 text-center">Satış Qiyməti (Xalis)</th>
                        <th class="px-4 py-3 text-center">Satılıb</th>
                        <th class="px-4 py-3 text-center">Qaytarılıb</th>
                        <th class="px-4 py-3 text-center">Qaytar (Say)</th>
                        <th class="px-4 py-3 text-right">Məbləğ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($order->items as $item)
                        @php
                            $maxReturn = $item->quantity - $item->returned_quantity;

                            // Controller-də hesablanmış real qiymət (Promokod payı çıxılmış)
                            $unitPrice = $item->refundable_unit_price;

                            // JS üçün təmiz rəqəm formatı (məs: 12.50) - vergülsüz
                            $unitPriceRaw = number_format($unitPrice, 2, '.', '');
                        @endphp

                        <tr class="hover:bg-gray-50 transition" id="row-{{ $item->id }}">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $item->product_name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $item->product_barcode }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                {{ number_format($unitPrice, 2) }} ₼
                                @if(round($unitPrice, 2) < round($item->price, 2))
                                    <div class="text-[10px] text-red-400 line-through" title="Endirimsiz qiymət">{{ number_format($item->price, 2) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-bold">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-center text-red-500">{{ $item->returned_quantity }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($maxReturn > 0)
                                    <select name="items[{{ $item->id }}][quantity]"
                                            class="border border-gray-300 rounded px-2 py-1 text-sm w-20 text-center refund-select focus:ring-blue-500 focus:border-blue-500"
                                            data-price="{{ $unitPriceRaw }}"
                                            data-row="row-{{ $item->id }}"
                                            @change="recalc()">
                                        <option value="0">0</option>
                                        @for($i=1; $i<=$maxReturn; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                @else
                                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">Tamamlanıb</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-gray-800">
                                <!-- Hər sətirin cəmi burada görünəcək -->
                                <span class="row-total">0.00</span> ₼
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- YEKUN HESAB -->
        <div class="flex justify-end items-center gap-6 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <div class="text-right">
                <p class="text-gray-500 text-sm uppercase font-bold tracking-wide">Cəmi Qaytarılacaq</p>
                <h2 class="text-4xl font-black text-blue-600 mt-1" x-text="grandTotal">0.00 ₼</h2>
            </div>
            <button type="submit"
                    :disabled="grandTotal === '0.00 ₼'"
                    class="bg-red-600 hover:bg-red-700 text-white px-8 py-4 rounded-xl font-bold shadow-lg transition transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                <i class="fa-solid fa-rotate-left mr-2"></i> Təsdiqlə və Qaytar
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('refundForm', () => ({
            grandTotal: '0.00 ₼',

            recalc() {
                let total = 0;

                // Bütün select elementlərini tapırıq
                const selects = document.querySelectorAll('.refund-select');

                selects.forEach(select => {
                    const qty = parseInt(select.value) || 0;
                    const price = parseFloat(select.getAttribute('data-price')) || 0;

                    const lineTotal = qty * price;
                    total += lineTotal;

                    // Hər sətirin qarşısındakı cəmi yeniləyirik
                    const rowId = select.getAttribute('data-row');
                    const rowTotalSpan = document.querySelector(`#${rowId} .row-total`);
                    if(rowTotalSpan) {
                        rowTotalSpan.innerText = lineTotal.toFixed(2);
                    }
                });

                // Ümumi cəmi yeniləyirik
                this.grandTotal = total.toFixed(2) + ' ₼';
            }
        }));
    });
</script>
@endsection
