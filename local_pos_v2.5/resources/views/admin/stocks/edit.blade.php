@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto">

    <!-- Başlıq -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Partiya Düzəlişi</h1>
            <p class="text-sm text-gray-500 mt-1">
                <span class="font-bold text-blue-600">{{ $batch->product->name }}</span> məhsulunun stok məlumatlarını yeniləyin
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

    <form action="{{ route('stocks.update', $batch->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Partiya Məlumatları</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Vergi Seçimi (Parse Edilir) -->
                <div>
                    @php
                        // Batch kodundan variant adını (Vergini) ayırırıq
                        // Format: "Vergi Adı | LOC:warehouse"
                        $currentVariant = explode(' | ', $batch->batch_code)[0] ?? '';
                    @endphp

                    <label class="block text-sm font-medium text-gray-700 mb-1">Vergi Dərəcəsi</label>
                    <select name="variant" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition">
                        <option value="">Vergi Seçin</option>
                        @foreach($taxes as $tax)
                            <option value="{{ $tax->name }} ({{ $tax->rate }}%)"
                                {{ (old('variant', $currentVariant) == $tax->name . ' (' . $tax->rate . '%)') ? 'selected' : '' }}>
                                {{ $tax->name }} ({{ $tax->rate }}%)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Lokasiya Seçimi (Parse Edilir) -->
                <div>
                    @php
                        $currentLocationStr = explode(' | ', $batch->batch_code)[1] ?? '';
                        $currentLocation = str_replace('LOC:', '', $currentLocationStr);
                    @endphp

                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasiya</label>
                    <select name="location" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition">
                        <option value="warehouse" {{ old('location', $currentLocation) == 'warehouse' ? 'selected' : '' }}>Anbar (Warehouse)</option>
                        <option value="store" {{ old('location', $currentLocation) == 'store' ? 'selected' : '' }}>Mağaza (Store)</option>
                    </select>
                </div>

                <!-- Maya Dəyəri -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maya Dəyəri (AZN)</label>
                    <div class="relative rounded-md shadow-sm">
                        <input type="number" name="cost_price" value="{{ old('cost_price', $batch->cost_price) }}" step="0.01" min="0" required
                               class="w-full rounded-lg border-gray-300 pl-3 pr-12 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition" placeholder="0.00">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">AZN</span>
                        </div>
                    </div>
                </div>

                <!-- Hazırki Say -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hazırki Say (Qalıq)</label>
                    <input type="number" name="quantity" value="{{ old('quantity', $batch->current_quantity) }}" min="0" required
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition text-center font-bold text-gray-800">
                    <p class="text-xs text-gray-500 mt-1">İlkin say: {{ $batch->initial_quantity }}</p>
                </div>

                <!-- Son İstifadə Tarixi -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Son İstifadə Tarixi</label>
                    <input type="date" name="expiration_date" value="{{ old('expiration_date', $batch->expiration_date) }}"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm text-gray-600">
                </div>

            </div>

            <div class="mt-6 flex justify-end space-x-3">

                {{-- Silmə Düyməsi (Opsional) --}}
                <button type="button" onclick="if(confirm('Bu partiyanı tamamilə silmək istədiyinizə əminsiniz?')) document.getElementById('delete-form').submit();" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg font-medium transition border border-red-200">
                    <i class="fa-solid fa-trash-can mr-2"></i> Sil
                </button>

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-floppy-disk mr-2"></i>
                    Yadda Saxla
                </button>
            </div>
        </div>
    </form>

    {{-- Silmə Formu (Gizli) --}}
    <form id="delete-form" action="{{ route('stocks.destroy', $batch->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

</div>
@endsection
