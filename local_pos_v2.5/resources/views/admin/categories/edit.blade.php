@extends('layouts.admin')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Kateqoriyanı Düzəlt</h1>
        <a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-blue-600 flex items-center">
            <i class="fa-solid fa-arrow-left mr-2"></i> Geri
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
        <form action="{{ route('categories.update', $category->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="space-y-5">
                <!-- Ad -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kateqoriya Adı</label>
                    <input type="text" name="name" value="{{ $category->name }}" required class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm">
                </div>

                <!-- Valideyn Kateqoriya -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ana Kateqoriya</label>
                    <select name="parent_id" class="w-full rounded-lg border-gray-300 focus:border-blue-500 shadow-sm">
                        <option value="">-- Bu Ana Kateqoriyadır --</option>
                        @foreach($allCategories as $cat)
                            <option value="{{ $cat->id }}" {{ $category->parent_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Mövcud Şəkil -->
                @if($category->image)
                    <div class="flex items-center p-3 bg-gray-50 rounded border border-gray-200">
                        <img src="{{ asset('storage/' . $category->image) }}" class="w-12 h-12 rounded object-cover mr-4">
                        <span class="text-sm text-gray-500">Cari şəkil</span>
                    </div>
                @endif

                <!-- Şəkil Dəyiş -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Şəkli Dəyiş</label>
                    <input type="file" name="image" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition">
                        Yenilə
                    </button>
                    <a href="{{ route('categories.index') }}" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded-lg text-center transition">
                        Ləğv et
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
