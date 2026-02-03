@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto print:hidden"> <!-- print:hidden sinfi çap zamanı bu hissəni gizlədir -->

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Barkod Çapı</h1>
            <p class="text-sm text-gray-500 mt-1">Məhsullar üçün rəf etiketi və ya yapışqan barkod hazırlayın</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-md flex items-center">
                <i class="fa-solid fa-print mr-2"></i> Çap Et
            </button>
            <a href="{{ route('products.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                Geri Qayıt
            </a>
        </div>
    </div>

    <!-- Nəzarət Paneli -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6" x-data="barcodeApp()">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Məhsul Seçimi -->
            <div class="md:col-span-1 border-r border-gray-200 pr-6">
                <h3 class="text-sm font-bold text-gray-700 mb-4 uppercase tracking-wide">Məhsul Seçimi</h3>

                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Məhsul</label>
                    <select x-model="selectedProduct" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">-- Seçin --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-barcode="{{ $product->barcode }}"
                                    data-price="{{ $product->selling_price }}">
                                {{ $product->name }} ({{ $product->barcode }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Etiket Sayı</label>
                    <input type="number" x-model="quantity" min="1" value="1" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>

                <button @click="addToQueue()" :disabled="!selectedProduct" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-plus mr-2"></i> Siyahıya Əlavə Et
                </button>
            </div>

            <!-- Çap Siyahısı -->
            <div class="md:col-span-2 pl-2">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">Çap Siyahısı (<span x-text="printQueue.length"></span>)</h3>
                    <button @click="printQueue = []" x-show="printQueue.length > 0" class="text-xs text-red-500 hover:text-red-700 underline">Siyahını Təmizlə</button>
                </div>

                <div class="bg-gray-50 rounded-lg border border-gray-200 h-64 overflow-y-auto p-2">
                    <template x-if="printQueue.length === 0">
                        <div class="flex flex-col items-center justify-center h-full text-gray-400">
                            <i class="fa-solid fa-barcode text-3xl mb-2"></i>
                            <p class="text-sm">Siyahı boşdur. Məhsul əlavə edin.</p>
                        </div>
                    </template>

                    <table class="w-full text-sm text-left" x-show="printQueue.length > 0">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-100 sticky top-0">
                            <tr>
                                <th class="px-3 py-2">Məhsul</th>
                                <th class="px-3 py-2">Barkod</th>
                                <th class="px-3 py-2 text-center">Say</th>
                                <th class="px-3 py-2 text-right">Sil</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in printQueue" :key="index">
                                <tr class="bg-white">
                                    <td class="px-3 py-2 font-medium" x-text="item.name"></td>
                                    <td class="px-3 py-2 font-mono text-xs" x-text="item.barcode"></td>
                                    <td class="px-3 py-2 text-center">
                                        <input type="number" x-model="item.qty" min="1" class="w-16 h-7 text-center border-gray-300 rounded text-xs">
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        <button @click="removeFromQueue(index)" class="text-red-500 hover:text-red-700">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Canlı Preview Məlumatı -->
        <div class="mt-6 border-t border-gray-200 pt-4" x-show="printQueue.length > 0">
            <h3 class="text-sm font-bold text-gray-700 mb-3">Çap Önizləmə (Aşağıda görünür)</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4" id="barcode-preview-area">
                <!-- Buraya JS ilə barkodlar dolacaq (Vizual olaraq görmək üçün) -->
            </div>
        </div>
    </div>
</div>

<!-- ÇAP HİSSƏSİ (Yalnız printerdə görünür) -->
<div id="printable-area" class="hidden print:block p-4">
    <div class="grid grid-cols-4 gap-4" x-data="barcodeApp()" x-init="initPrint()">
        <template x-for="item in printQueue">
            <template x-for="i in parseInt(item.qty)">
                <div class="border border-black p-2 text-center break-inside-avoid flex flex-col items-center justify-center h-40">
                    <p class="text-sm font-bold truncate w-full mb-1" x-text="item.name"></p>
                    <p class="text-lg font-extrabold mb-1"><span x-text="item.price"></span> ₼</p>

                    <!-- Barkod SVG -->
                    <svg class="barcode-svg"
                         x-bind:jsbarcode-format="'CODE128'"
                         x-bind:jsbarcode-value="item.barcode"
                         x-bind:jsbarcode-textmargin="0"
                         x-bind:jsbarcode-fontoptions="'bold'"
                         x-bind:jsbarcode-height="40"
                         x-bind:jsbarcode-width="1.5"
                         x-bind:jsbarcode-displayvalue="true">
                    </svg>
                </div>
            </template>
        </template>
    </div>
</div>

<!-- JsBarcode Kitabxanası -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('barcodeApp', () => ({
            selectedProduct: '',
            quantity: 1,
            // Səhifə yenilənəndə itməməsi üçün LocalStorage istifadə edə bilərik
            printQueue: JSON.parse(localStorage.getItem('printQueue')) || [],

            init() {
                this.$watch('printQueue', (val) => {
                    localStorage.setItem('printQueue', JSON.stringify(val));
                    // Hər dəyişiklikdə barkodları yenilə (Preview üçün)
                    this.$nextTick(() => {
                        this.renderBarcodes();
                    });
                });
                this.renderBarcodes();
            },

            addToQueue() {
                const select = document.querySelector('select');
                const option = select.options[select.selectedIndex];

                if(!option.value) return;

                const name = option.getAttribute('data-name');
                const barcode = option.getAttribute('data-barcode');
                const price = option.getAttribute('data-price');

                // Əgər siyahıda varsa, sayını artır
                const existing = this.printQueue.find(item => item.id === option.value);
                if(existing) {
                    existing.qty = parseInt(existing.qty) + parseInt(this.quantity);
                } else {
                    this.printQueue.push({
                        id: option.value,
                        name: name,
                        barcode: barcode,
                        price: price,
                        qty: this.quantity
                    });
                }

                // Reset inputs
                this.quantity = 1;
                this.selectedProduct = '';
            },

            removeFromQueue(index) {
                this.printQueue.splice(index, 1);
            },

            renderBarcodes() {
                // JsBarcode avtomatik olaraq 'jsbarcode-value' atributu olan SVG-ləri tapıb render edir
                // Ancaq Alpine ilə dinamik yarandığı üçün manual çağırmaq lazımdır
                JsBarcode(".barcode-svg").init();

                // Preview hissəsini doldurmaq (Opsional - sadəlik üçün yuxarıdakı print bölməsi kifayətdir)
            }
        }))
    });
</script>

<style>
    @media print {
        @page { margin: 0.5cm; }
        body { background: white; }
        /* Bütün layout elementlərini gizlət */
        nav, aside, header, footer, .print\:hidden { display: none !important; }
        /* Çap sahəsini göstər */
        #printable-area { display: block !important; width: 100%; position: absolute; top: 0; left: 0; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
    }
</style>
@endsection
