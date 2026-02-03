@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Partnyorlar</h1>
            <p class="text-sm text-gray-500">Əməkdaşlıq, Komissiya və Ödənişlər</p>
        </div>

        <div class="flex gap-2">
            <!-- Telegram-dan Yarat -->
            <button onclick="openTelegramModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center shadow-sm">
                <i class="fa-brands fa-telegram mr-2"></i> Telegram İstəkləri
            </button>

            <!-- Manual Yarat -->
            <button onclick="document.getElementById('manual-modal').classList.remove('hidden')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                <i class="fa-solid fa-plus mr-1"></i> Əllə
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6 border-l-4 border-green-500 shadow-sm flex items-center">
            <i class="fa-solid fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 border-l-4 border-red-500 shadow-sm flex items-center">
            <i class="fa-solid fa-circle-exclamation mr-2"></i> {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 text-sm">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- CƏDVƏL -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                <tr>
                    <th class="px-6 py-4">Partnyor</th>
                    <th class="px-6 py-4">Əlaqə</th>
                    <th class="px-6 py-4 text-center">Promokod</th>
                    <th class="px-6 py-4 text-center">Komissiya</th>
                    <th class="px-6 py-4 text-right">Balans</th>
                    <th class="px-6 py-4 text-right">Əməliyyat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($partners as $partner)
                    <tr class="hover:bg-gray-50 transition group">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-800">{{ $partner->name }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                @if($partner->telegram_chat_id)
                                    <span class="text-blue-600"><i class="fa-brands fa-telegram"></i> {{ $partner->telegram_chat_id }}</span>
                                @else
                                    <span class="text-gray-400 italic">Telegram yoxdur</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $partner->phone ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php $promo = $partner->promocodes->first(); @endphp
                            @if($promo)
                                <div class="bg-purple-100 text-purple-700 px-2 py-1 rounded inline-block font-mono font-bold text-xs border border-purple-200">
                                    {{ $promo->code }}
                                </div>
                                <div class="text-[10px] text-gray-500 mt-1">
                                    Endirim: {{ $promo->discount_type == 'percent' ? $promo->discount_value.'%' : $promo->discount_value.'₼' }}
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-full text-xs font-bold border border-orange-200">
                                {{ $partner->commission_percent }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="font-bold text-green-600 text-base">{{ number_format($partner->balance, 2) }} ₼</div>
                            <button onclick="openPayoutModal('{{ $partner->id }}', '{{ $partner->name }}', '{{ $partner->balance }}')" class="text-[10px] text-blue-600 hover:underline mt-1">
                                Ödəniş Et
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                <!-- Tənzimləmə (Edit Config) -->
                                <button onclick="openConfigModal('{{ $partner->id }}', '{{ $partner->name }}', '{{ $partner->commission_percent }}', '{{ $promo ? $promo->code : '' }}', '{{ $promo ? $promo->discount_value : '' }}')"
                                        class="p-2 bg-gray-100 hover:bg-blue-50 text-gray-600 hover:text-blue-600 rounded border border-gray-300 transition" title="Tənzimləmələr">
                                    <i class="fa-solid fa-gear"></i>
                                </button>

                                <!-- Silmək -->
                                <form action="{{ route('partners.destroy', $partner->id) }}" method="POST" onsubmit="return confirm('Partnyoru silmək istədiyinizə əminsiniz? Balans sıfırlanacaq.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 bg-gray-100 hover:bg-red-50 text-gray-600 hover:text-red-600 rounded border border-gray-300 transition" title="Sil">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-400">
                            <i class="fa-solid fa-users-slash text-4xl mb-3 block opacity-20"></i>
                            Partnyor tapılmadı.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            {{ $partners->links() }}
        </div>
    </div>

</div>

<!-- 1. TELEGRAM İSTƏKLƏRİ MODALI -->
<div id="telegram-modal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-xl w-[600px] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="text-lg font-bold text-gray-800"><i class="fa-brands fa-telegram text-blue-500 mr-2"></i> Telegram İstəkləri</h3>
            <button onclick="closeModal('telegram-modal')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>

        <div class="p-6 overflow-y-auto custom-scrollbar">
            <!-- Yüklənir -->
            <div id="tg-loading" class="text-center py-8 hidden">
                <i class="fa-solid fa-circle-notch fa-spin text-blue-500 text-3xl"></i>
                <p class="text-sm text-gray-500 mt-3">Serverdən sorğular yüklənir...</p>
            </div>

            <!-- Siyahı -->
            <div id="tg-list" class="space-y-2 mb-6"></div>

            <!-- Form (Seçiləndə görünür) -->
            <form id="tg-form" action="{{ route('partners.create_from_telegram') }}" method="POST" class="hidden border-t border-dashed border-gray-300 pt-6 mt-2 animate-fade-in">
                @csrf
                <input type="hidden" name="telegram_chat_id" id="form-chat-id">
                <input type="hidden" name="name" id="form-name">

                <div class="bg-blue-50 p-3 rounded-lg mb-4 text-sm text-blue-800 border border-blue-100 flex items-center">
                    <i class="fa-solid fa-user-check mr-2"></i> Seçilən: <span id="selected-user-name" class="font-bold ml-1"></span>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Promokod</label>
                        <input type="text" name="promo_code" required class="w-full border-gray-300 rounded-lg focus:ring-blue-500 uppercase" placeholder="Məs: ALI10">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Partnyor Telefonu</label>
                        <input type="text" name="phone" class="w-full border-gray-300 rounded-lg focus:ring-blue-500" placeholder="050xxxxxxx">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Müştəriyə Endirim</label>
                        <div class="flex">
                            <input type="number" name="discount_value" required min="0" step="0.1" class="w-full border-gray-300 rounded-l-lg focus:ring-blue-500" placeholder="5">
                            <select name="discount_type" class="border-gray-300 border-l-0 rounded-r-lg bg-gray-50 text-sm focus:ring-blue-500">
                                <option value="percent">%</option>
                                <option value="fixed">₼</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Partnyor Qazancı (%)</label>
                        <div class="relative">
                            <input type="number" name="commission_percent" required min="0" max="100" step="0.1" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 pr-8" placeholder="10">
                            <span class="absolute right-3 top-2.5 text-gray-400">%</span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg mt-6 shadow-md transition transform active:scale-95">
                    Təsdiqlə və Yarat
                </button>
            </form>
        </div>
    </div>
</div>

<!-- 2. ÖDƏNİŞ MODALI -->
<div id="payout-modal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl w-96 shadow-2xl p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Ödəniş Et</h3>
        <p class="text-sm text-gray-500 mb-4">Partnyor: <span id="payout-name" class="font-bold text-gray-800"></span></p>

        <form id="payout-form" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Məbləğ (Maks: <span id="max-payout">0</span> ₼)</label>
                <input type="number" name="amount" id="payout-amount" required step="0.01" min="0.01" class="w-full border-gray-300 rounded-lg focus:ring-green-500 text-lg font-bold text-green-600">
            </div>
            <div class="mb-6">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Qeyd</label>
                <textarea name="note" class="w-full border-gray-300 rounded-lg focus:ring-green-500 text-sm" rows="2" placeholder="Ödəniş qeydi..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal('payout-modal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">Ləğv</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-bold shadow-sm">Ödəniş Et</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. KONFİQURASİYA MODALI (Edit) -->
<div id="config-modal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl w-96 shadow-2xl p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Tənzimləmələr</h3>

        <form id="config-form" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Promokod</label>
                    <input type="text" name="promo_code" id="conf-code" required class="w-full border-gray-300 rounded-lg uppercase font-mono">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Müştəri Endirimi (Faiz/Məbləğ)</label>
                    <input type="number" name="discount_value" id="conf-discount" required step="0.1" class="w-full border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Partnyor Komissiyası (%)</label>
                    <input type="number" name="commission_percent" id="conf-comm" required step="0.1" max="100" class="w-full border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('config-modal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">Ləğv</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold shadow-sm">Yenilə</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. MANUAL ƏLAVƏ MODALI -->
<div id="manual-modal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl w-96 shadow-2xl p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Əllə Əlavə Et</h3>
        <form action="{{ route('partners.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div><label class="text-xs font-bold text-gray-500 uppercase">Ad Soyad</label><input type="text" name="name" required class="w-full border-gray-300 rounded-lg"></div>
                <div><label class="text-xs font-bold text-gray-500 uppercase">Telefon</label><input type="text" name="phone" class="w-full border-gray-300 rounded-lg"></div>
                <!-- Manualda komissiya və promokod sonradan edit ilə əlavə olunur -->
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('manual-modal')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">Ləğv</button>
                <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-700 font-bold">Yadda Saxla</button>
            </div>
        </form>
    </div>
</div>

<script>
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    // --- TELEGRAM SİYAHISI ---
    async function openTelegramModal() {
        document.getElementById('telegram-modal').classList.remove('hidden');
        const list = document.getElementById('tg-list');
        const loading = document.getElementById('tg-loading');

        list.innerHTML = '';
        loading.classList.remove('hidden');

        try {
            const res = await fetch('{{ route("partners.fetch_telegram") }}');
            const users = await res.json();

            loading.classList.add('hidden');

            if(users.length === 0) {
                list.innerHTML = '<div class="text-center text-gray-400 py-4 italic">Gözləyən istək yoxdur.</div>';
                return;
            }

            users.forEach(u => {
                const div = document.createElement('div');
                div.className = 'p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-blue-400 hover:shadow-md transition flex justify-between items-center';
                div.innerHTML = `
                    <div>
                        <div class="font-bold text-gray-800">${u.name}</div>
                        <div class="text-xs text-gray-500">@${u.username} • ${u.date}</div>
                    </div>
                    <div class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-xs font-mono">${u.chat_id}</div>
                `;
                div.onclick = () => selectTelegramUser(u);
                list.appendChild(div);
            });
        } catch(e) {
            loading.classList.add('hidden');
            list.innerHTML = '<div class="text-center text-red-500 py-4">Bağlantı xətası.</div>';
        }
    }

    function selectTelegramUser(user) {
        document.getElementById('tg-form').classList.remove('hidden');
        document.getElementById('form-chat-id').value = user.chat_id;
        document.getElementById('form-name').value = user.name;
        document.getElementById('selected-user-name').innerText = user.name;

        // Scroll to form
        document.getElementById('tg-form').scrollIntoView({behavior: 'smooth'});
    }

    // --- ÖDƏNİŞ ---
    function openPayoutModal(id, name, balance) {
        document.getElementById('payout-name').innerText = name;
        document.getElementById('max-payout').innerText = balance;
        document.getElementById('payout-amount').max = balance;
        document.getElementById('payout-form').action = `/partners/${id}/payout`;
        document.getElementById('payout-modal').classList.remove('hidden');
    }

    // --- KONFİQURASİYA ---
    function openConfigModal(id, name, comm, code, disc) {
        document.getElementById('config-form').action = `/partners/${id}/update-config`;
        document.getElementById('conf-comm').value = comm;
        document.getElementById('conf-code').value = code;
        document.getElementById('conf-discount').value = disc;
        document.getElementById('config-modal').classList.remove('hidden');
    }
</script>

<style>
    .animate-fade-in { animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    /* Scrollbar */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
</style>
@endsection
