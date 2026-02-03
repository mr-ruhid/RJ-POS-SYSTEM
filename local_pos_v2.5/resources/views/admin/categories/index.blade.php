@extends('layouts.admin')

@section('content')
<div class="flex flex-col md:flex-row gap-6">

    <!-- SOL: Yeni Kateqoriya Forması -->
    <div class="w-full md:w-1/3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                <i class="fa-solid fa-plus-circle mr-2 text-blue-600"></i>Yeni Kateqoriya
            </h2>

            @if($errors->any())
                <div class="bg-red-50 text-red-700 p-3 rounded mb-4 text-sm">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="space-y-4">
                    <!-- Ad -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kateqoriya Adı</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm" placeholder="Məs: İçkilər">
                    </div>

                    <!-- Valideyn Kateqoriya -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ana Kateqoriya (Varsa)</label>
                        <select name="parent_id" class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm">
                            <option value="">-- Bu Ana Kateqoriyadır --</option>
                            @foreach($allCategories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Məs: "Şirələr" kateqoriyası üçün "İçkilər" seçin.</p>
                    </div>

                    <!-- Şəkil -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">İkon / Şəkil</label>
                        <input type="file" name="image" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition">
                        Yadda Saxla
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SAĞ: Kateqoriya Siyahısı -->
    <div class="w-full md:w-2/3">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">Kateqoriya Ağacı</h2>
                <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded">{{ $allCategories->count() }} Kateqoriya</span>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 m-4 rounded shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="p-4">
                @if($categories->count() > 0)
                    <ul class="space-y-3">
                        @foreach($categories as $parent)
                            <!-- Ana Kateqoriya -->
                            <li class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                                <div class="flex items-center justify-between p-3">
                                    <div class="flex items-center">
                                        @if($parent->image)
                                            <img src="{{ asset('storage/' . $parent->image) }}" class="w-8 h-8 rounded object-cover mr-3">
                                        @else
                                            <div class="w-8 h-8 rounded bg-blue-100 text-blue-500 flex items-center justify-center mr-3">
                                                <i class="fa-solid fa-layer-group"></i>
                                            </div>
                                        @endif
                                        <span class="font-bold text-gray-800">{{ $parent->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('categories.edit', $parent->id) }}" class="text-gray-400 hover:text-blue-600 transition">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form action="{{ route('categories.destroy', $parent->id) }}" method="POST" onsubmit="return confirm('Silmək istədiyinizə əminsiniz?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-gray-400 hover:text-red-600 transition">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Alt Kateqoriyalar -->
                                @if($parent->children->count() > 0)
                                    <div class="bg-white border-t border-gray-100 pl-11 pr-3 py-2 space-y-2">
                                        @foreach($parent->children as $child)
                                            <div class="flex items-center justify-between group">
                                                <div class="flex items-center">
                                                    <i class="fa-solid fa-turn-up rotate-90 text-gray-300 mr-2 text-xs"></i>
                                                    @if($child->image)
                                                        <img src="{{ asset('storage/' . $child->image) }}" class="w-6 h-6 rounded object-cover mr-2">
                                                    @endif
                                                    <span class="text-sm text-gray-600">{{ $child->name }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <a href="{{ route('categories.edit', $child->id) }}" class="text-gray-400 hover:text-blue-600 text-xs">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                    <form action="{{ route('categories.destroy', $child->id) }}" method="POST" onsubmit="return confirm('Silmək istədiyinizə əminsiniz?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-gray-400 hover:text-red-600 text-xs">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-10 text-gray-500">
                        <i class="fa-solid fa-folder-open text-4xl mb-3 text-gray-300"></i>
                        <p>Hələ kateqoriya yaradılmayıb.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
