@extends('layouts.admin')

@section('content')
    <!-- Stok Naviqasiya -->
    <div class="mb-6 border-b border-gray-200">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <a href="{{ route('stocks.index') }}" class="inline-flex items-center p-4 text-gray-500 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 group transition">
                    <i class="fa-solid fa-chart-pie mr-2 text-lg"></i>
                    Ümumi Stok
                </a>
            </li>
            <li class="mr-2">
                <a href="{{ route('stocks.market') }}" class="inline-flex items-center p-4 text-blue-600 border-b-2 border-blue-600 rounded-t-lg active group bg-blue-50" aria-current="page">
                    <i class="fa-solid fa-shop mr-2 text-lg"></i>
                    Mağaza Stoku (Rəf)
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

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Mağaza Stoku</h1>
            <p class="text-sm text-gray-500 mt-1">Satış nöqtəsində olan mallar və kritik limitlər</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">Əla!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase w-10"></th> <!-- Accordion icon -->
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Məhsul</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Mağaza Qalığı</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase w-48">Kritik Limit Ayarı</th>
                    </tr>
                </thead>

                @forelse($products as $product)
                    @php
                        // Mağazadakı partiyaların cəmi (Controller filter edib)
                        $storeQty = $product->batches->sum('current_quantity');
                        $isCritical = $storeQty <= $product->alert_limit;
                        $isOutOfStock = $storeQty == 0;
                    @endphp

                    <tbody x-data="{ expanded: false }" class="border-b border-gray-100 last:border-b-0">
                        <!-- ƏSAS SƏTİR -->
                        <tr class="hover:bg-gray-50 transition duration-150 cursor-pointer group {{ $isCritical ? 'bg-red-50' : '' }}"
                            @click="expanded = !expanded">

                            <!-- Accordion Icon -->
                            <td class="px-6 py-4 text-center">
                                <i class="fa-solid fa-chevron-right text-gray-400 text-xs transition-transform duration-200"
                                   :class="expanded ? 'rotate-90 text-blue-500' : ''"></i>
                            </td>

                            <!-- Məhsul Adı -->
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-9 w-9 rounded bg-gray-100 flex items-center justify-center text-gray-400 mr-3 border border-gray-200 overflow-hidden relative">
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" class="h-full w-full object-cover">
                                        @else
                                            <i class="fa-solid fa-box"></i>
                                        @endif
                                    </div>
                                    <div class="flex items-center">
                                        @if($isCritical)
                                            <div class="text-red-500 mr-2" title="Kritik Stok!">
                                                <i class="fa-solid fa-triangle-exclamation animate-pulse"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-bold text-gray-900">{{ $product->name }}</div>
                                            <div class="text-xs text-gray-500 font-mono">{{ $product->barcode }}</div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Say -->
                            <td class="px-6 py-4 text-center">
                                <span class="text-lg font-bold {{ $isCritical ? 'text-red-600' : 'text-blue-600' }}">
                                    {{ $storeQty }}
                                </span>
                                <span class="text-xs text-gray-400 block">ədəd</span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                @if($isOutOfStock)
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-gray-200 text-gray-700">Bitib</span>
                                @elseif($isCritical)
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700">Azalıb</span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700">Normal</span>
                                @endif
                            </td>

                            <!-- Limit Dəyişmə Forması -->
                            <td class="px-6 py-4" @click.stop>
                                <form action="{{ route('products.update_alert', $product->id) }}" method="POST" class="flex items-center">
                                    @csrf
                                    <input type="number" name="alert_limit" value="{{ $product->alert_limit }}" min="0"
                                           class="w-20 px-2 py-1 text-sm text-center border border-gray-300 rounded-l-lg focus:ring-blue-500 focus:border-blue-500"
                                           title="Limit">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 text-sm rounded-r-lg transition">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- DETALLI HİSSƏ (Mağaza Partiyaları) -->
                        <tr x-show="expanded" x-cloak x-transition class="bg-gray-100 border-t border-gray-200">
                            <td colspan="5" class="p-4 pl-16">
                                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 border-b pb-2">
                                        <i class="fa-solid fa-layer-group mr-1"></i> Mağaza Partiyaları
                                    </h4>

                                    @if($product->batches->count() > 0)
                                        <table class="w-full text-sm text-left">
                                            <thead class="text-xs text-gray-500 bg-gray-50 uppercase border-b border-gray-100">
                                                <tr>
                                                    <th class="px-3 py-2 font-medium">Qəbul Tarixi</th>
                                                    <th class="px-3 py-2 font-medium">Vergi / Variant</th>
                                                    <th class="px-3 py-2 font-medium text-right">Maya Dəyəri</th>
                                                    <th class="px-3 py-2 font-medium text-center">Qalıq Say</th>
                                                    <th class="px-3 py-2 font-medium text-center">Əməliyyat</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                @foreach($product->batches as $batch)
                                                    @php
                                                        $variantName = explode('|', $batch->batch_code)[0] ?? '-';
                                                    @endphp
                                                    <tr class="hover:bg-blue-50/50 transition">
                                                        <td class="px-3 py-2 text-gray-600">{{ $batch->created_at->format('d.m.Y H:i') }}</td>
                                                        <td class="px-3 py-2 text-gray-500 font-mono text-xs">{{ $variantName }}</td>
                                                        <td class="px-3 py-2 text-right font-medium text-gray-700">{{ number_format($batch->cost_price, 2) }} ₼</td>
                                                        <td class="px-3 py-2 text-center font-bold text-gray-800">{{ $batch->current_quantity }}</td>
                                                        <td class="px-3 py-2 text-center">
                                                            <a href="{{ route('stocks.edit', $batch->id) }}" class="text-blue-600 hover:text-blue-800 transition p-1" title="Stoku Düzəlt">
                                                                <i class="fa-solid fa-pen-to-square"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="flex flex-col items-center justify-center py-4 text-gray-400">
                                            <i class="fa-solid fa-box-open text-2xl mb-2 opacity-50"></i>
                                            <p class="text-sm italic">Mağazada aktiv stok partiyası yoxdur.</p>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-box-open text-3xl text-gray-300 mb-3"></i>
                                    <p>Mağaza stokunda heç bir məhsul tapılmadı.</p>
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
