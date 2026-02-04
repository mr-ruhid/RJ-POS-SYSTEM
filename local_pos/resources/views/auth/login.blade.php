<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RJ POS - Sistemə Giriş</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
        <!-- Başlıq -->
        <div class="bg-blue-600 p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white text-blue-600 mb-4 shadow-lg">
                <i class="fa-solid fa-cash-register text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-white">RJ POS</h2>
            <p class="text-blue-100 text-sm">Sistemə daxil olun</p>
        </div>

        <!-- Form -->
        <div class="p-8">
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Ünvanı</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="email" required autofocus
                               class="pl-10 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm py-2.5 border"
                               placeholder="admin@sistem.az">
                    </div>
                    @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Şifrə</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" required
                               class="pl-10 w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm py-2.5 border"
                               placeholder="••••••••">
                    </div>
                    @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-600">Məni xatırla</span>
                    </label>
                </div>

                <button type="submit" class="w-full flex justify-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg shadow-lg transition duration-200 transform hover:-translate-y-0.5">
                    Daxil Ol
                </button>
            </form>
        </div>
        <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} RJ POS System. Bütün hüquqlar qorunur.
        </div>
    </div>

</body>
</html>
