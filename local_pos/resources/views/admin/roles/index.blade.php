@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Rollar və İcazələr</h1>
            <p class="text-sm text-gray-500">Sistemdəki istifadəçi vəzifələri və giriş hüquqları</p>
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

    <div class="flex flex-col md:flex-row gap-6">

        <!-- SOL TƏRƏF: Rol Siyahısı -->
        <div class="w-full md:w-2/3">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <h3 class="font-bold text-gray-700">Mövcud Rollar</h3>
                </div>
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                        <tr>
                            <th class="px-6 py-4">ID</th>
                            <th class="px-6 py-4">Rol Adı</th>
                            <th class="px-6 py-4">İcazələr</th>
                            <th class="px-6 py-4 text-right">Əməliyyat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @foreach($roles as $role)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-gray-500">#{{ $role->id }}</td>
                                <td class="px-6 py-4 font-bold text-gray-800">
                                    {{ ucfirst($role->name) }}
                                    @if($role->slug === 'admin')
                                        <span class="ml-2 bg-purple-100 text-purple-700 text-[10px] px-2 py-0.5 rounded border border-purple-200">Super Admin</span>
                                    @elseif($role->slug === 'cashier')
                                        <span class="ml-2 bg-blue-100 text-blue-700 text-[10px] px-2 py-0.5 rounded border border-blue-200">Sistem</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    @if($role->slug === 'admin')
                                        <span class="text-green-600 font-bold">Tam Giriş</span>
                                    @else
                                        @php
                                            // İcazələri JSON-dan massivə çeviririk (əgər varsa)
                                            $perms = is_string($role->permissions) ? json_decode($role->permissions, true) : $role->permissions;
                                        @endphp
                                        @if(!empty($perms))
                                            {{ count($perms) }} bölməyə icazə var
                                        @else
                                            <span class="text-gray-400">Məhdud</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($role->slug === 'admin')
                                        <span class="text-gray-300 italic text-xs"><i class="fa-solid fa-lock"></i> Qorunur</span>
                                    @else
                                        <div class="flex justify-end gap-2">
                                            <!-- Redaktə Düyməsi (Kassir üçün də aktivdir) -->
                                            <button onclick="openEditModal({{ json_encode($role) }})"
                                                    class="text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 p-2 rounded transition"
                                                    title="İcazələri Dəyiş">
                                                <i class="fa-solid fa-user-gear"></i>
                                            </button>

                                            <!-- Silmə Düyməsi (Kassir üçün gizlidir) -->
                                            @if($role->slug !== 'cashier')
                                                <form action="{{ route('roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Bu rolu silmək istədiyinizə əminsiniz?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded transition" title="Sil">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SAĞ TƏRƏF: Yeni Rol Forması -->
        <div class="w-full md:w-1/3">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fa-solid fa-plus-circle mr-2 text-blue-600"></i> Yeni Rol
                </h2>

                <form action="{{ route('roles.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rol Adı</label>
                            <input type="text" name="name" required placeholder="Məs: Anbar Müdiri"
                                   class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm p-2.5 border">
                            <p class="text-xs text-gray-500 mt-1">Sistemdə istifadə olunacaq vəzifə adı.</p>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg mt-6 shadow-md transition transform active:scale-95 flex items-center justify-center">
                        <i class="fa-solid fa-save mr-2"></i> Yadda Saxla
                    </button>
                </form>

                <div class="mt-6 p-4 bg-yellow-50 rounded-lg border border-yellow-100 text-sm text-yellow-800">
                    <p class="font-bold mb-1"><i class="fa-solid fa-lightbulb mr-1"></i> Məlumat</p>
                    Kassir rolunu silə bilməzsiniz, lakin icazələrini dəyişə bilərsiniz.
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ROL REDAKTƏ MODALI -->
<div id="edit-role-modal" class="fixed inset-0 bg-black/60 hidden flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white rounded-xl w-[500px] shadow-2xl p-6">
        <div class="flex justify-between items-center mb-4 border-b pb-3">
            <h3 class="text-xl font-bold text-gray-800 flex items-center">
                <i class="fa-solid fa-user-shield mr-2 text-blue-600"></i> İcazələri Tənzimlə
            </h3>
            <button onclick="document.getElementById('edit-role-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <form id="edit-role-form" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-1">Rol Adı</label>
                <input type="text" name="name" id="edit-role-name" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-blue-500">
            </div>

            <div class="mb-2">
                <label class="block text-sm font-bold text-gray-700 mb-2">Giriş İcazələri:</label>
                <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="pos" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Kassa (POS)</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="products" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Məhsullar</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="stocks" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Anbar & Stok</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="partners" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Partnyorlar</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="reports" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Hesabatlar</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="users" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">İşçilər</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="settings" class="rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Tənzimləmələr</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('edit-role-modal').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Ləğv et</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold shadow">Yadda Saxla</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(role) {
        document.getElementById('edit-role-form').action = `/roles/${role.id}`;
        document.getElementById('edit-role-name').value = role.name;

        // Checkboxları təmizlə
        const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
        checkboxes.forEach(cb => cb.checked = false);

        // İcazələri doldur
        if (role.permissions) {
            let perms = [];
            try {
                // Əgər stringdirsə parse et, yoxsa olduğu kimi götür
                perms = typeof role.permissions === 'string' ? JSON.parse(role.permissions) : role.permissions;
            } catch(e) { console.error(e); }

            if (Array.isArray(perms)) {
                perms.forEach(p => {
                    const cb = document.querySelector(`input[value="${p}"]`);
                    if(cb) cb.checked = true;
                });
            }
        }

        document.getElementById('edit-role-modal').classList.remove('hidden');
    }
</script>
@endsection
