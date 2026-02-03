@extends('layouts.admin')

@section('content')
<div class="flex items-center justify-center h-[80vh]">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Məhsul Qaytarma</h1>
            <p class="text-gray-500 mt-2">Çek nömrəsini daxil edərək qaytarma əməliyyatına başlayın</p>
        </div>

        @if($errors->any())
            <div class="bg-red-50 text-red-700 p-4 rounded-lg mb-6 border border-red-200">
                <p class="font-bold"><i class="fa-solid fa-circle-exclamation mr-2"></i> Xəta:</p>
                <ul class="list-disc list-inside mt-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
            <form action="{{ route('returns.search') }}" method="GET">
                <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">Çek Nömrəsi (Receipt Code)</label>
                <div class="relative">
                    <i class="fa-solid fa-receipt absolute left-4 top-4 text-gray-400"></i>
                    <input type="text" name="receipt_code" placeholder="Məs: A1B2C3D4" required
                           class="w-full pl-12 pr-4 py-3.5 rounded-xl border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm text-lg font-mono uppercase" autofocus>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl mt-6 shadow-lg transform hover:-translate-y-0.5 transition duration-150 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i>
                    Axtar
                </button>
            </form>
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('pos.index') }}" class="text-gray-500 hover:text-gray-800 font-medium transition flex items-center justify-center">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kassaya Qayıt
            </a>
        </div>
    </div>
</div>
@endsection
