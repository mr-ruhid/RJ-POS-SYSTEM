@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto"
     x-data="{
        isOpen: false,
        modalData: {
            id: null,
            name: '',
            price: 0,
            cost: 0
        },
        discountType: 'fixed',
        discountValue: 0,

        // Modal açan funksiya
        openModal(el) {
            this.modalData = {
                id: el.dataset.id,
                name: el.dataset.name,
                price: Number(el.dataset.price),
                cost: Number(el.dataset.cost)
            };

            this.discountType = 'fixed';
            this.discountValue = 0;
            this.isOpen = true;
        },

        get calculatedDiscount() {
            let discount = 0;
            if(this.discountType === 'fixed') {
                discount = Number(this.discountValue);
            } else {
                discount = (Number(this.modalData.price) * Number(this.discountValue) / 100);
            }
            return isNaN(discount) ? '0.00' : discount.toFixed(2);
        },

        get priceAfterDiscount() {
            let price = Number(this.modalData.price) - Number(this.calculatedDiscount);
            return isNaN(price) ? '0.00' : price.toFixed(2);
        },

        get calculatedProfit() {
            let profit = Number(this.modalData.price) - Number(this.modalData.cost) - Number(this.calculatedDiscount);
            return isNaN(profit) ? '0.00' : profit.toFixed(2);
        }
     }">

    <!-- Başlıq -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Mağaza Endirimləri</h1>
            <p class="text-sm text-gray-500 mt-1">Məhsullara fərdi endirimlərin tətbiqi</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">Uğurlu!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <!-- Cədvəl -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Məhsul</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Maya Dəyəri</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">İlkin Qiymət</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Endirim</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Yekun Qiymət</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-right">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        @php
                            // DÜZƏLİŞ: activeDiscount əvəzinə birbaşa son aktiv endirimi çağırırıq
                            // Bu, zaman fərqi problemini həll edəcək (Gələcək tarixləri də göstərəcək)
                            $discount = $product->discounts()->where('is_active', true)->latest()->first();
                            $hasDiscount = $discount ? true : false;

                            $oldPrice = $product->selling_price;
                            $finalPrice = $oldPrice;

                            // Endirim aktivdirsə qiyməti hesabla
                            if($hasDiscount) {
                                if($discount->type == 'fixed') {
                                    $finalPrice -= $discount->value;
                                } else {
                                    $finalPrice -= ($oldPrice * $discount->value / 100);
                                }
                            }

                            // Maya dəyəri
                            $cost = $product->batches->where('current_quantity', '>', 0)->avg('cost_price') ?? 0;
                        @endphp

                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-9 w-9 rounded bg-orange-100 flex items-center justify-center text-orange-500 mr-3">
                                        <i class="fa-solid fa-tag"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">{{ $product->name }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ $product->barcode }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Maya Dəyəri -->
                            <td class="px-6 py-4 text-center text-gray-400 text-sm">
                                {{ number_format($cost, 2) }} ₼
                            </td>

                            <!-- İlkin Qiymət -->
                            <td class="px-6 py-4 text-center text-gray-600 font-medium">
                                <span class="{{ $hasDiscount ? 'line-through text-red-400' : '' }}">
                                    {{ number_format($oldPrice, 2) }} ₼
                                </span>
                            </td>

                            <!-- Endirim -->
                            <td class="px-6 py-4 text-center">
                                @if($hasDiscount)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        @if($discount->type == 'percent')
                                            -{{ floatval($discount->value) }}%
                                        @else
                                            -{{ number_format($discount->value, 2) }} ₼
                                        @endif
                                    </span>

                                    {{-- Endirimin statusunu göstəririk (Başlayıb yoxsa gözlənilir) --}}
                                    @if($discount->start_date > now())
                                        <div class="text-[10px] text-orange-500 mt-1 font-bold">
                                            <i class="fa-regular fa-clock"></i> Başlayacaq: {{ $discount->start_date->format('d.m H:i') }}
                                        </div>
                                    @else
                                        <div class="text-[10px] text-gray-400 mt-1">
                                            Bitir: {{ $discount->end_date->format('d.m.Y H:i') }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>

                            <!-- Yekun Qiymət -->
                            <td class="px-6 py-4 text-center font-bold {{ $hasDiscount ? 'text-green-600 text-lg' : 'text-gray-800' }}">
                                {{ number_format($finalPrice, 2) }} ₼
                            </td>

                            <td class="px-6 py-4 text-right">
                                @if($hasDiscount)
                                    <form action="{{ route('discounts.stop', $discount->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Endirimi dayandırmaq istədiyinizə əminsiniz?');">
                                        @csrf
                                        <button type="submit" class="text-red-500 hover:text-red-700 p-2 border border-red-200 rounded-lg hover:bg-red-50 transition" title="Vaxtından tez bitir">
                                            <i class="fa-solid fa-stop"></i>
                                        </button>
                                    </form>
                                @else
                                    <button
                                        type="button"
                                        @click="openModal($el)"
                                        data-id="{{ $product->id }}"
                                        data-name="{{ $product->name }}"
                                        data-price="{{ $product->selling_price }}"
                                        data-cost="{{ $cost }}"
                                        class="text-blue-600 hover:text-blue-800 p-2 border border-blue-200 rounded-lg hover:bg-blue-50 transition"
                                        title="Endirim Təyin Et">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                Məhsul tapılmadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $products->links() }}
        </div>
    </div>

    <!-- POPUP MODAL -->
    <div x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Arxa fon -->
            <div x-show="isOpen" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <!-- Modal -->
            <div x-show="isOpen" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">

                <form action="{{ route('discounts.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" x-model="modalData.id">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">
                                    Endirim Təyin Et: <span x-text="modalData.name" class="text-blue-600"></span>
                                </h3>

                                <div class="mt-4 space-y-4">

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Növ</label>
                                            <select name="type" x-model="discountType" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                                <option value="fixed">Məbləğ (AZN)</option>
                                                <option value="percent">Faiz (%)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Dəyər</label>
                                            <input type="number" name="value" x-model="discountValue" step="0.01" min="0" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        </div>
                                    </div>

                                    <!-- Canlı Hesablama Paneli -->
                                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                        <div class="flex justify-between text-sm mb-2">
                                            <span class="text-gray-600">İlkin Qiymət:</span>
                                            <span class="font-bold text-gray-800"><span x-text="Number(modalData.price).toFixed(2)"></span> ₼</span>
                                        </div>

                                        <div class="flex justify-between text-sm mb-2 text-red-600 border-b border-blue-200 pb-2">
                                            <span>Endirim:</span>
                                            <span class="font-bold">-<span x-text="calculatedDiscount"></span> ₼</span>
                                        </div>

                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-blue-800">YENİ Qiymət:</span>
                                            <span class="font-extrabold text-xl text-blue-700"><span x-text="priceAfterDiscount"></span> ₼</span>
                                        </div>
                                    </div>

                                    <!-- Mənfəət Xəbərdarlığı -->
                                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 text-sm">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-gray-500">Maya Dəyəri:</span>
                                            <span class="font-medium"><span x-text="Number(modalData.cost).toFixed(2)"></span> ₼</span>
                                        </div>
                                        <div class="flex justify-between font-bold" :class="calculatedProfit < 0 ? 'text-red-600' : 'text-green-600'">
                                            <span>Qalan Mənfəət:</span>
                                            <span><span x-text="calculatedProfit"></span> ₼</span>
                                        </div>
                                        <div x-show="calculatedProfit < 0" class="text-xs text-red-500 mt-2 font-bold flex items-center">
                                            <i class="fa-solid fa-triangle-exclamation mr-1"></i> Diqqət! Zərər edirsiniz.
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Başlama</label>
                                            <input type="datetime-local" name="start_date" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Bitmə</label>
                                            <input type="datetime-local" name="end_date" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Təsdiqlə
                        </button>
                        <button type="button" @click="isOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Ləğv et
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
