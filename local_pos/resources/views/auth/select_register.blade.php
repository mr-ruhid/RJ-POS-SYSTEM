<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kassa Seçimi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-4">
            <i class="fa-solid fa-cash-register text-3xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-2">Kassa Seçimi</h2>
        <p class="text-gray-500 mb-6">İşə başlamaq üçün kassanı seçin</p>

        <form action="{{ route('register.open') }}" method="POST" class="space-y-4">
            @csrf
            <select name="register_id" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                @foreach($registers as $register)
                    <option value="{{ $register->id }}">{{ $register->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg transition shadow-lg">
                İşə Başla <i class="fa-solid fa-arrow-right ml-2"></i>
            </button>
        </form>

        <form action="{{ route('logout') }}" method="POST" class="mt-4">
            @csrf
            <button type="submit" class="text-sm text-red-500 hover:underline">Sistemdən Çıx</button>
        </form>
    </div>

</body>
</html>
