@extends('layouts.admin')

@section('content')
<div class="flex flex-col md:flex-row gap-6">

    <!-- SOL TƏRƏF: Yeni Vergi Əlavə Et -->
    <div class="w-full md:w-1/3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fa-solid fa-plus-circle mr-2 text-blue-600"></i>Yeni Vergi Növü
            </h2>

            <form action="{{ route('taxes.store') }}" method="POST">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vergi Adı</label>
                        <input type="text" name="name" placeholder="Məs: ƏDV" required
                               class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dərəcə (%)</label>
                        <div class="relative">
                            <input type="number" step="0.01" name="rate" placeholder="18" required
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm pr-8">
                            <span class="absolute right-3 top-2 text-gray-500">%</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition shadow-md">
                        Əlavə Et
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SAĞ TƏRƏF: Mövcud Vergilər -->
    <div class="w-full md:w-2/3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <h2 class="font-bold text-gray-800">Mövcud Vergi Dərəcələri</h2>
            </div>

            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Ad</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Faiz</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase text-right">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($taxes as $tax)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $tax->name }}</td>
                            <td class="px-6 py-4 font-bold text-blue-600">{{ $tax->rate }}%</td>
                            <td class="px-6 py-4">
                                @if($tax->is_active)
                                    <span class="px-2 py-1 text-xs font-bold text-green-700 bg-green-100 rounded-full">Aktiv</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-bold text-gray-700 bg-gray-100 rounded-full">Passiv</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-2">
                                <!-- Status Dəyiş -->
                                <form action="{{ route('taxes.toggle', $tax->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-gray-400 hover:text-blue-600" title="Statusu dəyiş">
                                        <i class="fa-solid fa-power-off"></i>
                                    </button>
                                </form>

                                <!-- Sil -->
                                <form action="{{ route('taxes.destroy', $tax->id) }}" method="POST" onsubmit="return confirm('Silmək istədiyinizə əminsiniz?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600" title="Sil">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                Vergi dərəcəsi əlavə edilməyib.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
