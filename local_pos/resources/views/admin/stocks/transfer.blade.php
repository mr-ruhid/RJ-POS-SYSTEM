@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto">

    <!-- Başlıq -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Stok Transferi</h1>
            <p class="text-sm text-gray-500 mt-1">
                <span class="text-purple-600 font-bold">Anbar (Warehouse)</span>
                <i class="fa-solid fa-arrow-right mx-2 text-gray-400"></i>
                <span class="text-blue-600 font-bold">Mağaza (Store)</span>
            </p>
        </div>
        <a href="{{ route('stocks.index') }}" class="text-gray-600 hover:text-gray-900 font-medium text-sm flex items-center bg-white border border-gray-300 px-4 py-2 rounded-lg shadow-sm transition">
            <i class="fa-solid fa-arrow-left mr-2"></i> Geri Qayıt
        </a>
    </div>

    <!-- Xəta Bildirişləri -->
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0"><i class="fa-solid fa-circle-exclamation text-red-400"></i></div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Xəta baş verdi:</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden"
         x-data="{
            selectedProductId: '',
            // Bütün partiyaları birbaşa bura yükləyirik
            allBatches: {{ json_encode($products->pluck('batches', 'id')) }},
            currentBatches: [],

            // Məhsul seçiləndə işə düşən funksiya
            loadBatches() {
                if(this.selectedProductId && this.allBatches[this.selectedProductId]) {
                    this.currentBatches = this.allBatches[this.selectedProductId];
                } else {
                    this.currentBatches = [];
                }
            },

            // Tarix formatı
            formatDate(dateString) {
                if(!dateString) return '';
                const date = new Date(dateString);
                return date.toLocaleDateString('az-AZ') + ' ' + date.toLocaleTimeString('az-AZ', {hour: '2-digit', minute:'2-digit'});
            },

            // Variantı ayırmaq
            getVariant(batchCode) {
                if(!batchCode) return 'Standart';
                return batchCode.split('|')[0].trim();
            }
         }">

        <div class="p-6">
            <!-- Məhsul Seçimi -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Transfer ediləcək məhsulu seçin</label>

                @if($products->count() > 0)
                    <select x-model="selectedProductId" @change="loadBatches()" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm py-2.5">
                        <option value="">-- Məhsul Seçin --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }} ({{ $product->batches->sum('current_quantity') }} ədəd anbarda)
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Yalnız anbarda stoku olan məhsullar siyahıda görünür.</p>
                @else
                    <div class="p-4 bg-yellow-50 text-yellow-700 rounded-lg text-sm border border-yellow-200 flex items-center">
                        <i class="fa-solid fa-circle-info mr-2"></i>
                        Hazırda anbarda transfer ediləcək məhsul yoxdur. Zəhmət olmasa əvvəlcə <a href="{{ route('stocks.create') }}" class="underline font-bold ml-1">Mal Qəbulu</a> edin.
                    </div>
                @endif
            </div>

            <!-- Partiya Siyahısı (Seçim ediləndə görünür) -->
            <div x-show="selectedProductId" x-transition>

                <template x-if="currentBatches.length > 0">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 mb-3 uppercase tracking-wide border-b pb-2">
                            Mövcud Anbar Partiyaları
                        </h3>
                        <div class="space-y-3">
                            <template x-for="batch in currentBatches" :key="batch.id">
                                <form action="{{ route('stocks.transfer.process') }}" method="POST" class="bg-purple-50 border border-purple-100 rounded-lg p-4 flex items-center justify-between shadow-sm">
                                    @csrf
                                    <input type="hidden" name="batch_id" :value="batch.id">

                                    <!-- Sol Tərəf: Məlumat -->
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-bold text-gray-500">QƏBUL:</span>
                                            <span class="text-sm font-medium text-gray-800" x-text="formatDate(batch.created_at)"></span>
                                            <span class="bg-white border border-gray-200 text-gray-600 text-[10px] px-2 py-0.5 rounded font-mono" x-text="getVariant(batch.batch_code)"></span>
                                        </div>
                                        <div class="text-xs text-gray-600">
                                            Maya: <span class="font-bold" x-text="batch.cost_price"></span> ₼ |
                                            Qalıq: <span class="font-bold text-purple-700 text-base" x-text="batch.current_quantity"></span> ədəd
                                        </div>
                                    </div>

                                    <!-- Sağ Tərəf: Transfer Inputu -->
                                    <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-gray-200">
                                        <div class="relative">
                                            <input type="number" name="quantity" min="1" :max="batch.current_quantity" required
                                                class="w-24 rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-center text-sm font-bold"
                                                placeholder="Say">
                                            <div class="absolute -bottom-5 left-0 w-full text-center text-[9px] text-gray-400">max: <span x-text="batch.current_quantity"></span></div>
                                        </div>
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition shadow-sm flex items-center h-full">
                                            Transfer <i class="fa-solid fa-arrow-right ml-2"></i>
                                        </button>
                                    </div>
                                </form>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="currentBatches.length === 0">
                    <div class="text-center py-4 text-gray-500 italic bg-gray-50 rounded-lg border border-gray-100">
                        Seçilən məhsul üçün anbar qalığı tapılmadı.
                    </div>
                </template>

            </div>
        </div>
    </div>
</div>
@endsection
