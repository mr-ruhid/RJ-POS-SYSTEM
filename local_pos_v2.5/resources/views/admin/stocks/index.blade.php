@extends('layouts.admin')

@section('content')
    <!-- Stok Naviqasiya Tabları -->
    <div class="mb-6 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <a href="{{ route('stocks.index') }}" class="inline-flex items-center p-4 text-blue-600 border-b-2 border-blue-600 rounded-t-lg active group bg-blue-50" aria-current="page">
                    <i class="fa-solid fa-chart-pie mr-2 text-lg"></i>
                    Ümumi Stok (İcmal)
                </a>
            </li>
            <li class="mr-2">
                <a href="{{ route('stocks.market') }}" class="inline-flex items-center p-4 text-gray-500 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 group transition">
                    <i class="fa-solid fa-shop mr-2 text-lg"></i>
                    Mağaza Stoku
                </a>
            </li>
            <li class="mr-2">
                <a href="{{ route('stocks.warehouse') }}" class="inline-flex items-center p-4 text-gray-500 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 group transition">
                    <i class="fa-solid fa-warehouse mr-2 text-lg"></i>
                    Anbar Stoku
                </a>
            </li>
        </ul>
    </div>

    <!-- Başlıq -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Ümumi Stok Vəziyyəti</h1>
            <p class="text-sm text-gray-500 mt-1">Məhsullar üzrə toplam qalıqlar, maya dəyərləri və statuslar</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('stocks.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-md flex items-center">
                <i class="fa-solid fa-plus mr-2"></i> Mal Qəbulu
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">Uğurlu!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <!-- Məhsullar Cədvəli -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase w-10"></th> <!-- Accordion icon -->
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Məhsul</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Kateqoriya</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center text-blue-600">Mağaza</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center text-purple-600">Anbar</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-right">Ümumi Dəyər (Maya)</th>
                    </tr>
                </thead>

                {{-- DİQQƏT: Hər məhsul üçün ayrı tbody yaradırıq ki, Alpine.js scope düzgün işləsin --}}
                @forelse($products as $product)
                    @php
                        // Hesablamalar
                        $batches = $product->batches;

                        $storeQty = $batches->filter(fn($b) => str_contains($b->batch_code, 'LOC:store'))->sum('current_quantity');
                        $warehouseQty = $batches->filter(fn($b) => str_contains($b->batch_code, 'LOC:warehouse'))->sum('current_quantity');

                        $totalQty = $storeQty + $warehouseQty;

                        $totalCostValue = 0;
                        foreach($batches as $batch) {
                            $totalCostValue += $batch->current_quantity * $batch->cost_price;
                        }

                        // Kritik Stok Yoxlanışı
                        $isCritical = $totalQty <= $product->alert_limit;
                        $isOutOfStock = $totalQty == 0;
                    @endphp

                    <tbody x-data="{ expanded: false }" class="border-b border-gray-100 last:border-b-0">
                        <!-- ƏSAS SƏTİR (Məhsul) -->
                        <tr class="hover:bg-gray-50 transition duration-150 cursor-pointer group"
                            @click="expanded = !expanded">

                            <td class="px-6 py-4 text-center">
                                <i class="fa-solid fa-chevron-right text-gray-400 text-xs transition-transform duration-200"
                                   :class="expanded ? 'rotate-90 text-blue-500' : ''"></i>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 mr-3 border border-gray-200 relative overflow-hidden">
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" class="h-full w-full object-cover">
                                        @else
                                            <i class="fa-solid fa-box text-lg"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition">{{ $product->name }}</div>
                                        <div class="text-xs text-gray-500 font-mono flex items-center gap-2">
                                            <span>{{ $product->barcode }}</span>
                                            @if($product->alert_limit > 0)
                                                <span class="text-xs text-gray-400" title="Kritik Limit">
                                                    <i class="fa-solid fa-bell text-[10px]"></i> {{ $product->alert_limit }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                    {{ $product->category->name ?? 'Yoxdur' }}
                                </span>
                            </td>

                            <!-- Status və Ümumi Say -->
                            <td class="px-6 py-4 text-center">
                                @if($isOutOfStock)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200">
                                        Bitib
                                    </span>
                                @elseif($isCritical)
                                    <div class="flex flex-col items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200 animate-pulse">
                                            Kritik: {{ $totalQty }}
                                        </span>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center">
                                        <span class="font-bold text-gray-800">{{ $totalQty }}</span>
                                        <span class="text-[10px] text-green-600">Normal</span>
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-center font-medium text-blue-600 bg-blue-50/30">
                                {{ $storeQty }}
                            </td>

                            <td class="px-6 py-4 text-center font-medium text-purple-600 bg-purple-50/30">
                                {{ $warehouseQty }}
                            </td>

                            <td class="px-6 py-4 text-right font-mono text-sm text-gray-800 font-bold">
                                {{ number_format($totalCostValue, 2) }} ₼
                            </td>
                        </tr>

                        <!-- DETALLI HİSSƏ (Stok Partiyaları) -->
                        <tr x-show="expanded" x-cloak x-transition class="bg-gray-100 border-t border-gray-200">
                            <td colspan="7" class="p-4 pl-16">
                                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                                    <div class="flex justify-between items-center mb-3 border-b pb-2">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                            <i class="fa-solid fa-layer-group mr-1"></i> Partiya Detalları (Stok Düzəlişi)
                                        </h4>
                                        <span class="text-xs text-gray-400">Satış qiyməti: {{ number_format($product->selling_price, 2) }} ₼</span>
                                    </div>

                                    @if($batches->count() > 0)
                                        <table class="w-full text-sm text-left">
                                            <thead class="text-xs text-gray-500 bg-gray-50 uppercase border-b border-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 font-medium">Qəbul Tarixi</th>
                                                    <th class="px-3 py-2 font-medium">Vergi / Qeyd</th>
                                                    <th class="px-3 py-2 font-medium">Lokasiya</th>
                                                    <th class="px-3 py-2 font-medium text-right">Gəliş Qiyməti (Maya)</th>
                                                    <th class="px-3 py-2 font-medium text-center">Qalıq Say</th>
                                                    <th class="px-3 py-2 font-medium text-right">Cəmi Dəyər</th>
                                                    <th class="px-3 py-2 font-medium text-center">Əməliyyat</th> <!-- Stok Edit Burada -->
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                @foreach($batches as $batch)
                                                    @php
                                                        $isStore = str_contains($batch->batch_code, 'LOC:store');
                                                        $variantName = explode('|', $batch->batch_code)[0] ?? '-';
                                                    @endphp
                                                    <tr class="hover:bg-blue-50/50 transition">
                                                        <td class="px-3 py-2 text-gray-600">{{ $batch->created_at->format('d.m.Y H:i') }}</td>
                                                        <td class="px-3 py-2 text-gray-500 font-mono text-xs">{{ $variantName }}</td>
                                                        <td class="px-3 py-2">
                                                            @if($isStore)
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-800 border border-blue-200">Mağaza</span>
                                                            @else
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-800 border border-purple-200">Anbar</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 text-right font-medium text-gray-700">{{ number_format($batch->cost_price, 2) }} ₼</td>
                                                        <td class="px-3 py-2 text-center font-bold text-gray-800">{{ $batch->current_quantity }}</td>
                                                        <td class="px-3 py-2 text-right text-gray-600">{{ number_format($batch->current_quantity * $batch->cost_price, 2) }} ₼</td>

                                                        <!-- STOK DÜZƏLİŞ DÜYMƏSİ -->
                                                        <td class="px-3 py-2 text-center">
                                                            <a href="{{ route('stocks.edit', $batch->id) }}" class="text-blue-600 hover:text-blue-800 transition p-1" title="Stoku (Sayı/Mayanı) Düzəlt">
                                                                <i class="fa-solid fa-pen-to-square"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="flex flex-col items-center justify-center py-6 bg-yellow-50 rounded-lg border border-yellow-100 text-yellow-700">
                                            <i class="fa-solid fa-triangle-exclamation text-2xl mb-2"></i>
                                            <p class="text-sm font-medium">Bu məhsul üçün aktiv stok partiyası yoxdur.</p>
                                            <p class="text-xs mt-1">Say sıfırdır və ya hələ mal qəbulu edilməyib.</p>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-box-open text-3xl text-gray-300 mb-3"></i>
                                    <p>Sistemdə heç bir məhsul tapılmadı.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $products->links() }}
        </div>
    </div>
@endsection
