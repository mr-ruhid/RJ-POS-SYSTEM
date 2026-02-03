@extends('layouts.admin')

@section('content')
<div class="h-[calc(100vh-120px)] flex flex-col md:flex-row gap-4"
     x-data="{
        searchQuery: '',
        products: [],
        cart: [],
        isLoading: false,
        isProcessing: false,
        showCheckoutModal: false,
        promoCode: '',

        // Səhifə yüklənəndə axtarışa fokuslan
        init() {
            this.$nextTick(() => {
                if(this.$refs.searchInput) this.$refs.searchInput.focus();
            });
        },

        // Məhsul Axtarışı
        async searchProducts() {
            if(this.searchQuery.length > 0 && this.searchQuery.length < 2) return;

            this.isLoading = true;
            try {
                const response = await fetch(`{{ route('pos.search') }}?query=${this.searchQuery}`);
                const data = await response.json();
                this.products = data;

                // Əgər 1 məhsul tapılıbsa və barkod tam uyğundursa, birbaşa səbətə at
                if (this.products.length === 1 && this.products[0].barcode === this.searchQuery) {
                    this.addToCart(this.products[0]);
                    this.searchQuery = '';
                    this.products = [];
                }
            } catch (error) {
                console.error('Axtarış xətası:', error);
            } finally {
                this.isLoading = false;
            }
        },

        // Səbətə At
        addToCart(product) {
            if (product.stock <= 0) {
                alert('Bu məhsul bitib!');
                return;
            }
            // Hədiyyə olmayan eyni məhsulu axtarırıq
            const existingItem = this.cart.find(item => item.id === product.id && !item.is_gift);

            if (existingItem) {
                if (existingItem.qty < product.stock) {
                    existingItem.qty++;
                } else {
                    alert('Maksimum stok sayına çatdınız!');
                }
            } else {
                this.cart.push({
                    ...product,
                    qty: 1,
                    is_gift: false // Default: Hədiyyə deyil
                });
            }
        },

        // Sayı Dəyiş
        updateQty(index, amount) {
            const item = this.cart[index];
            const newQty = item.qty + amount;

            if (newQty > item.stock) {
                alert('Stokda kifayət qədər məhsul yoxdur.');
                return;
            }

            if (newQty > 0) {
                item.qty = newQty;
            } else {
                this.removeFromCart(index);
            }
        },

        // Sil
        removeFromCart(index) {
            this.cart.splice(index, 1);
        },

        // Hədiyyə Rejimini Dəyiş
        toggleGift(index) {
            this.cart[index].is_gift = !this.cart[index].is_gift;
        },

        // Cəm Hesablamalar
        get totals() {
            let subtotal = 0;
            let discount = 0;
            let grandTotal = 0;

            this.cart.forEach(item => {
                // Əgər hədiyyədirsə, müştəri üçün qiymət 0-dır, hesablamaya qatmırıq
                if (!item.is_gift) {
                    let itemTotal = Number(item.final_price) * item.qty;
                    let itemDiscount = Number(item.discount_amount) * item.qty;

                    subtotal += (Number(item.price) * item.qty);
                    discount += itemDiscount;
                    grandTotal += itemTotal;
                }
            });

            return { subtotal, discount, grandTotal };
        },

        // Modal Aç
        openCheckout() {
            if (this.cart.length === 0) return;
            this.showCheckoutModal = true;
        },

        // Satışı Tamamla (API Sorğusu)
        async processPayment(method) {
            this.isProcessing = true;
            try {
                const response = await fetch(`{{ route('pos.store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        cart: this.cart,
                        payment_method: method,
                        paid_amount: this.totals.grandTotal,
                        promo_code: this.promoCode
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Avtomatik Çap Pəncərəsini Aç
                    const printUrl = '/sales/' + result.order_id + '/print-official';
                    window.open(printUrl, 'ReceiptPrint', 'width=400,height=600,toolbar=0,scrollbars=0,status=0');

                    // Təmizlik işləri
                    this.cart = [];
                    this.products = [];
                    this.searchQuery = '';
                    this.promoCode = '';
                    this.showCheckoutModal = false;

                    // Axtarışa fokuslan
                    this.$nextTick(() => {
                         if(this.$refs.searchInput) this.$refs.searchInput.focus();
                    });
                } else {
                    alert('Xəta: ' + result.message);
                }
            } catch (error) {
                alert('Sistem xətası baş verdi.');
                console.error(error);
            } finally {
                this.isProcessing = false;
            }
        },

        formatPrice(value) {
            return Number(value).toFixed(2) + ' ₼';
        }
     }">

    <!-- YENİ: Kassa Başlığı (Header) -->
    <div class="flex justify-between items-center bg-white p-3 rounded-xl border border-gray-200 shadow-sm shrink-0">
        <div class="flex items-center">
            <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 mr-3">
                <i class="fa-solid fa-cash-register text-xl"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-800 leading-tight">Satış Terminalı</h1>
                <p class="text-xs text-gray-500">Kassir: {{ Auth::user()->name ?? 'Admin' }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <!-- Geri Qaytarma Düyməsi -->
            <a href="{{ route('returns.index') }}" class="bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 px-4 py-2 rounded-lg text-sm font-bold transition flex items-center shadow-sm">
                <i class="fa-solid fa-rotate-left mr-2"></i> Geri Qaytarma
            </a>

            <a href="{{ route('dashboard') }}" class="bg-gray-100 text-gray-600 hover:bg-gray-200 border border-gray-200 px-4 py-2 rounded-lg text-sm font-medium transition">
                <i class="fa-solid fa-right-from-bracket mr-1"></i> Çıxış
            </a>
        </div>
    </div>

    <!-- ƏSAS HİSSƏ -->
    <div class="flex flex-1 gap-4 overflow-hidden">

        <!-- SOL TƏRƏF: Məhsul Axtarışı -->
        <div class="w-full md:w-2/3 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Axtarış Inputu -->
            <div class="p-4 border-b border-gray-200 bg-gray-50 flex gap-4">
                <div class="relative flex-1">
                    <i class="fa-solid fa-barcode absolute left-4 top-3.5 text-gray-400 text-lg"></i>
                    <input type="text" x-ref="searchInput" x-model="searchQuery" @keyup.debounce.300ms="searchProducts()"
                        class="w-full pl-12 pr-4 py-3 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm text-lg"
                        placeholder="Barkod oxudun və ya məhsul adı yazın..." autofocus>
                </div>
            </div>

            <!-- Məhsul Siyahısı -->
            <div class="flex-1 overflow-y-auto p-4 bg-gray-100">
                <div x-show="isLoading" class="flex justify-center items-center h-full">
                    <i class="fa-solid fa-circle-notch fa-spin text-4xl text-blue-500"></i>
                </div>

                <div x-show="!isLoading && products.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <template x-for="product in products" :key="product.id">
                        <div @click="addToCart(product)" class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md cursor-pointer transition transform hover:-translate-y-1 overflow-hidden flex flex-col h-full">
                            <div class="h-32 bg-gray-200 flex items-center justify-center relative">
                                <template x-if="product.image">
                                    <img :src="product.image" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!product.image">
                                    <i class="fa-solid fa-box-open text-4xl text-gray-400"></i>
                                </template>
                                <div x-show="product.stock <= 0" class="absolute inset-0 bg-white/80 flex items-center justify-center">
                                    <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded">Stok Yoxdur</span>
                                </div>
                            </div>
                            <div class="p-3 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-800 line-clamp-2" x-text="product.name"></h3>
                                    <p class="text-xs text-gray-500 font-mono mt-1" x-text="product.barcode"></p>
                                </div>
                                <div class="mt-2 flex justify-between items-end">
                                    <div>
                                        <template x-if="product.discount_amount > 0">
                                            <div class="flex flex-col">
                                                <span class="text-xs text-gray-400 line-through" x-text="formatPrice(product.price)"></span>
                                                <span class="text-sm font-bold text-red-600" x-text="formatPrice(product.final_price)"></span>
                                            </div>
                                        </template>
                                        <template x-if="product.discount_amount <= 0">
                                            <span class="text-sm font-bold text-blue-600" x-text="formatPrice(product.price)"></span>
                                        </template>
                                    </div>
                                    <span class="text-xs font-medium bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded" x-text="product.stock + ' əd'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="!isLoading && products.length === 0 && searchQuery.length > 0" class="flex flex-col items-center justify-center h-64 text-gray-500">
                    <i class="fa-solid fa-magnifying-glass text-3xl mb-2"></i>
                    <p>Məhsul tapılmadı.</p>
                </div>
                <div x-show="!isLoading && products.length === 0 && searchQuery.length === 0" class="flex flex-col items-center justify-center h-64 text-gray-400">
                    <i class="fa-solid fa-barcode text-4xl mb-3"></i>
                    <p>Satışa başlamaq üçün məhsul axtarın</p>
                </div>
            </div>
        </div>

        <!-- SAĞ TƏRƏF: Səbət -->
        <div class="w-full md:w-1/3 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 h-full">
            <div class="p-4 border-b border-gray-200 bg-blue-600 text-white rounded-t-xl flex justify-between items-center">
                <h2 class="text-lg font-bold"><i class="fa-solid fa-cart-shopping mr-2"></i> Satış Səbəti</h2>
                <span class="bg-blue-700 px-2 py-1 rounded text-xs font-mono" x-text="cart.length + ' məhsul'"></span>
            </div>

            <div class="flex-1 overflow-y-auto p-2 space-y-2">
                <template x-for="(item, index) in cart" :key="item.id + (item.is_gift ? '_gift' : '')">
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 flex flex-col gap-2 group hover:bg-white hover:shadow-sm transition"
                         :class="item.is_gift ? 'border-purple-300 bg-purple-50' : ''">

                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-gray-800" x-text="item.name"></h4>
                                <div class="text-xs text-gray-500 mt-1 flex gap-2 items-center">
                                    <template x-if="!item.is_gift">
                                        <span x-text="formatPrice(item.final_price) + ' x ' + item.qty"></span>
                                    </template>
                                    <template x-if="item.is_gift">
                                        <span class="text-purple-600 font-bold uppercase text-[10px] border border-purple-200 px-1 rounded bg-white">HƏDİYYƏ</span>
                                    </template>
                                </div>
                            </div>

                            <!-- Hədiyyə Düyməsi -->
                            <button @click="toggleGift(index)"
                                    class="text-xs px-2 py-1 rounded border transition mr-2"
                                    :class="item.is_gift ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-gray-500 border-gray-300 hover:bg-purple-50 hover:text-purple-600'"
                                    title="Hədiyyə kimi işarələ">
                                <i class="fa-solid fa-gift"></i>
                            </button>

                            <div class="text-right w-20">
                                <p class="text-sm font-bold"
                                   :class="item.is_gift ? 'text-purple-600' : 'text-gray-800'"
                                   x-text="item.is_gift ? '0.00 ₼' : formatPrice(item.final_price * item.qty)"></p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center bg-white border border-gray-300 rounded-lg">
                                <button @click="updateQty(index, -1)" class="w-7 h-7 flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-l-lg">-</button>
                                <span class="w-8 text-center text-sm font-bold text-gray-800" x-text="item.qty"></span>
                                <button @click="updateQty(index, 1)" class="w-7 h-7 flex items-center justify-center text-blue-600 hover:bg-blue-50 rounded-r-lg">+</button>
                            </div>
                            <button @click="removeFromCart(index)" class="text-red-400 hover:text-red-600">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="cart.length === 0">
                    <div class="flex flex-col items-center justify-center h-40 text-gray-400">
                        <i class="fa-solid fa-basket-shopping text-3xl mb-2"></i>
                        <p class="text-sm">Səbət boşdur</p>
                    </div>
                </template>
            </div>

            <!-- Hesablama -->
            <div class="p-4 bg-gray-50 border-t border-gray-200 space-y-2">

                <!-- Promokod -->
                <div class="flex gap-2 mb-3">
                    <input type="text" x-model="promoCode" placeholder="Promokod daxil et" class="flex-1 rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <button class="bg-gray-200 text-gray-700 px-3 py-1 rounded-lg text-sm hover:bg-gray-300 transition">
                        <i class="fa-solid fa-check"></i>
                    </button>
                </div>

                <div class="flex justify-between text-sm text-gray-600">
                    <span>Ara Cəm:</span>
                    <span class="font-medium" x-text="formatPrice(totals.subtotal)"></span>
                </div>

                <div class="flex justify-between text-sm text-red-500 font-bold bg-red-50 p-2 rounded">
                    <span>Ümumi Endirim:</span>
                    <span>-<span x-text="formatPrice(totals.discount)"></span></span>
                </div>

                <div class="flex justify-between items-center pt-2 border-t border-gray-300 mt-2">
                    <span class="text-lg font-bold text-gray-800">YEKUN:</span>
                    <span class="text-2xl font-extrabold text-blue-700" x-text="formatPrice(totals.grandTotal)"></span>
                </div>
            </div>

            <!-- Ödəniş Düyməsi -->
            <div class="p-4 bg-white border-t border-gray-200">
                <button @click="openCheckout()" :disabled="cart.length === 0"
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-4 rounded-xl font-bold shadow-md hover:shadow-lg transition flex items-center justify-center text-lg disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-check-double mr-2"></i>
                    Satışı Təsdiqlə
                </button>
            </div>
        </div>
    </div>

    <!-- POP-UP MODAL (Ödəniş Təsdiqi) -->
    <div x-show="showCheckoutModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCheckoutModal = false"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                            <i class="fa-solid fa-coins text-green-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Ödəniş Təsdiqi</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Ödəniləcək Yekun Məbləğ:</p>
                            <p class="text-3xl font-bold text-blue-700 mt-2" x-text="formatPrice(totals.grandTotal)"></p>
                        </div>
                    </div>

                    <!-- Ödəniş Növləri -->
                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <button @click="processPayment('cash')" :disabled="isProcessing" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg font-bold shadow transition flex flex-col items-center justify-center disabled:opacity-50">
                            <i class="fa-solid fa-money-bill-wave text-2xl mb-1"></i>
                            NƏĞD
                        </button>
                        <button @click="processPayment('card')" :disabled="isProcessing" class="w-full bg-purple-600 hover:bg-purple-700 text-white py-3 rounded-lg font-bold shadow transition flex flex-col items-center justify-center disabled:opacity-50">
                            <i class="fa-regular fa-credit-card text-2xl mb-1"></i>
                            KART
                        </button>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="showCheckoutModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Ləğv et
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
