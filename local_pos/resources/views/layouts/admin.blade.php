<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RJ POS - İdarəetmə Paneli</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased" x-data="{ sidebarOpen: true }">

    <div class="flex h-screen overflow-hidden">

        <!-- SIDEBAR (Sol Menyu) -->
        <aside class="bg-gray-900 text-white flex flex-col transition-all duration-300 ease-in-out shadow-xl z-20"
               :class="sidebarOpen ? 'w-64' : 'w-20'">

            <!-- Logo -->
            <div class="h-16 flex items-center justify-center bg-gray-800 shadow-md border-b border-gray-700">
                <i class="fa-solid fa-cash-register text-2xl text-blue-500"></i>
                <span class="ml-3 text-xl font-bold tracking-wider" x-show="sidebarOpen">RJ POS <span class="text-blue-500">v2</span></span>
            </div>

            <!-- Menyu Elementləri -->
            <div class="flex-1 overflow-y-auto py-4">
                <nav class="space-y-1 px-2">

                    <!-- 1. Ana Səhifə (HƏR KƏS) -->
                    <a href="{{ route('dashboard') }}" class="group flex items-center px-2 py-3 text-sm font-medium rounded-md hover:bg-gray-700 hover:text-white text-gray-300 transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : '' }}">
                        <i class="fa-solid fa-chart-pie mr-3 text-lg w-6 text-center"></i>
                        <span x-show="sidebarOpen">Ana Səhifə</span>
                    </a>

                    <!-- 2. Kassa (POS) (HƏR KƏS) -->
                    <a href="{{ route('pos.index') }}" class="group flex items-center px-2 py-3 text-sm font-medium rounded-md bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800 my-4 shadow-lg transform hover:scale-[1.02] transition-all">
                        <i class="fa-solid fa-desktop mr-3 text-lg w-6 text-center"></i>
                        <span x-show="sidebarOpen">KASSA EKRANI</span>
                    </a>

                    <!-- 3. Satışlar (HƏR KƏS) -->
                    <div x-data="{ open: {{ request()->routeIs('sales.*') || request()->routeIs('returns.*') ? 'true' : 'false' }} }">
                        <button @click="open = !open" class="w-full group flex items-center justify-between px-2 py-3 text-sm font-medium rounded-md hover:bg-gray-700 hover:text-white text-gray-300 focus:outline-none transition-colors">
                            <div class="flex items-center">
                                <i class="fa-solid fa-file-invoice-dollar mr-3 text-lg w-6 text-center"></i>
                                <span x-show="sidebarOpen">Satışlar</span>
                            </div>
                            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'transform rotate-180' : ''" x-show="sidebarOpen"></i>
                        </button>
                        <div x-show="open && sidebarOpen" x-cloak class="space-y-1 pl-11 pr-2 bg-gray-800/50 py-2 rounded-md mt-1 border-l-2 border-gray-700 ml-2">
                            <a href="{{ route('sales.index') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Satış Tarixçəsi</a>
                            <a href="{{ route('returns.index') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Geri Qaytarma</a>
                        </div>
                    </div>

                    <!-- ====================================================== -->
                    <!-- YALNIZ ADMIN GÖRƏ BİLƏCƏYİ MENYULAR -->
                    <!-- ====================================================== -->
                    @if(Auth::user()->role && Auth::user()->role->name === 'admin')

                        <!-- Məhsul İdarəsi -->
                        <div class="mt-4 pt-4 border-t border-gray-700">
                            <p class="px-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2" x-show="sidebarOpen">İdarəetmə</p>

                            <div x-data="{ open: {{ request()->routeIs('products.*') || request()->routeIs('categories.*') ? 'true' : 'false' }} }">
                                <button @click="open = !open" class="w-full group flex items-center justify-between px-2 py-3 text-sm font-medium rounded-md hover:bg-gray-700 hover:text-white text-gray-300 focus:outline-none transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-box-open mr-3 text-lg w-6 text-center"></i>
                                        <span x-show="sidebarOpen">Məhsullar</span>
                                    </div>
                                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'transform rotate-180' : ''" x-show="sidebarOpen"></i>
                                </button>
                                <div x-show="open && sidebarOpen" x-cloak class="space-y-1 pl-11 pr-2 bg-gray-800/50 py-2 rounded-md mt-1 border-l-2 border-gray-700 ml-2">
                                    <a href="{{ route('products.index') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Məhsul Siyahısı</a>
                                    <a href="{{ route('categories.index') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Kateqoriyalar</a>
                                    <a href="{{ route('products.barcodes') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Barkod Çapı</a>
                                    <a href="{{ route('products.discounts') }}" class="block px-2 py-2 text-sm text-orange-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors"><i class="fa-solid fa-percent mr-1 text-xs"></i> Endirimlər</a>
                                </div>
                            </div>

                            <!-- Stok və Anbar -->
                            <div x-data="{ open: {{ request()->routeIs('stocks.*') || request()->routeIs('suppliers.*') ? 'true' : 'false' }} }">
                                <button @click="open = !open" class="w-full group flex items-center justify-between px-2 py-3 text-sm font-medium rounded-md hover:bg-gray-700 hover:text-white text-gray-300 focus:outline-none transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-warehouse mr-3 text-lg w-6 text-center"></i>
                                        <span x-show="sidebarOpen">Stok və Anbar</span>
                                    </div>
                                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'transform rotate-180' : ''" x-show="sidebarOpen"></i>
                                </button>
                                <div x-show="open && sidebarOpen" x-cloak class="space-y-1 pl-11 pr-2 bg-gray-800/50 py-2 rounded-md mt-1 border-l-2 border-gray-700 ml-2">
                                    <a href="{{ route('stocks.index') }}" class="block px-2 py-2 text-sm font-semibold text-blue-300 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Ümumi Stok</a>
                                    <a href="{{ route('stocks.market') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Mağaza Stoku</a>
                                    <a href="{{ route('stocks.warehouse') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Anbar Stoku</a>
                                    <a href="{{ route('stocks.transfer') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Stok Transferi</a>
                                </div>
                            </div>

                            <!-- Lotoreya -->
                            <a href="{{ route('lotteries.index') }}" class="group flex items-center px-2 py-3 text-sm font-medium rounded-md hover:bg-gray-700 hover:text-white text-gray-300 transition-colors">
                                <i class="fa-solid fa-ticket mr-3 text-lg w-6 text-center text-yellow-500"></i>
                                <span x-show="sidebarOpen">Lotoreyalar</span>
                            </a>

                            <!-- Partnyorlar və Promokodlar -->
                            <div x-data="{ open: {{ request()->routeIs('partners.*') || request()->routeIs('promocodes.*') ? 'true' : 'false' }} }">
                                <button @click="open = !open" class="w-full group flex items-center justify-between px-2 py-3 text-sm font-medium rounded-md hover:bg-gray-700 hover:text-white text-gray-300 focus:outline-none transition-colors">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-users-gear mr-3 text-lg w-6 text-center"></i>
                                        <span x-show="sidebarOpen">Partnyorlar</span>
                                    </div>
                                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'transform rotate-180' : ''" x-show="sidebarOpen"></i>
                                </button>
                                <div x-show="open && sidebarOpen" x-cloak class="space-y-1 pl-11 pr-2 bg-gray-800/50 py-2 rounded-md mt-1 border-l-2 border-gray-700 ml-2">
                                    <a href="{{ route('partners.index') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Partnyor Siyahısı</a>
                                    <a href="{{ route('promocodes.index') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Promokodlar</a>
                                </div>
                            </div>

                            <!-- Hesabatlar -->
                            <a href="{{ route('reports.index') }}" class="group flex items-center px-2 py-3 text-sm font-medium rounded-md hover:bg-gray-700 hover:text-white text-gray-300 transition-colors">
                                <i class="fa-solid fa-chart-line mr-3 text-lg w-6 text-center"></i>
                                <span x-show="sidebarOpen">Hesabatlar</span>
                            </a>

                            <!-- İşçilər (Users) -->
                            <a href="{{ route('users.index') }}" class="group flex items-center px-2 py-3 text-sm font-medium rounded-md hover:bg-gray-700 hover:text-white text-gray-300 transition-colors">
                                <i class="fa-solid fa-users mr-3 text-lg w-6 text-center"></i>
                                <span x-show="sidebarOpen">İşçilər</span>
                            </a>

                            <!-- Rollar (Roles) - YENİ -->
                            <a href="{{ route('roles.index') }}" class="group flex items-center px-2 py-3 text-sm font-medium rounded-md hover:bg-gray-700 hover:text-white text-gray-300 transition-colors">
                                <i class="fa-solid fa-user-shield mr-3 text-lg w-6 text-center"></i>
                                <span x-show="sidebarOpen">Rollar</span>
                            </a>

                            <!-- Tənzimləmələr -->
                            <div class="mt-4 pt-4 border-t border-gray-700">
                                <div x-data="{ open: {{ request()->routeIs('settings.*') ? 'true' : 'false' }} }">
                                    <button @click="open = !open" class="w-full group flex items-center justify-between px-2 py-3 text-sm font-medium rounded-md hover:bg-gray-700 hover:text-white text-gray-300 focus:outline-none transition-colors">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-gears mr-3 text-lg w-6 text-center text-gray-400"></i>
                                            <span x-show="sidebarOpen">Tənzimləmələr</span>
                                        </div>
                                        <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'transform rotate-180' : ''" x-show="sidebarOpen"></i>
                                    </button>
                                    <div x-show="open && sidebarOpen" x-cloak class="space-y-1 pl-11 pr-2 bg-gray-800/50 py-2 rounded-md mt-1 border-l-2 border-gray-700 ml-2">
                                        <a href="{{ route('settings.store') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Mağaza</a>
                                        <a href="{{ route('settings.registers') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Kassalar</a>
                                        <a href="{{ route('settings.receipt') }}" class="block px-2 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Qəbz</a>
                                        <a href="{{ route('settings.server') }}" class="block px-2 py-2 text-sm text-red-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Server</a>
                                        <a href="{{ route('system.updates') }}" class="block px-2 py-2 text-sm text-blue-400 hover:text-white hover:bg-gray-700 rounded-md transition-colors">Yeniləmələr</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                    <!-- ====================================================== -->

                </nav>
            </div>

            <!-- Profil & Çıxış -->
            <div class="border-t border-gray-800 p-4 bg-gray-900">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center font-bold text-white uppercase border border-gray-600">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="ml-3" x-show="sidebarOpen">
                            <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-400">
                                {{ Auth::user()->role->name === 'admin' ? 'İdarəçi' : 'Kassir' }}
                            </p>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" x-show="sidebarOpen">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-400 transition-colors" title="Çıxış">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-6 z-10 border-b border-gray-200">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700 focus:outline-none transition-colors">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>

                <!-- Kassa Məlumatı -->
                <div class="flex items-center gap-4">
                    @if(session('cash_register_id'))
                        <div class="bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200 flex items-center">
                            <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span>
                            Kassa Aktiv
                        </div>
                    @else
                        @if(Auth::user()->role->name !== 'admin')
                            <div class="bg-red-50 text-red-700 px-3 py-1 rounded-full text-xs font-bold border border-red-200">
                                Kassa Seçilməyib
                            </div>
                        @endif
                    @endif
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
