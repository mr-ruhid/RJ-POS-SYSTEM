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
                <a href="{{ route('stocks.market') }}" class="inline-flex items-center p-4 text-gray-500 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 group transition">
                    <i class="fa-solid fa-shop mr-2 text-lg"></i>
                    Mağaza Stoku (Rəf)
                </a>
            </li>
            <li class="mr-2">
                <a href="{{ route('stocks.warehouse') }}" class="inline-flex items-center p-4 text-blue-600 border-b-2 border-blue-600 rounded-t-lg active group bg-blue-50" aria-current="page">
                    <i class="fa-solid fa-warehouse mr-2 text-lg"></i>
                    Anbar Stoku
                </a>
            </li>
        </ul>
    </div>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Anbar Stoku (Partiyalar)</h1>
            <p class="text-sm text-gray-500 mt-1">Anbarda mövcud olan bütün partiyaların siyahısı (FIFO üzrə)</p>
        </div>
        <a href="{{ route('stocks.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-md flex items-center">
            <i class="fa-solid fa-plus mr-2"></i> Yeni Mal Qəbulu
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">Uğurlu!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Qəbul Tarixi</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Məhsul</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Batch Kodu / Variant</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-right">Maya Dəyəri</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Qalıq / İlkin</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-right">Cəmi Dəyər</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($batches as $batch)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <!-- Tarix -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $batch->created_at->format('d.m.Y H:i') }}
                            </td>

                            <!-- Məhsul Adı -->
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded bg-purple-100 flex items-center justify-center text-purple-500 mr-3 text-xs">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">{{ $batch->product->name }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ $batch->product->barcode }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Batch Kodu -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($batch->batch_code)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                        {{ Str::limit($batch->batch_code, 20) }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>

                            <!-- Maya Dəyəri -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-700">
                                {{ number_format($batch->cost_price, 2) }} ₼
                            </td>

                            <!-- Say -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold
                                    {{ $batch->current_quantity < 5 ? 'bg-red-100 text-red-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ $batch->current_quantity }} əd
                                </span>
                                <div class="text-[10px] text-gray-400 mt-1">
                                    İlkin: {{ $batch->initial_quantity }}
                                </div>
                            </td>

                            <!-- Cəmi Dəyər -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900">
                                {{ number_format($batch->current_quantity * $batch->cost_price, 2) }} ₼
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-solid fa-boxes-stacked text-3xl text-gray-300 mb-3"></i>
                                    <p>Anbar stokunda heç bir partiya tapılmadı.</p>
                                    <a href="{{ route('stocks.create') }}" class="text-blue-600 hover:underline text-sm mt-2">
                                        Yeni mal qəbul et
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $batches->links() }}
        </div>
    </div>
@endsection
