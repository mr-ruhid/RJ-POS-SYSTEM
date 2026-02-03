@extends('layouts.admin')

@section('content')
<div class="flex flex-col md:flex-row gap-6">

    <!-- SOL: Yeni Ödəniş Metodu -->
    <div class="w-full md:w-1/3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fa-regular fa-credit-card mr-2 text-purple-600"></i>Yeni Metod
            </h2>

            <form action="{{ route('settings.payments.store') }}" method="POST" x-data="{ isIntegrated: false }">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ad</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-gray-300 shadow-sm" placeholder="Məs: Kapital Bank">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Növ</label>
                        <select name="type" class="w-full rounded-lg border-gray-300 shadow-sm">
                            <option value="card">Bank Kartı</option>
                            <option value="cash">Nəğd</option>
                            <option value="other">Digər (Kaspi, Bonus)</option>
                        </select>
                    </div>

                    <div class="border-t pt-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_integrated" value="1" x-model="isIntegrated" class="rounded text-blue-600 h-4 w-4">
                            <span class="ml-2 text-sm font-bold text-gray-700">Terminal İnteqrasiyası</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-6">Sistem avtomatik məbləği terminala göndərəcək.</p>
                    </div>

                    <!-- İnteqrasiya Ayarları (Yalnız seçildikdə görünür) -->
                    <div x-show="isIntegrated" x-transition class="bg-gray-50 p-3 rounded-lg space-y-3 border border-gray-200">
                        <div>
                            <label class="block text-xs font-medium text-gray-600">Protokol / Model</label>
                            <select name="driver_name" class="w-full rounded border-gray-300 text-sm">
                                <option value="pax">PAX Terminal</option>
                                <option value="ingenico">Ingenico</option>
                                <option value="verifone">Verifone</option>
                                <option value="web_serial">Web Serial API (USB)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">IP Ünvanı (Ethernet)</label>
                            <input type="text" name="ip_address" class="w-full rounded border-gray-300 text-sm" placeholder="192.168.1.50">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600">COM Port (USB/Serial)</label>
                            <input type="text" name="com_port" class="w-full rounded border-gray-300 text-sm" placeholder="COM1">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-lg transition">
                        Əlavə Et
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SAĞ: Siyahı -->
    <div class="w-full md:w-2/3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="font-bold text-gray-800">Aktiv Ödəniş Üsulları</h2>
            </div>

            <table class="w-full text-left border-collapse">
                <tbody class="divide-y divide-gray-100">
                    @foreach($methods as $method)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="font-bold text-gray-900">{{ $method->name }}</span>
                                @if($method->is_integrated)
                                    <span class="ml-2 bg-purple-100 text-purple-700 text-[10px] px-2 py-0.5 rounded border border-purple-200">
                                        <i class="fa-solid fa-link"></i> {{ strtoupper($method->driver_name) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($method->is_active)
                                    <span class="text-green-600 text-xs font-bold bg-green-100 px-2 py-1 rounded">Aktiv</span>
                                @else
                                    <span class="text-gray-500 text-xs font-bold bg-gray-100 px-2 py-1 rounded">Deaktiv</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($method->slug != 'cash')
                                    <form action="{{ route('settings.payments.toggle', $method->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="text-gray-400 hover:text-blue-600 mr-2">
                                            <i class="fa-solid fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('settings.payments.destroy', $method->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Silmək istədiyinizə əminsiniz?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400 italic">Sabit</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
