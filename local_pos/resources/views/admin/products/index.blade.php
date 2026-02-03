@extends('layouts.admin')

@section('content')
    <!-- Başlıq və Əməliyyatlar -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Məhsullar</h1>
            <p class="text-sm text-gray-500 mt-1">Sistemdəki bütün məhsulların siyahısı və idarə edilməsi</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('products.barcodes') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center">
                <i class="fa-solid fa-barcode mr-2"></i> Barkod Çapı
            </a>
            <a href="{{ route('products.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition shadow-md flex items-center">
                <i class="fa-solid fa-plus mr-2"></i> Yeni Məhsul
            </a>
        </div>
    </div>

    <!-- Bildiriş Mesajları (Uğurlu əlavə edildikdə) -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm" role="alert">
            <p class="font-bold">Uğurlu!</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <!-- Məhsul Cədvəli -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

        <!-- Filterlər (Gələcək üçün yer) -->
        <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <div class="relative w-full md:w-1/3">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fa-solid fa-search text-gray-400"></i>
                </span>
                <input type="text" class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm w-full focus:ring-blue-500 focus:border-blue-500" placeholder="Ad və ya Barkod ilə axtar...">
            </div>
            <!-- Pagination Məlumatı -->
            <div class="text-xs text-gray-500">
                Cəmi: <span class="font-bold text-gray-700">{{ $products->total() }}</span> məhsul
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">Şəkil</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Məhsul Adı / Barkod</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kateqoriya</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Qiymət (Satış)</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Mağaza Stoku</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50 transition duration-150 group">
                            <!-- Şəkil -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="h-10 w-10 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden">
                                    @if($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <i class="fa-solid fa-image text-gray-300"></i>
                                    @endif
                                </div>
                            </td>

                            <!-- Ad və Barkod -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                <div class="text-xs text-gray-500 font-mono mt-0.5 flex items-center">
                                    <i class="fa-solid fa-barcode mr-1 text-gray-400"></i> {{ $product->barcode }}
                                </div>
                            </td>

                            <!-- Kateqoriya -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($product->category)
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ $product->category->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 italic">Təyin edilməyib</span>
                                @endif
                            </td>

                            <!-- Satış Qiyməti -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-gray-900">{{ number_format($product->selling_price, 2) }} ₼</div>
                                @if($product->tax_rate > 0)
                                    <div class="text-[10px] text-gray-500">+{{ floatval($product->tax_rate) }}% Vergi</div>
                                @endif
                            </td>

                            <!-- Mağaza Stoku (YENİ) -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @php
                                    // Mağaza (LOC:store) stokunu hesablayırıq
                                    $storeQty = $product->batches->filter(fn($b) => str_contains($b->batch_code, 'LOC:store'))->sum('current_quantity');
                                @endphp
                                <span class="text-sm font-bold text-blue-600">{{ $storeQty }}</span>
                                <span class="text-xs text-gray-500">əd</span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($product->is_active)
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-green-100 text-green-600" title="Aktiv">
                                        <i class="fa-solid fa-check text-xs"></i>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-red-100 text-red-600" title="Passiv">
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </span>
                                @endif
                            </td>

                            <!-- Düymələr -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('products.edit', $product->id) }}" class="text-gray-500 hover:text-blue-600 p-1 border border-transparent hover:border-blue-100 rounded bg-transparent hover:bg-blue-50 transition" title="Redaktə et">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bu məhsulu silmək istədiyinizə əminsiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-500 hover:text-red-600 p-1 border border-transparent hover:border-red-100 rounded bg-transparent hover:bg-red-50 transition" title="Sil">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-16 w-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-box-open text-3xl text-gray-300"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Məhsul tapılmadı</h3>
                                    <p class="text-sm text-gray-500 mt-1 mb-4">Hələ sistemə heç bir məhsul əlavə edilməyib.</p>
                                    <a href="{{ route('products.create') }}" class="text-blue-600 hover:underline text-sm font-medium">
                                        İlk məhsulu əlavə et
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination (Səhifələmə) -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $products->links() }}
            {{-- Laravel-in standart pagination linkləri. Tailwind stilində görünməsi üçün AppServiceProvider-də Paginator::useTailwind() etmək lazımdır --}}
        </div>
    </div>
@endsection
