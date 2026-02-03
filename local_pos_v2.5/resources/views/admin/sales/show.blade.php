@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto print:max-w-full">

    <!-- Başlıq (Çapda gizlənir) -->
    <div class="flex items-center justify-between mb-6 print:hidden">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Satış Detalları</h1>
            <p class="text-sm text-gray-500 mt-1">Çek Nömrəsi: <span class="font-mono font-bold text-blue-600">#{{ $order->receipt_code }}</span></p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-md flex items-center">
                <i class="fa-solid fa-print mr-2"></i> Çap Et
            </button>
            <a href="{{ route('sales.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                Geri
            </a>
        </div>
    </div>

    <!-- Çek Forması -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden print:shadow-none print:border-0">

        <!-- Çek Başlığı (DİNAMİK MƏLUMATLARLA) -->
        <div class="bg-gray-50 p-6 border-b border-gray-200 print:bg-white print:p-0 print:border-b-2 print:border-black print:mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <!-- Mağaza Adı -->
                    <h2 class="text-xl font-bold text-gray-800 uppercase tracking-wider">
                        {{ $settings['store_name'] ?? 'RJ POS Market' }}
                    </h2>

                    <!-- Mağaza Ünvanı -->
                    @if(!empty($settings['store_address']))
                        <p class="text-sm text-gray-500 mt-1">{{ $settings['store_address'] }}</p>
                    @endif

                    <!-- Mağaza Telefonu -->
                    @if(!empty($settings['store_phone']))
                        <p class="text-sm text-gray-500">Tel: {{ $settings['store_phone'] }}</p>
                    @endif
                </div>

                <div class="text-right">
                    <p class="text-sm text-gray-600">Tarix: <span class="font-bold text-gray-900">{{ $order->created_at->format('d.m.Y H:i') }}</span></p>
                    <p class="text-sm text-gray-600 mt-1">Kassir: <span class="font-bold text-gray-900">{{ $order->user->name ?? 'Naməlum' }}</span></p>
                    <p class="text-sm text-gray-600 mt-1">Çek No: <span class="font-mono font-bold">{{ $order->receipt_code }}</span></p>
                </div>
            </div>
        </div>

        <!-- Məhsullar -->
        <div class="p-6 print:p-0">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-gray-500 border-b border-gray-200 print:border-black">
                        <th class="py-3 font-semibold">Məhsul</th>
                        <th class="py-3 text-center font-semibold">Qiymət</th>
                        <th class="py-3 text-center font-semibold">Say</th>
                        <th class="py-3 text-right font-semibold">Cəm</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 print:divide-dashed print:divide-gray-400">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="py-3">
                                <p class="font-medium text-gray-900">{{ $item->product_name }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ $item->product_barcode }}</p>
                            </td>
                            <td class="py-3 text-center text-gray-600">
                                {{ number_format($item->price, 2) }}
                                @if($item->discount_amount > 0)
                                    <br><span class="text-xs text-red-500">-{{ number_format($item->discount_amount, 2) }}</span>
                                @endif
                            </td>
                            <td class="py-3 text-center font-bold text-gray-800">
                                {{ $item->quantity }}
                            </td>
                            <td class="py-3 text-right font-bold text-gray-900">
                                {{ number_format($item->total, 2) }} ₼
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Yekun Hesablamalar -->
        <div class="bg-gray-50 p-6 border-t border-gray-200 print:bg-white print:p-0 print:border-t-2 print:border-black">
            <div class="flex flex-col items-end gap-2">
                <div class="w-full md:w-1/3 flex justify-between text-sm text-gray-600">
                    <span>Ara Cəm:</span>
                    <span>{{ number_format($order->subtotal, 2) }} ₼</span>
                </div>

                @if($order->total_discount > 0)
                    <div class="w-full md:w-1/3 flex justify-between text-sm text-red-600">
                        <span>Endirim:</span>
                        <span>-{{ number_format($order->total_discount, 2) }} ₼</span>
                    </div>
                @endif

                <div class="w-full md:w-1/3 flex justify-between text-lg font-bold text-gray-900 border-t border-gray-300 pt-2 mt-1 print:border-black">
                    <span>YEKUN:</span>
                    <span>{{ number_format($order->grand_total, 2) }} ₼</span>
                </div>

                <div class="w-full md:w-1/3 flex justify-between text-sm text-gray-600 mt-2">
                    <span>Ödənilən ({{ ucfirst($order->payment_method) }}):</span>
                    <span>{{ number_format($order->paid_amount, 2) }} ₼</span>
                </div>

                @if($order->change_amount > 0)
                    <div class="w-full md:w-1/3 flex justify-between text-sm text-green-600 font-bold">
                        <span>Qalıq (Sdat):</span>
                        <span>{{ number_format($order->change_amount, 2) }} ₼</span>
                    </div>
                @endif
            </div>

            <!-- Footer (Çap üçün) - DİNAMİK -->
            <div class="text-center mt-8 pt-4 border-t border-gray-200 hidden print:block">
                <p class="text-xs font-bold text-gray-800">{{ $settings['receipt_footer'] ?? 'TƏŞƏKKÜRLƏR!' }}</p>
                <p class="text-[10px] text-gray-500 mt-1">Yenə gözləyirik</p>
                <p class="text-[10px] text-gray-400 mt-2">Software by RJ POS v2</p>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        @page { margin: 0; size: auto; }
        body { background: white; }
        nav, aside, .print\:hidden { display: none !important; }
        .shadow-lg { box-shadow: none !important; }
        .border { border: none !important; }
    }
</style>
@endsection
