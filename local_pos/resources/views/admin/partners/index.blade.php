@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto" x-data="partnerHandler()">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Partnyorlar (Telegram Bot)</h1>
            <p class="text-sm text-gray-500 mt-1">Bot vasitəsilə qeydiyyatdan keçən istifadəçilər və kod təyini</p>
        </div>

        <!-- Webhook Info -->
        <div class="bg-blue-50 text-blue-700 px-4 py-2 rounded-lg text-xs border border-blue-100 hidden md:block">
            <i class="fa-brands fa-telegram mr-1"></i> Bot istifadəçiləri avtomatik bura düşür
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <p class="font-bold">Uğurlu!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    {{-- LOKAL REJİM XƏBƏRDARLIĞI (Bloklama yox, sadəcə məlumat) --}}
    @if($systemMode == 'standalone')
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded shadow-sm flex items-start">
            <i class="fa-solid fa-triangle-exclamation text-yellow-500 mt-1 mr-3"></i>
            <div>
                <p class="font-bold text-yellow-800">Diqqət: Lokal Rejim</p>
                <p class="text-sm text-yellow-700">Siz hazırda Lokal (Standalone) rejimdəsiniz. Partnyorlara Telegram vasitəsilə bildirişlər getməyəcək, lakin məlumatları idarə edə bilərsiniz.</p>
            </div>
        </div>
    @endif

    <!-- Cədvəl -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

        @if($systemMode == 'client')
            <div class="bg-blue-50 px-6 py-3 border-b border-blue-100 text-blue-800 text-sm flex items-center">
                <i class="fa-solid fa-cloud-arrow-down mr-2"></i>
                Siz <b>Mağaza (Client)</b> rejimindəsiniz. Dəyişikliklər serverlə sinxronlaşdırılacaq.
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Partnyor Adı (Telegram)</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Chat ID</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Balans</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Promokodlar</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-right">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($partners as $partner)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
                                        <i class="fa-brands fa-telegram"></i>
                                    </div>
                                    <span class="font-medium text-gray-900">{{ $partner->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-gray-500">
                                {{ $partner->telegram_chat_id }}
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-green-600">
                                {{ number_format($partner->balance, 2) }} ₼
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($partner->promocodes->count() > 0)
                                    @foreach($partner->promocodes as $code)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-purple-100 text-purple-700 border border-purple-200 mb-1" title="Endirim: {{ $code->discount_value }}% | Komissiya: {{ $code->commission_value }}%">
                                            {{ $code->code }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-xs text-gray-400 italic">Kod yoxdur</span>
                                @endif
                            </td>

                            {{-- Düymələr İNDİ BÜTÜN REJİMLƏRDƏ GÖRÜNÜR --}}
                            <td class="px-6 py-4 text-right">
                                <button @click="openModal({{ $partner->id }}, '{{ addslashes($partner->name) }}')"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-xs font-medium transition shadow-sm">
                                    <i class="fa-solid fa-plus mr-1"></i> Kod Ver
                                </button>

                                <form action="{{ route('partners.destroy', $partner->id) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('Bu partnyoru silmək istədiyinizə əminsiniz?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fa-brands fa-telegram text-4xl text-gray-300 mb-3"></i>
                                    <p>Hələ heç kim bota daxil olmayıb.</p>
                                    @if($systemMode == 'standalone')
                                        <p class="text-xs mt-1 text-yellow-600">Lokal rejimdə bot işləmir. Partnyorları əl ilə əlavə edə bilməzsiniz (hazırkı məntiqə görə).</p>
                                    @else
                                        <p class="text-xs mt-1">İstifadəçilər bota <b>/start</b> yazanda burada görünəcəklər.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $partners->links() }}
        </div>
    </div>

    <!-- MODAL: Promokod Təyin Et (İNDİ HƏR REJİMDƏ AKTİVDİR) -->
    <div x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="{{ route('partners.assign_code') }}" method="POST">
                    @csrf
                    <input type="hidden" name="partner_id" x-model="modalData.id">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Promokod Təyin Et: <span x-text="modalData.name" class="text-blue-600"></span>
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Promokod (Avtomatik və ya Manual)</label>
                                <div class="flex gap-2">
                                    <input type="text" name="code" x-model="promoCode" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 uppercase font-bold text-gray-700" placeholder="Məs: RAUF10">
                                    <button type="button" @click="generateCode()" class="bg-gray-100 hover:bg-gray-200 px-3 rounded border text-gray-600">
                                        <i class="fa-solid fa-shuffle"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Müştəri Endirimi (%)</label>
                                    <input type="number" name="discount_value" required min="0" max="100" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="10">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Partnyor Qazancı (%)</label>
                                    <input type="number" name="commission_value" required min="0" max="100" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="5">
                                    <p class="text-[10px] text-gray-500 mt-1">Endirimli məbləğdən hesablanır</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Təsdiqlə və Göndər
                        </button>
                        <button type="button" @click="isOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Ləğv et
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function partnerHandler() {
        return {
            isOpen: false,
            promoCode: '',
            modalData: { id: null, name: '' },

            openModal(id, name) {
                this.modalData = { id, name };
                this.promoCode = '';
                this.isOpen = true;
            },

            generateCode() {
                // Sadə random kod generatoru
                let prefix = this.modalData.name.replace(/[^a-zA-Z]/g, '').substring(0, 4).toUpperCase();
                if(prefix.length < 3) prefix = 'PART';
                this.promoCode = prefix + Math.floor(Math.random() * 900 + 100);
            }
        }
    }
</script>
@endsection
