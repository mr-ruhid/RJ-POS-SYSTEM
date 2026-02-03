@extends('layouts.admin')

@section('content')
<div class="flex flex-col md:flex-row gap-6">

    <!-- SOL TƏRƏF: Yeni Promokod Forması -->
    <div class="w-full md:w-1/3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fa-solid fa-ticket mr-2 text-blue-600"></i>Yeni Promokod
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

            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('promocodes.store') }}" method="POST" x-data="{ code: '' }">
                @csrf

                <div class="space-y-4">
                    <!-- Kod -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kod</label>
                        <div class="flex gap-2">
                            <input type="text" name="code" x-model="code" required
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm uppercase font-mono"
                                   placeholder="Məs: YAY2024">
                            <button type="button" @click="code = 'SALE' + Math.floor(Math.random() * 9000 + 1000)"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 rounded-lg border border-gray-300" title="Random Kod">
                                <i class="fa-solid fa-shuffle"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Endirim Növü və Dəyəri -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Növ</label>
                            <select name="discount_type" class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm">
                                <option value="percent">Faiz (%)</option>
                                <option value="fixed">Məbləğ (AZN)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dəyər</label>
                            <input type="number" name="discount_value" required min="0" step="0.01"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm" placeholder="0">
                        </div>
                    </div>

                    <!-- Limit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">İstifadə Limiti (Opsional)</label>
                        <input type="number" name="usage_limit" min="1"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm"
                               placeholder="Sonsuz">
                        <p class="text-xs text-gray-500 mt-1">Neçə dəfə istifadə oluna bilər? Boş qalsa sonsuzdur.</p>
                    </div>

                    <!-- Son Tarix -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bitmə Tarixi (Opsional)</label>
                        <input type="date" name="expires_at"
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm text-gray-600">
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition">
                        Yarat
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SAĞ: Promokod Siyahısı -->
    <div class="w-full md:w-2/3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">Aktiv Mağaza Kodları</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 text-gray-500 uppercase font-bold text-xs">
                        <tr>
                            <th class="px-4 py-3">Kod</th>
                            <th class="px-4 py-3">Endirim</th>
                            <th class="px-4 py-3 text-center">İstifadə</th>
                            <th class="px-4 py-3">Bitmə Tarixi</th>
                            <th class="px-4 py-3 text-right">Sil</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($promocodes as $promo)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-bold text-blue-600 tracking-wide">
                                    {{ $promo->code }}
                                </td>
                                <td class="px-4 py-3 font-bold text-gray-700">
                                    @if($promo->discount_type == 'percent')
                                        {{ floatval($promo->discount_value) }}%
                                    @else
                                        {{ number_format($promo->discount_value, 2) }} ₼
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                                        {{ $promo->used_count }} / {{ $promo->usage_limit ?? '∞' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 text-xs">
                                    {{ $promo->expires_at ? $promo->expires_at->format('d.m.Y') : 'Müddətsiz' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form action="{{ route('promocodes.destroy', $promo->id) }}" method="POST" onsubmit="return confirm('Silmək istədiyinizə əminsiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600 transition">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">
                                    Heç bir promokod tapılmadı.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-gray-200">
                {{ $promocodes->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
