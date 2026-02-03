@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto">

    <!-- Başlıq -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Stok Qeydiyyatı (Mal Qəbulu)</h1>
            <p class="text-sm text-gray-500 mt-1">Məhsulun fərqli partiyalarını (Vergili/Vergisiz) daxil edin</p>
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
                    <h3 class="text-sm font-medium text-red-800">Zəhmət olmasa məlumatları yoxlayın:</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('stocks.store') }}" method="POST"
          x-data="{
              selectedProductId: '',
              sellingPrice: '',
              batches: [
                  { variant: '', cost: '', qty: '', location: 'warehouse' }
              ],

              updateProductPrice() {
                  const select = this.$refs.productSelect;
                  const option = select.options[select.selectedIndex];
                  if(option && option.value) {
                      this.sellingPrice = option.getAttribute('data-price');
                  } else {
                      this.sellingPrice = '';
                  }
              },

              addBatch() {
                  this.batches.push({ variant: '', cost: '', qty: '', location: 'warehouse' });
              },

              removeBatch(index) {
                  if(this.batches.length > 1) {
                      this.batches.splice(index, 1);
                  } else {
                      this.batches[0].variant = '';
                      this.batches[0].cost = '';
                      this.batches[0].qty = '';
                  }
              },

              totalCost() {
                  return this.batches.reduce((sum, batch) => sum + ((Number(batch.cost) || 0) * (Number(batch.qty) || 0)), 0).toFixed(2);
              },

              totalQty() {
                  return this.batches.reduce((sum, batch) => sum + (Number(batch.qty) || 0), 0);
              }
          }">
        @csrf

        <!-- HİSSƏ 1: Məhsul Seçimi -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">1. Məhsul Məlumatları</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Məhsul Seçimi -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Məhsul Seçin <span class="text-red-500">*</span></label>
                    <select name="product_id"
                            x-ref="productSelect"
                            x-model="selectedProductId"
                            @change="updateProductPrice()"
                            required
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm py-2.5">
                        <option value="">-- Siyahıdan seçin --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" data-price="{{ $product->selling_price }}">
                                {{ $product->name }} (Barkod: {{ $product->barcode }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Avtomatik Satış Qiyməti -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Satış Qiyməti (Sabit)</label>
                    <div class="relative rounded-md shadow-sm bg-gray-50">
                        <input type="text" x-model="sellingPrice" readonly
                               class="w-full rounded-lg border-gray-300 bg-gray-100 text-gray-500 focus:ring-0 cursor-not-allowed pl-3 pr-12 font-bold"
                               placeholder="Məhsul seçilməyib">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">AZN</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Bu qiymət məhsul kartından gəlir.</p>
                </div>
            </div>
        </div>

        <!-- HİSSƏ 2: Partiyalar və Vergi Statusu -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 overflow-hidden">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">2. Partiyalar (FIFO)</h2>
                    <p class="text-xs text-gray-500">Məhsulun maya dəyərini və vergi statusunu burada qeyd edin</p>
                </div>
                <button type="button" @click="addBatch()" class="text-sm bg-blue-50 text-blue-600 hover:bg-blue-100 px-4 py-2 rounded-lg transition font-medium border border-blue-200 shadow-sm flex items-center">
                    <i class="fa-solid fa-plus mr-2"></i> Partiya Artır
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3 min-w-[200px]">Vergi Dərəcəsi</th>
                            <th class="px-4 py-3 w-40">Maya Dəyəri (AZN)</th>
                            <th class="px-4 py-3 w-32">Say</th>
                            <th class="px-4 py-3 w-48">Lokasiya</th>
                            <th class="px-4 py-3 w-10 text-center">Sil</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(batch, index) in batches" :key="index">
                            <tr class="hover:bg-gray-50 transition">

                                <!-- Vergi Seçimi -->
                                <td class="px-4 py-2">
                                    <select :name="'batches['+index+'][variant]'" x-model="batch.variant"
                                            class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Vergi Seçin</option>
                                        @foreach($taxes as $tax)
                                            <option value="{{ $tax->name }} ({{ $tax->rate }}%)">
                                                {{ $tax->name }} ({{ $tax->rate }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <!-- Maya Dəyəri -->
                                <td class="px-4 py-2">
                                    <div class="relative">
                                        <input type="number" :name="'batches['+index+'][cost_price]'" x-model="batch.cost" step="0.01" min="0" required
                                               class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-medium pl-2 pr-8"
                                               placeholder="0.00">
                                        <span class="absolute right-2 top-2 text-xs text-gray-400">₼</span>
                                    </div>
                                </td>

                                <!-- Say -->
                                <td class="px-4 py-2">
                                    <input type="number" :name="'batches['+index+'][quantity]'" x-model="batch.qty" min="1" required
                                           class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 text-center"
                                           placeholder="0">
                                </td>

                                <!-- Lokasiya Seçimi -->
                                <td class="px-4 py-2">
                                    <select :name="'batches['+index+'][location]'" x-model="batch.location" required
                                            class="w-full rounded border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="warehouse">Anbar (Warehouse)</option>
                                        <option value="store">Mağaza (Store)</option>
                                    </select>
                                </td>

                                <!-- Sil -->
                                <td class="px-4 py-2 text-center">
                                    <button type="button" @click="removeBatch(index)" class="text-red-400 hover:text-red-600 transition p-2 bg-white rounded border border-gray-200 shadow-sm hover:bg-red-50" title="Sil">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <!-- Cəm -->
                    <tfoot class="bg-gray-50 font-bold text-gray-700 text-sm">
                        <tr>
                            <td class="px-4 py-3 text-right">Yekun:</td>
                            <td class="px-4 py-3 text-blue-700" x-text="totalCost() + ' ₼'"></td>
                            <td class="px-4 py-3 text-center text-blue-700" x-text="totalQty() + ' ədəd'"></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Təsdiq Düyməsi -->
        <div class="mt-6 flex justify-end">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transform hover:-translate-y-0.5 transition duration-150 flex items-center">
                <i class="fa-solid fa-check-circle mr-2"></i>
                Təsdiq Et və Qəbul Et
            </button>
        </div>

    </form>
</div>
@endsection
