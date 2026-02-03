@extends('layouts.admin')

@section('content')
    <!-- Başlıq və Əlavə Et Düyməsi -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Rollar və İcazələr</h1>
            <p class="text-sm text-gray-500 mt-1">Sistemdəki istifadəçi rollarını idarə edin</p>
        </div>
        <button class="mt-4 md:mt-0 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium transition shadow-md flex items-center">
            <i class="fa-solid fa-shield-halved mr-2"></i> Yeni Rol Yarat
        </button>
    </div>

    <!-- Rollar Cədvəli -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Rol Adı</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kod (Slug)</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">İcazələr</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">

                    {{-- PHP Kodu ilə Rolları Dövr edirik (Məlumat bazadan gələcək) --}}
                    @forelse($roles as $role)
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <!-- Rol Adı -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-lg">
                                        {{ substr($role->name, 0, 1) }}
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $role->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $role->users_count ?? 0 }} istifadəçi</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Slug -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200 font-mono">
                                    {{ $role->slug }}
                                </span>
                            </td>

                            <!-- İcazələr -->
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @php
                                        // JSON-u array-ə çeviririk (Modeldə cast etməsəydik)
                                        $perms = is_array($role->permissions) ? $role->permissions : json_decode($role->permissions, true);
                                    @endphp

                                    @if(isset($perms['all']) && $perms['all'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                                            <i class="fa-solid fa-star mr-1 text-[10px]"></i> Tam İcazə (Super Admin)
                                        </span>
                                    @else
                                        @foreach($perms as $key => $value)
                                            @if($value)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                                    {{ str_replace('.', ' ', ucfirst($key)) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                            </td>

                            <!-- Düymələr -->
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                @if($role->slug !== 'super_admin')
                                    <button class="text-blue-600 hover:text-blue-900 mr-3" title="Düzəliş et">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <button class="text-red-600 hover:text-red-900" title="Sil">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 italic">Toxunulmaz</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                <i class="fa-solid fa-folder-open text-4xl mb-3 text-gray-300"></i>
                                <p>Hələ heç bir rol tapılmadı.</p>
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>
@endsection
