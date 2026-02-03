@extends('layouts.admin')

@section('content')
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Lotoreya İştirakçıları</h1>
            <p class="text-sm text-gray-500 mt-1">Hər satışdan sonra avtomatik yaranan şans kodları</p>
        </div>

        <div class="flex gap-2">
            <!-- Gələcəkdə bura "Qalib Seç" düyməsi qoya bilərik -->
            <button class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-md transition flex items-center">
                <i class="fa-solid fa-trophy mr-2"></i> Qalib Təyin Et (Tezliklə)
            </button>
        </div>
    </div>

    <!-- Statistika -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg p-5 text-white relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-xs font-bold uppercase opacity-80">Ümumi İştirakçı</p>
                <p class="text-3xl font-extrabold mt-1">{{ $lotteries->total() }}</p>
            </div>
            <i class="fa-solid fa-ticket absolute right-4 bottom-4 text-5xl opacity-20"></i>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs font-bold text-gray-500 uppercase">Bugünkü Kodlar</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">
                {{ \App\Models\Order::whereNotNull('lottery_code')->whereDate('created_at', today())->count() }}
            </p>
        </div>
    </div>

    <!-- Cədvəl -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase w-56">Lotoreya Kodu</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Çek No</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Müştəri / Tarix</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-right">Alış Məbləği</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($lotteries as $lottery)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <!-- Lotoreya Kodu (Bilet Dizaynı - Yenilənmiş) -->
                            <td class="px-6 py-4">
                                <div class="group relative inline-flex items-center justify-center bg-purple-50 border-2 border-purple-200 text-purple-900 px-4 py-2 rounded-lg font-mono text-lg font-bold tracking-widest shadow-sm border-dashed cursor-pointer hover:bg-purple-100 transition" title="Kodu Kopyala" onclick="navigator.clipboard.writeText('{{ $lottery->lottery_code }}');">
                                    {{ $lottery->lottery_code }}
                                    <!-- Dekorativ kəsiklər -->
                                    <div class="absolute -left-1.5 top-1/2 -mt-1.5 w-3 h-3 bg-white rounded-full border-r border-purple-200"></div>
                                    <div class="absolute -right-1.5 top-1/2 -mt-1.5 w-3 h-3 bg-white rounded-full border-l border-purple-200"></div>
                                </div>
                            </td>

                            <!-- Çek -->
                            <td class="px-6 py-4">
                                <a href="{{ route('sales.show', $lottery->id) }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline flex items-center">
                                    <i class="fa-solid fa-receipt mr-1 text-gray-400"></i> #{{ $lottery->receipt_code }}
                                </a>
                            </td>

                            <!-- Tarix -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">Pərakəndə Müştəri</div>
                                <div class="text-xs text-gray-500 flex items-center mt-0.5">
                                    <i class="fa-regular fa-calendar mr-1"></i>
                                    {{ $lottery->created_at->format('d.m.Y H:i') }}
                                </div>
                            </td>

                            <!-- Məbləğ -->
                            <td class="px-6 py-4 text-right font-bold text-gray-800 font-mono">
                                {{ number_format($lottery->grand_total, 2) }} ₼
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                    <span class="w-1.5 h-1.5 bg-green-600 rounded-full mr-1.5"></span>
                                    Aktiv
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="bg-gray-100 p-4 rounded-full mb-3">
                                        <i class="fa-solid fa-ticket text-3xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Lotoreya Kodu Yoxdur</h3>
                                    <p class="text-sm text-gray-500 mt-1">Hələ heç bir satışdan lotoreya kodu yaranmayıb.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $lotteries->links() }}
        </div>
    </div>
@endsection
