@extends('layouts.admin')

@section('content')
    <div class="max-w-4xl mx-auto">
        <!-- Başlıq -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Yeni Məhsul</h1>
                <p class="text-sm text-gray-500 mt-1">Sistemə yeni məhsul əlavə etmək üçün formu doldurun</p>
            </div>
            <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-900 font-medium text-sm flex items-center bg-white border border-gray-300 px-4 py-2 rounded-lg shadow-sm transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Geri Qayıt
            </a>
        </div>

        <!-- Səhvlər varsa göstər -->
        @if ($errors->any())
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-circle-exclamation text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Diqqət! Bəzi məlumatlar düzgün deyil:</h3>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Maya dəyəri stokda təyin ediləcək, burada 0 göndəririk --}}
            <input type="hidden" name="cost_price" value="0">
            {{-- Vergi dərəcəsi stokda/partiyada təyin ediləcək (və ya satışda), burada 0 göndəririk --}}
            <input type="hidden" name="tax_rate" value="0">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- SOL TƏRƏF (Əsas Məlumatlar) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Kart: Ümumi Məlumat -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Ümumi Məlumatlar</h2>

                        <div class="space-y-4">
                            <!-- Ad -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Məhsulun Adı <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition"
                                    placeholder="Məs: Coca-Cola 0.5L">
                            </div>

                            <!-- Barkod və Kateqoriya -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="barcode" class="block text-sm font-medium text-gray-700 mb-1">Barkod <span class="text-red-500">*</span></label>
                                    <div class="relative rounded-md shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fa-solid fa-barcode text-gray-400"></i>
                                        </div>
                                        <input type="text" name="barcode" id="barcode" value="{{ old('barcode') }}" required
                                            class="pl-10 w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition"
                                            placeholder="Oxudun və ya yazın">
                                        <button type="button" onclick="document.getElementById('barcode').value = Math.floor(Math.random() * 899999999999 + 100000000000)" class="absolute inset-y-0 right-0 px-3 flex items-center bg-gray-50 border-l border-gray-300 rounded-r-lg hover:bg-gray-100 text-gray-500 text-xs font-medium cursor-pointer">
                                            Generasiya
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Kateqoriya</label>
                                    <select name="category_id" id="category_id" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition">
                                        <option value="">-- Kateqoriya Seçin --</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Təsvir -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Təsvir (İstəyə bağlı)</label>
                                <textarea name="description" id="description" rows="3" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition" placeholder="Məhsul haqqında qısa məlumat...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Kart: Qiymət -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2 flex items-center">
                            <i class="fa-solid fa-tag mr-2 text-blue-500"></i> Qiymət
                        </h2>

                        <div>
                            <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-1">Satış Qiyməti</label>
                            <div class="relative mt-1 rounded-md shadow-sm">
                                <input type="number" step="0.01" name="selling_price" id="selling_price"
                                       value="{{ old('selling_price') }}"
                                       required
                                       class="w-full rounded-lg border-gray-300 pl-3 pr-12 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition font-bold text-gray-800 text-lg" placeholder="0.00">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">AZN</span>
                                </div>
                            </div>
                            <p class="mt-2 text-xs text-gray-500">Bu, məhsulun baza satış qiymətidir.</p>
                        </div>
                    </div>
                </div>

                <!-- SAĞ TƏRƏF (Status və Şəkil) -->
                <div class="lg:col-span-1 space-y-6">

                    <!-- Kart: Status -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Görünüş və Status</h2>

                        <div class="flex items-center justify-between mb-4">
                            <span class="text-gray-700 font-medium">Status</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                <span class="ml-2 text-sm font-medium text-gray-900 peer-checked:text-green-600">Aktiv</span>
                            </label>
                        </div>
                    </div>

                    <!-- Kart: Şəkil -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Məhsul Şəkli</h2>

                        <div class="flex items-center justify-center w-full">
                            <label for="image-upload" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-3"></i>
                                    <p class="text-sm text-gray-500"><span class="font-semibold">Yükləmək üçün klikləyin</span></p>
                                    <p class="text-xs text-gray-500">PNG, JPG (Max 2MB)</p>
                                </div>
                                <input id="image-upload" name="image" type="file" class="hidden" accept="image/*" />
                            </label>
                        </div>
                    </div>

                    <!-- Yadda Saxla -->
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transform hover:-translate-y-0.5 transition duration-150 flex items-center justify-center">
                        <i class="fa-solid fa-floppy-disk mr-2"></i>
                        Yadda Saxla
                    </button>

                    <a href="{{ route('products.index') }}" class="block w-full text-center text-gray-500 hover:text-gray-700 text-sm mt-2">
                        Ləğv et
                    </a>

                </div>
            </div>
        </form>
    </div>
@endsection
