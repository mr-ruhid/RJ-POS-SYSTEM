@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto" x-data="receiptSettings()">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Qəbz Şablonu</h1>
            <p class="text-sm text-gray-500 mt-1">Kassa çeklərinin görünüşünü və formatını tənzimləyin</p>
        </div>
        <button type="submit" form="receiptForm" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-bold shadow-md transition flex items-center">
            <i class="fa-solid fa-floppy-disk mr-2"></i> Seçimi Yadda Saxla
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">Uğurlu!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- SOL TƏRƏF: Ayarlar -->
        <div class="lg:col-span-1 space-y-6">

            <form id="receiptForm" action="{{ route('settings.receipt.update') }}" method="POST">
                @csrf

                <!-- Şablon Seçimi -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Şablon Seçin</h3>

                    <div class="space-y-3">

                        <!-- Variant 1: Termal -->
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer transition hover:bg-blue-50"
                               :class="selectedTemplate === 'thermal' ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-500' : 'border-gray-200'">
                            <input type="radio" name="receipt_template" value="thermal" x-model="selectedTemplate" class="mt-1 text-blue-600 focus:ring-blue-500">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Termal Çek (Standart)</span>
                                <span class="block text-xs text-gray-500">80mm kassa printerləri üçün</span>
                            </div>
                        </label>

                        <!-- Variant 2: Rəsmi -->
                        <label class="flex items-start p-3 border rounded-lg cursor-pointer transition hover:bg-purple-50"
                               :class="selectedTemplate === 'official' ? 'border-purple-500 bg-purple-50 ring-1 ring-purple-500' : 'border-gray-200'">
                            <input type="radio" name="receipt_template" value="official" x-model="selectedTemplate" class="mt-1 text-purple-600 focus:ring-purple-500">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-900">Forma № 10 MQ (A4)</span>
                                <span class="block text-xs text-gray-500">Rəsmi mühasibatlıq sənədi</span>
                            </div>
                        </label>

                        <!-- Variant 3: Gələcək -->
                        <label class="flex items-start p-3 border border-gray-200 rounded-lg opacity-50 cursor-not-allowed bg-gray-50">
                            <input type="radio" disabled class="mt-1 text-gray-400">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-500">Modern Dizayn (Tezliklə)</span>
                                <span class="block text-xs text-gray-400">Logolu və rəngli dizayn</span>
                            </div>
                        </label>

                    </div>
                </div>

                <!-- Digər Opsiyalar -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Görünüş Ayarları</h3>

                    <div class="space-y-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="receipt_show_logo" value="1" x-model="showLogo" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                            <span class="ml-2 text-sm text-gray-700">Mağaza logosunu göstər</span>
                        </label>

                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="receipt_show_qr" value="1" x-model="showQr" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                            <span class="ml-2 text-sm text-gray-700">QR Kod göstər</span>
                        </label>

                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="receipt_show_currency_symbol" value="1" checked disabled class="rounded text-gray-400 h-4 w-4">
                            <span class="ml-2 text-sm text-gray-500">Valyuta simvolu (₼) <span class="text-xs text-gray-400">(Həmişə aktiv)</span></span>
                        </label>
                    </div>
                </div>
            </form>

        </div>

        <!-- SAĞ TƏRƏF: Canlı Önizləmə (Preview) -->
        <div class="lg:col-span-2">
            <div class="bg-gray-100 rounded-xl border border-gray-300 p-8 min-h-[600px] flex justify-center items-start overflow-hidden relative">
                <div class="absolute top-4 left-4 bg-white/80 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-gray-500 border border-gray-200">
                    <i class="fa-solid fa-eye mr-1"></i> Canlı Önizləmə
                </div>

                <!-- PREVIEW: Termal (80mm) -->
                <div x-show="selectedTemplate === 'thermal'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="bg-white p-4 shadow-xl w-[300px] text-[10px] font-mono text-gray-800 leading-tight border-t-8 border-gray-800 relative">

                    <!-- Simulyasiya edilmiş kassa çeki -->
                    <div class="text-center mb-4">
                        <template x-if="showLogo">
                            <div class="mb-2 flex justify-center">
                                <div class="w-12 h-12 bg-gray-800 text-white flex items-center justify-center rounded-full font-bold text-lg">LOGO</div>
                            </div>
                        </template>
                        <h2 class="text-sm font-bold uppercase">{{ $settings['store_name'] ?? 'RJ POS MARKET' }}</h2>
                        <p>{{ $settings['store_address'] ?? 'Bakı şəhəri' }}</p>
                        <p>Tel: {{ $settings['store_phone'] ?? '+994 50 000 00 00' }}</p>
                    </div>

                    <div class="border-b border-dashed border-black my-2"></div>

                    <div class="flex justify-between">
                        <span>Çek №: 123456</span>
                        <span>{{ date('d.m.Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Kassir: Admin</span>
                        <span>POS-01</span>
                    </div>

                    <div class="border-b border-dashed border-black my-2"></div>

                    <!-- Məhsullar -->
                    <div class="space-y-1">
                        <div class="flex justify-between font-bold border-b border-black pb-1 mb-1">
                            <span>Məhsul</span>
                            <span>Cəm</span>
                        </div>
                        <div class="flex justify-between">
                            <span>1 x Coca Cola 0.5L</span>
                            <span>1.50</span>
                        </div>
                        <div class="flex justify-between">
                            <span>2 x Lays Çipsi</span>
                            <span>5.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>1 x Çörək</span>
                            <span>0.70</span>
                        </div>
                    </div>

                    <div class="border-b border-dashed border-black my-2"></div>

                    <div class="flex justify-between font-bold text-xs">
                        <span>TOPLAM:</span>
                        <span>7.20 ₼</span>
                    </div>

                    <!-- Lotoreya -->
                    <div class="mt-4 border-2 border-black p-2 text-center">
                        <div class="font-bold">LOTOREYA KODU</div>
                        <div class="text-lg font-bold tracking-widest my-1">RJ-A1B2</div>
                        <div class="text-[8px]">Uduşlu kampaniya üçün saxlayın</div>
                    </div>

                    <template x-if="showQr">
                        <div class="mt-4 flex justify-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=DemoReceipt" alt="QR" class="opacity-80">
                        </div>
                    </template>

                    <div class="text-center mt-4">
                        <p>{{ $settings['receipt_footer'] ?? 'Təşəkkür edirik!' }}</p>
                    </div>

                    <!-- Kağızın kəsik effekti -->
                    <div class="absolute -bottom-2 left-0 w-full h-4 bg-gray-100" style="background: radial-gradient(circle, transparent 50%, #f3f4f6 50%) 0 0/10px 10px repeat-x;"></div>
                </div>

                <!-- PREVIEW: Rəsmi (A4) -->
                <div x-show="selectedTemplate === 'official'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="bg-white p-8 shadow-xl w-[450px] min-h-[600px] text-[10px] font-serif text-gray-900 border border-gray-300 relative">

                     <div class="text-right font-bold mb-4">Forma № 10 MQ</div>

                     <div class="text-center mb-6">
                         <h1 class="text-sm font-bold uppercase mb-1">Mədaxil Qəbzi</h1>
                         <p class="text-[9px]">Seriya A № 123456</p>
                     </div>

                     <div class="mb-4 space-y-1">
                         <div class="flex border-b border-black pb-1">
                             <span class="w-24 font-bold">Müəssisə:</span>
                             <span>{{ $settings['store_name'] ?? 'RJ POS Market' }}</span>
                         </div>
                         <div class="flex border-b border-black pb-1">
                             <span class="w-24 font-bold">VÖEN:</span>
                             <span>1234567890</span>
                         </div>
                     </div>

                     <table class="w-full border-collapse border border-black mb-4">
                         <thead class="bg-gray-100">
                             <tr>
                                 <th class="border border-black p-1">Malın adı</th>
                                 <th class="border border-black p-1">Ölçü</th>
                                 <th class="border border-black p-1">Miqdar</th>
                                 <th class="border border-black p-1">Məbləğ</th>
                             </tr>
                         </thead>
                         <tbody>
                             <tr>
                                 <td class="border border-black p-1">Mal 1</td>
                                 <td class="border border-black p-1 text-center">əd</td>
                                 <td class="border border-black p-1 text-center">5</td>
                                 <td class="border border-black p-1 text-right">10.00</td>
                             </tr>
                             <tr>
                                 <td class="border border-black p-1">Mal 2</td>
                                 <td class="border border-black p-1 text-center">kq</td>
                                 <td class="border border-black p-1 text-center">2</td>
                                 <td class="border border-black p-1 text-right">5.50</td>
                             </tr>
                             <tr>
                                 <td colspan="3" class="border border-black p-1 text-right font-bold">YEKUN:</td>
                                 <td class="border border-black p-1 text-right font-bold">15.50 ₼</td>
                             </tr>
                         </tbody>
                     </table>

                     <div class="mt-8 flex justify-between px-4">
                         <div class="text-center">
                             <div class="border-b border-black w-24 mb-1"></div>
                             <span>(İmza)</span>
                         </div>
                         <div class="text-center">
                             <div class="border-b border-black w-24 mb-1"></div>
                             <span>(Möhdür)</span>
                         </div>
                     </div>

                </div>

            </div>
        </div>

    </div>

</div>

<script>
    function receiptSettings() {
        return {
            // Bazadakı dəyəri bura gətirəcəyik (Blade ilə)
            selectedTemplate: '{{ $settings['receipt_template'] ?? 'thermal' }}',
            showLogo: {{ ($settings['receipt_show_logo'] ?? '0') == '1' ? 'true' : 'false' }},
            showQr: {{ ($settings['receipt_show_qr'] ?? '0') == '1' ? 'true' : 'false' }}
        }
    }
</script>
@endsection
