@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="{ activeTab: 'backup', processing: false, progress: 0, processMessage: '' }">

    <!-- LOADING MODAL (POP-UP) -->
    <!-- Bu pəncərə backup prosesi zamanı açılacaq -->
    <div x-show="processing" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4 animate-pulse">
                        <i class="fa-solid fa-server text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-lg font-bold leading-6 text-gray-900" x-text="processMessage"></h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Zəhmət olmasa gözləyin, sistem nüsxəsi hazırlanır. <br>
                        <span class="text-red-500 font-bold">Pəncərəni bağlamayın!</span>
                    </p>

                    <!-- Progress Bar (Simulyasiya) -->
                    <div class="w-full bg-gray-200 rounded-full h-4 mt-6 overflow-hidden">
                        <div class="bg-blue-600 h-4 rounded-full transition-all duration-300 ease-out flex items-center justify-center text-[10px] text-white font-bold"
                             :style="'width: ' + progress + '%'">
                            <span x-text="progress + '%'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Başlıq və Son Tarix -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Sistem Nüsxələnməsi</h1>

            @if($lastBackupDate)
                <div class="text-sm text-green-600 font-medium mt-1 flex items-center">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    Son nüsxə: {{ $lastBackupDate }}
                </div>
            @else
                <div class="text-sm text-gray-400 font-medium mt-1 flex items-center">
                    <i class="fa-regular fa-clock mr-2"></i>
                    Hələ nüsxə çıxarılmayıb
                </div>
            @endif
        </div>

        <div class="bg-white p-1 rounded-lg shadow-sm border border-gray-200 inline-flex">
            <button @click="activeTab = 'backup'"
                    :class="activeTab === 'backup' ? 'bg-blue-600 text-white shadow' : 'text-gray-600 hover:bg-gray-50'"
                    class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                <i class="fa-solid fa-cloud-arrow-up mr-2"></i> Backup Al
            </button>
            <button @click="activeTab = 'restore'"
                    :class="activeTab === 'restore' ? 'bg-green-600 text-white shadow' : 'text-gray-600 hover:bg-gray-50'"
                    class="px-4 py-2 rounded-md text-sm font-medium transition-all ml-1">
                <i class="fa-solid fa-clock-rotate-left mr-2"></i> Bərpa Et (Restore)
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm flex items-center">
            <i class="fa-solid fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm flex items-center">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i> {{ session('error') }}
        </div>
    @endif

    <!-- 1. BACKUP TABI -->
    <div x-show="activeTab === 'backup'" x-transition.opacity.duration.300ms>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Sol: Yeni Backup Yarat -->
            <div class="bg-white rounded-xl shadow-md p-6 border-t-4 border-blue-500">
                <h3 class="text-lg font-bold text-gray-800 mb-2">Yeni Nüsxə Yarat</h3>
                <p class="text-gray-500 text-sm mb-6">Sistemin cari vəziyyətini yadda saxlayın.</p>

                <div class="space-y-4">
                    <!-- Database Backup -->
                    <form action="{{ route('system.backup.create') }}" method="POST"
                          @submit="startProcess('Verilənlər Bazası (SQL) arxivlənir...')"
                          class="flex items-center justify-between p-4 border rounded-lg hover:bg-blue-50 transition border-gray-200 group">
                        @csrf
                        <input type="hidden" name="type" value="db">
                        <div>
                            <div class="font-bold text-gray-800 group-hover:text-blue-700 transition">Verilənlər Bazası (SQL)</div>
                            <div class="text-xs text-gray-500">Yalnız satışlar, məhsullar və istifadəçilər.</div>
                        </div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center">
                            <i class="fa-solid fa-database mr-2"></i> SQL Yarat
                        </button>
                    </form>

                    <!-- Full Backup -->
                    <form action="{{ route('system.backup.create') }}" method="POST"
                          @submit="startProcess('Tam Sistem (ZIP) hazırlanır. Bu bir neçə dəqiqə çəkə bilər...')"
                          class="flex items-center justify-between p-4 border rounded-lg hover:bg-purple-50 transition border-gray-200 group">
                        @csrf
                        <input type="hidden" name="type" value="full">
                        <div>
                            <div class="font-bold text-gray-800 group-hover:text-purple-700 transition">Tam Sistem (Full + SQL)</div>
                            <div class="text-xs text-gray-500">Bütün fayllar (Vendor daxil), şəkillər və DB.</div>
                        </div>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center">
                            <i class="fa-solid fa-file-zipper mr-2"></i> ZIP Yarat
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sağ: Qaydalar və Avto Backup -->
            <div class="space-y-6">
                <!-- Avto Backup Info -->
                <div class="bg-green-50 rounded-xl p-6 border border-green-200">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fa-solid fa-robot text-green-600 text-xl mt-1"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-bold text-green-800">Avtomatik Arxivləmə</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>Sistem hər gün saat <b>13:00 - 14:00</b> aralığında verilənlər bazasının (SQL) avtomatik nüsxəsini çıxarır.</p>
                                <p class="mt-2 text-xs opacity-75">* Nüsxələmə yalnız sistemə giriş edildikdə aktivləşir.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Qaydalar -->
                <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                    <h3 class="text-lg font-bold text-gray-700 mb-4"><i class="fa-solid fa-circle-info text-blue-500 mr-2"></i> Vacib Qeydlər</h3>
                    <ul class="space-y-3 text-sm text-gray-600 list-disc pl-5">
                        <li><b>SQL Backup:</b> Tez-tez edilməsi məsləhətdir. Ölçüsü kiçikdir.</li>
                        <li><b>Tam Backup:</b> Həftədə bir dəfə edilməsi məsləhətdir.</li>
                        <li>Nüsxələri yüklədikdən sonra onları <b>Google Drive</b> və ya xarici yaddaşda saxlayın.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. RESTORE TABI -->
    <div x-show="activeTab === 'restore'" x-cloak x-transition.opacity.duration.300ms>
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
            <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-gray-800">Mövcud Nüsxələr</h3>
                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded border border-yellow-200 font-semibold">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i> Bərpa etməzdən əvvəl cari vəziyyəti backup edin!
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 text-xs uppercase font-semibold">
                            <th class="p-4">Fayl Adı</th>
                            <th class="p-4">Tip</th>
                            <th class="p-4">Tarix</th>
                            <th class="p-4">Ölçü</th>
                            <th class="p-4 text-center">Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($files as $file)
                            <tr class="hover:bg-gray-50 transition group">
                                <td class="p-4 font-medium text-gray-800 flex items-center">
                                    <div class="p-2 rounded-full mr-3 {{ $file['type'] == 'full' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' }}">
                                        <i class="fa-solid {{ $file['type'] == 'full' ? 'fa-file-zipper' : 'fa-database' }}"></i>
                                    </div>
                                    {{ $file['name'] }}
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-1 rounded text-xs font-bold {{ $file['type'] == 'full' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $file['type'] == 'full' ? 'TAM SİSTEM' : 'DATABASE' }}
                                    </span>
                                </td>
                                <td class="p-4 text-gray-500">{{ $file['date'] }}</td>
                                <td class="p-4 text-gray-500">{{ $file['size'] }}</td>
                                <td class="p-4 flex justify-center space-x-2">
                                    <!-- Yüklə -->
                                    <a href="{{ route('system.backup.download', $file['name']) }}" class="text-gray-500 hover:text-blue-600 p-2 rounded hover:bg-blue-50 transition" title="Yüklə">
                                        <i class="fa-solid fa-download text-lg"></i>
                                    </a>

                                    <!-- Restore (Yalnız SQL üçün aktivdir) -->
                                    @if($file['type'] == 'db')
                                        <button onclick="confirmRestore('{{ $file['name'] }}', '{{ route('system.backup.restore', $file['name']) }}')" class="text-gray-500 hover:text-green-600 p-2 rounded hover:bg-green-50 transition" title="Bərpa Et">
                                            <i class="fa-solid fa-rotate-left text-lg"></i>
                                        </button>
                                    @else
                                        <button class="text-gray-300 cursor-not-allowed p-2" title="ZIP faylları yalnız manual bərpa olunur">
                                            <i class="fa-solid fa-rotate-left text-lg"></i>
                                        </button>
                                    @endif

                                    <!-- Sil -->
                                    <form action="{{ route('system.backup.delete', $file['name']) }}" method="POST" class="inline" onsubmit="return confirm('Bu nüsxəni silmək istədiyinizə əminsiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-500 hover:text-red-600 p-2 rounded hover:bg-red-50 transition" title="Sil">
                                            <i class="fa-solid fa-trash text-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="fa-solid fa-folder-open text-4xl mb-3 text-gray-300"></i>
                                        <p>Heç bir nüsxə tapılmadı.</p>
                                        <p class="text-xs mt-1">"Backup Al" bölməsindən yeni nüsxə yaradın.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Backup prosesini başladan funksiya
    function startProcess(msg) {
        // Alpine data-sına (x-data) çatmaq üçün sadə yol
        const container = document.querySelector('[x-data]');
        const data = Alpine.$data(container);

        data.processing = true;
        data.processMessage = msg;
        data.progress = 0;

        // Progress bar simulyasiyası
        let currentProgress = 0;
        const interval = setInterval(() => {
            if (currentProgress < 90) {
                // Yavaş-yavaş artır
                currentProgress += Math.floor(Math.random() * 5) + 1;
                data.progress = currentProgress;
            } else {
                clearInterval(interval);
            }
        }, 800);
    }

    function confirmRestore(name, url) {
        if(confirm('DİQQƏT: "' + name + '" faylını bərpa etmək istədiyinizə əminsiniz? \n\nBu əməliyyat cari məlumatları silib köhnə versiyanı yazacaq! \n\nRazısınızsa OK düyməsini sıxın.')) {
            // Restore düyməsini yükləmə rejiminə keçir
            const btn = document.activeElement;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            btn.disabled = true;

            // Pop-up-ı da açaq
            startProcess('Verilənlər Bazası bərpa olunur...');

            window.location.href = url;
        }
    }
</script>
@endsection
