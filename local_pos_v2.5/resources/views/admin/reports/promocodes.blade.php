@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Promokod Hesabatı</h1>
        <p class="text-gray-500 mt-1">Endirim kampaniyalarının effektivliyi</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase font-semibold">
                        <th class="p-4 border-b">Kod</th>
                        <th class="p-4 border-b">Endirim</th>
                        <th class="p-4 border-b text-center">İstifadə Sayı</th>
                        <th class="p-4 border-b">Partnyor</th>
                        <th class="p-4 border-b text-center">Status</th>
                        <th class="p-4 border-b">Bitmə Tarixi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($promocodes as $promo)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 font-bold text-blue-600 font-mono text-lg">
                                {{ $promo->code }}
                            </td>
                            <td class="p-4">
                                <span class="bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs font-bold">
                                    {{ $promo->discount_type == 'percent' ? $promo->discount_value.'%' : $promo->discount_value.' ₼' }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="text-lg font-bold text-gray-800">{{ $promo->orders_count }}</span>
                            </td>
                            <td class="p-4 text-gray-600">
                                {{ $promo->partner->name ?? 'Mağaza' }}
                            </td>
                            <td class="p-4 text-center">
                                @if($promo->is_active && (!$promo->expires_at || $promo->expires_at > now()))
                                    <span class="text-green-600 font-bold text-xs"><i class="fa-solid fa-check-circle"></i> Aktiv</span>
                                @else
                                    <span class="text-red-500 font-bold text-xs"><i class="fa-solid fa-ban"></i> Bitib</span>
                                @endif
                            </td>
                            <td class="p-4 text-gray-500 text-xs">
                                {{ $promo->expires_at ? $promo->expires_at->format('d.m.Y') : 'Sonsuz' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-400">
                                Promokod tapılmadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
