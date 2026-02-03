@extends('layouts.admin')

@section('content')
<div class="flex flex-col md:flex-row gap-6">

    <!-- SOL TƏRƏF: Yeni Kassa Əlavə Et -->
    <div class="w-full md:w-1/3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fa-solid fa-cash-register mr-2 text-blue-600"></i>Yeni Kassa
            </h2>

            @if($errors->any())
                <div class="bg-red-50 text-red-700 p-3 rounded mb-4 text-sm">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('registers.store') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kassa Adı</label>
                        <input type="text" name="name" placeholder="Məs: Kassa 1" required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kassa Kodu (Unikal)</label>
                        <input type="text" name="code" placeholder="Məs: POS-01" required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm uppercase">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">IP Ünvanı (Opsional)</label>
                        <input type="text" name="ip_address" placeholder="192.168.1.100"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm">
                        <p class="text-xs text-gray-500 mt-1">Şəbəkə printeri və ya terminal identifikasiyası üçün.</p>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition shadow-md">
                        Əlavə Et
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SAĞ TƏRƏF: Kassa Siyahısı -->
    <div class="w-full md:w-2/3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h2 class="font-bold text-gray-800">Mövcud Kassalar</h2>
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">{{ count($registers) }} ədəd</span>
            </div>

            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Ad / Kod</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">IP</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Balans</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($registers as $register)
                        <tr class="hover:bg-gray-50 group">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $register->name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $register->code }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $register->ip_address ?? '-' }}
                            </td>
                            <td class="px-6 py-4 font-mono font-bold text-gray-800">
                                {{ number_format($register->balance, 2) }} ₼
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <!-- Aktivlik -->
                                    @if($register->is_active)
                                        <span class="inline-flex w-fit items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Aktiv</span>
                                    @else
                                        <span class="inline-flex w-fit items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Deaktiv</span>
                                    @endif

                                    <!-- Növbə -->
                                    @if($register->status === 'open')
                                        <span class="text-[10px] text-green-600 font-bold">● Açıqdır</span>
                                    @else
                                        <span class="text-[10px] text-red-500">● Bağlıdır</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-2">
                                <!-- Status Dəyiş -->
                                <form action="{{ route('registers.toggle', $register->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-gray-400 hover:text-blue-600 p-1" title="Aktiv/Deaktiv et">
                                        <i class="fa-solid fa-power-off"></i>
                                    </button>
                                </form>

                                <!-- Sil -->
                                <form action="{{ route('registers.destroy', $register->id) }}" method="POST" onsubmit="return confirm('Bu kassanı silmək istədiyinizə əminsiniz?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600 p-1" title="Sil">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                Heç bir kassa nöqtəsi yaradılmayıb.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
