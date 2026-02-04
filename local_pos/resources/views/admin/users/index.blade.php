@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">İstifadəçilər</h1>
            <p class="text-sm text-gray-500">Sistemə giriş icazəsi olan əməkdaşlar</p>
        </div>

        <!-- YALNIZ ADMİN GÖRƏ BİLƏR -->
        @if(Auth::user()->role->name === 'admin')
        <button onclick="document.getElementById('new-user-modal').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center shadow-sm">
            <i class="fa-solid fa-user-plus mr-2"></i> Yeni İşçi
        </button>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6 border-l-4 border-green-500">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-6 border-l-4 border-red-500">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                <tr>
                    <th class="px-6 py-4">Ad Soyad</th>
                    <th class="px-6 py-4">Email (Login)</th>
                    <th class="px-6 py-4">Rol (Vəzifə)</th>
                    <th class="px-6 py-4 text-center">Tarix</th>
                    @if(Auth::user()->role->name === 'admin')
                    <th class="px-6 py-4 text-right">Əməliyyat</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @foreach($users as $user)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-bold text-gray-800">{{ $user->name }}</td>
                        <td class="px-6 py-4 text-gray-600 font-mono">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            @if($user->role->name === 'admin')
                                <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold uppercase">Admin</span>
                            @else
                                <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold uppercase">Kassir</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center text-gray-500">{{ $user->created_at->format('d.m.Y') }}</td>

                        @if(Auth::user()->role->name === 'admin')
                        <td class="px-6 py-4 text-right">
                            @if($user->id !== Auth::id())
                                <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('İstifadəçini silmək istədiyinizə əminsiniz?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-2 rounded transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400">Siz</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

<!-- YENİ İSTİFADƏÇİ MODALI -->
<div id="new-user-modal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50 backdrop-blur-sm">
    <div class="bg-white p-6 rounded-xl w-96 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Yeni İşçi Əlavə Et</h3>
            <button onclick="document.getElementById('new-user-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ad Soyad</label>
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg p-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email (Giriş)</label>
                    <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg p-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Şifrə</label>
                    <input type="password" name="password" required class="w-full border border-gray-300 rounded-lg p-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Vəzifə (Rol)</label>
                    <select name="role_id" required class="w-full border border-gray-300 rounded-lg p-2 focus:ring-blue-500 bg-white">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('new-user-modal').classList.add('hidden')" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">Ləğv et</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 font-bold">Yadda Saxla</button>
            </div>
        </form>
    </div>
</div>
@endsection
