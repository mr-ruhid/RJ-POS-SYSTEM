@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto" x-data="{ isOpen: false }">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Kassir Hesabları</h1>
            <p class="text-sm text-gray-500 mt-1">Mağaza işçilərinin giriş məlumatları</p>
        </div>
        <button @click="isOpen = true" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-bold shadow-md transition flex items-center">
            <i class="fa-solid fa-user-plus mr-2"></i> Yeni Kassir
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6 shadow-sm border-l-4 border-green-500">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Kassir Adı</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Login (Email)</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Status</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-center">Yaradılıb</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase text-right">Əməliyyat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($users as $user)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center font-bold mr-3 border border-green-200">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <span class="font-medium text-gray-900">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ $user->email }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($user->is_active)
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold">Aktiv</span>
                            @else
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">Bloklanıb</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center text-sm text-gray-500">
                            {{ $user->created_at->format('d.m.Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bu kassir hesabını silmək istədiyinizə əminsiniz?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 p-2 rounded-lg hover:bg-red-50 transition" title="Sil">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $users->links() }}
        </div>
    </div>

    <!-- MODAL: Yeni Kassir -->
    <div x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="isOpen = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    <!-- Avtomatik Kassir Rolu Seçilir -->
                    <input type="hidden" name="role_id" value="{{ $kassaRole->id ?? '' }}">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center mb-4">
                            <div class="bg-green-100 p-2 rounded-full mr-3">
                                <i class="fa-solid fa-cash-register text-green-600"></i>
                            </div>
                            <h3 class="text-lg leading-6 font-bold text-gray-900">Yeni Kassir Hesabı</h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ad Soyad</label>
                                <input type="text" name="name" required class="w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500 shadow-sm" placeholder="Məs: Əli Vəliyev">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Login (Email)</label>
                                <input type="email" name="email" required class="w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500 shadow-sm" placeholder="kassa1@market.az">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Şifrə</label>
                                <input type="password" name="password" required class="w-full rounded-lg border-gray-300 focus:ring-green-500 focus:border-green-500 shadow-sm" placeholder="******">
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Yarat
                        </button>
                        <button type="button" @click="isOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Ləğv
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
