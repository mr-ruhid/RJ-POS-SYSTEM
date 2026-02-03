const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

// Limitləri artırırıq
app.use(cors());
app.use(bodyParser.json({ limit: '50mb' }));
app.use(bodyParser.urlencoded({ limit: '50mb', extended: true }));

let currentData = null;

// ---------------------------------------------------------
// DASHBOARD HTML (Genişləndirilmiş Sidebar ilə)
// ---------------------------------------------------------
const dashboardHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RJ POS - İdarəetmə Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/socket.io/socket.io.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #0f172a; color: #e2e8f0; font-family: 'Inter', sans-serif; overflow: hidden; }
        
        /* Sidebar Dizaynı */
        .sidebar { width: 260px; background: #1e293b; height: 100vh; position: fixed; left: 0; top: 0; border-right: 1px solid #334155; display: flex; flex-direction: column; }
        .logo-area { height: 70px; display: flex; align-items: center; padding-left: 24px; border-bottom: 1px solid #334155; }
        .nav-item { display: flex; align-items: center; padding: 14px 24px; color: #94a3b8; cursor: pointer; transition: all 0.3s; border-left: 4px solid transparent; }
        .nav-item:hover { background: #2a384b; color: #fff; }
        .nav-item.active { background: #334155; color: #fff; border-left-color: #3b82f6; }
        .nav-item i { width: 24px; margin-right: 10px; font-size: 1.1rem; }
        
        /* Əsas Məzmun */
        .main-content { margin-left: 260px; padding: 30px; height: 100vh; overflow-y: auto; }
        
        /* Kartlar və Cədvəllər */
        .card { background: #1e293b; border-radius: 12px; border: 1px solid #334155; padding: 20px; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th { text-align: left; padding: 16px; background: #253042; color: #94a3b8; font-size: 0.85rem; font-weight: 600; position: sticky; top: 0; }
        td { padding: 16px; border-bottom: 1px solid #334155; color: #fff; font-size: 0.95rem; }
        tr:hover td { background: #2a384b; }
        
        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        
        .hidden-page { display: none; }
        .animate-enter { animation: enterPage 0.4s ease-out; }
        @keyframes enterPage { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo-area">
            <h1 class="text-2xl font-bold text-white flex items-center">
                <i class="fa-solid fa-layer-group text-blue-500 mr-2"></i> RJ POS
            </h1>
        </div>
        
        <div class="flex-1 py-6 space-y-1 overflow-y-auto">
            <div class="nav-item active" onclick="switchPage('dashboard', this)">
                <i class="fa-solid fa-chart-pie"></i> İcmal
            </div>
            <div class="nav-item" onclick="switchPage('products', this)">
                <i class="fa-solid fa-boxes-stacked"></i> Məhsullar
            </div>
            <div class="nav-item" onclick="switchPage('warehouse', this)">
                <i class="fa-solid fa-warehouse"></i> Anbar & Stok
            </div>
            <div class="nav-item" onclick="switchPage('lottery', this)">
                <i class="fa-solid fa-ticket"></i> Lotereya
            </div>
            <div class="nav-item" onclick="switchPage('promocodes', this)">
                <i class="fa-solid fa-tags"></i> Promokodlar
            </div>
        </div>

        <div class="p-6 border-t border-slate-700">
            <div id="status-badge" class="flex items-center space-x-2 text-sm text-gray-400 bg-slate-800 p-2 rounded-lg justify-center">
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                <span>Offline</span>
            </div>
            <div class="text-xs text-center text-gray-500 mt-2" id="last-sync-time">Yenilənməyib</div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <!-- 1. DASHBOARD (İCMAL) -->
        <div id="page-dashboard" class="animate-enter">
            <h2 class="text-3xl font-bold text-white mb-8">Bu Günün Statistikası</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Satış -->
                <div class="card border-l-4 border-blue-500">
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Günlük Satış</p>
                    <h3 class="text-3xl font-bold text-white mt-1" id="d-sales">0.00 ₼</h3>
                    <p class="text-sm text-blue-400 mt-2 flex items-center"><i class="fa-solid fa-receipt mr-1"></i> <span id="d-count">0</span> çek</p>
                </div>
                <!-- Mənfəət -->
                <div class="card border-l-4 border-green-500">
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Xalis Mənfəət</p>
                    <h3 class="text-3xl font-bold text-green-400 mt-1" id="d-profit">0.00 ₼</h3>
                    <p class="text-sm text-gray-500 mt-2">Təxmini gəlir</p>
                </div>
                <!-- Anbar -->
                <div class="card border-l-4 border-orange-500">
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Anbar Dəyəri</p>
                    <h3 class="text-2xl font-bold text-white mt-1" id="d-stock-val">0.00 ₼</h3>
                    <p class="text-sm text-orange-400 mt-2">Maya dəyəri</p>
                </div>
                <!-- Partnyor -->
                <div class="card border-l-4 border-purple-500">
                    <p class="text-gray-400 text-xs uppercase tracking-wider">Partnyorlar</p>
                    <h3 class="text-3xl font-bold text-white mt-1" id="d-partners">0</h3>
                    <p class="text-sm text-purple-400 mt-2">Aktiv</p>
                </div>
            </div>

            <!-- Son Satışlar Cədvəli -->
            <div class="card">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-white">Son Əməliyyatlar</h3>
                    <span class="bg-blue-900 text-blue-300 text-xs px-2 py-1 rounded">Canlı Axın</span>
                </div>
                <table class="w-full">
                    <thead>
                        <tr>
                            <th>Saat</th>
                            <th>Qəbz №</th>
                            <th>Ödəniş</th>
                            <th class="text-center">Məhsul</th>
                            <th class="text-right">Məbləğ</th>
                        </tr>
                    </thead>
                    <tbody id="table-recent-orders">
                        <tr><td colspan="5" class="text-center py-8 text-gray-500">Məlumat gözlənilir...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. MƏHSULLAR -->
        <div id="page-products" class="hidden-page animate-enter">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-white">Məhsul Bazası</h2>
                <input type="text" onkeyup="filterTable('tbody-products', this.value)" placeholder="Məhsul axtar..." class="bg-slate-800 border border-slate-600 text-white px-4 py-2 rounded-lg w-64 focus:outline-none focus:border-blue-500">
            </div>
            <div class="card overflow-hidden">
                <div class="overflow-y-auto max-h-[700px]">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th>Məhsul Adı</th>
                                <th>Barkod</th>
                                <th class="text-center">Stok</th>
                                <th class="text-right">Maya</th>
                                <th class="text-right">Satış</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-products">
                            <tr><td colspan="6" class="text-center py-8 text-gray-500">Yüklənir...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. ANBAR (WAREHOUSE) -->
        <div id="page-warehouse" class="hidden-page animate-enter">
            <h2 class="text-3xl font-bold text-white mb-8">Anbar Hesabatı</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="card bg-slate-800/50 border-orange-500/30 border">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-400">Ümumi Maya Dəyəri</p>
                            <h3 class="text-4xl font-bold text-white mt-2" id="w-cost">0.00 ₼</h3>
                        </div>
                        <i class="fa-solid fa-boxes-packing text-5xl text-orange-500/20"></i>
                    </div>
                </div>
                <div class="card bg-slate-800/50 border-green-500/30 border">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-400">Potensial Satış Dəyəri</p>
                            <h3 class="text-4xl font-bold text-white mt-2" id="w-sale">0.00 ₼</h3>
                        </div>
                        <i class="fa-solid fa-chart-line text-5xl text-green-500/20"></i>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 class="text-xl font-bold text-white mb-4">Kritik Stokda Olanlar</h3>
                <table class="w-full">
                    <thead>
                        <tr>
                            <th>Məhsul</th>
                            <th class="text-center">Qalıq</th>
                            <th class="text-center">Limit</th>
                            <th class="text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-critical">
                        <!-- JS ilə dolacaq -->
                        <tr><td colspan="4" class="text-center py-4 text-gray-500">Kritik məhsul yoxdur (və ya yüklənməyib)</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. LOTEREYA -->
        <div id="page-lottery" class="hidden-page animate-enter">
            <h2 class="text-3xl font-bold text-white mb-6">Lotereya Kodları</h2>
            <div class="card">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th>Tarix</th>
                            <th>Qəbz №</th>
                            <th>Lotereya Kodu</th>
                            <th class="text-right">Məbləğ</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-lottery">
                        <tr><td colspan="4" class="text-center py-8 text-gray-500">Məlumat yoxdur</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. PROMOKODLAR -->
        <div id="page-promocodes" class="hidden-page animate-enter">
            <h2 class="text-3xl font-bold text-white mb-6">Promokodlar</h2>
            <div class="card">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th>Kod</th>
                            <th>Endirim</th>
                            <th class="text-center">İstifadə Sayı</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-promos">
                        <tr><td colspan="4" class="text-center py-8 text-gray-500">Məlumat yoxdur</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        const socket = io();
        let globalData = null;

        // Naviqasiya
        function switchPage(pageId, element) {
            document.querySelectorAll('[id^="page-"]').forEach(el => el.classList.add('hidden-page'));
            document.getElementById('page-' + pageId).classList.remove('hidden-page');
            
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        // Socket Events
        socket.on('connect', () => {
            const badge = document.getElementById('status-badge');
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-500"></span><span class="text-green-400">Online</span>';
            socket.emit('request_last_data');
        });

        socket.on('disconnect', () => {
            const badge = document.getElementById('status-badge');
            badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-red-500"></span><span class="text-red-400">Offline</span>';
        });

        socket.on('live_update', (data) => {
            if (data.type === 'full_report') {
                renderAll(data.payload);
            }
        });

        function renderAll(payload) {
            globalData = payload;
            const s = payload.stats;

            document.getElementById('last-sync-time').innerText = 'Son: ' + new Date().toLocaleTimeString();

            // 1. DASHBOARD
            setVal('d-sales', formatMoney(s.today_sales));
            setVal('d-count', s.today_count);
            setVal('d-profit', formatMoney(s.today_profit));
            setVal('d-stock-val', formatMoney(s.warehouse_cost));
            setVal('d-partners', s.partner_count);

            // Son Satışlar Cədvəli
            if (payload.latest_orders) {
                const tbody = document.getElementById('table-recent-orders');
                if (payload.latest_orders.length > 0) tbody.innerHTML = '';
                
                payload.latest_orders.forEach(o => {
                    tbody.innerHTML += \`
                        <tr>
                            <td class="font-mono text-gray-400">\${o.time}</td>
                            <td class="font-bold text-white">#\${o.receipt_code || '---'}</td>
                            <td class="text-center text-sm">\${o.payment_method === 'card' ? '<span class="text-blue-400">KART</span>' : '<span class="text-green-400">NAĞD</span>'}</td>
                            <td class="text-center text-gray-400">\${o.items_count}</td>
                            <td class="text-right font-bold text-green-400">+\${formatMoney(o.grand_total)}</td>
                        </tr>
                    \`;
                });
            }

            // 2. MƏHSULLAR
            if (payload.products) {
                const tbody = document.getElementById('tbody-products');
                if(payload.products.length > 0) tbody.innerHTML = '';
                
                // Kritik stok siyahısı üçün də hazırlıq
                const criticalBody = document.getElementById('tbody-critical');
                criticalBody.innerHTML = '';
                let hasCritical = false;

                payload.products.forEach(p => {
                    // Əsas Məhsul Cədvəli
                    tbody.innerHTML += \`
                        <tr>
                            <td class="font-medium text-white">\${p.name}</td>
                            <td class="font-mono text-gray-400">\${p.barcode}</td>
                            <td class="text-center font-bold \${p.quantity < 5 ? 'text-red-500' : 'text-blue-400'}">\${p.quantity}</td>
                            <td class="text-right text-gray-500">\${formatMoney(p.cost_price)}</td>
                            <td class="text-right font-bold text-white">\${formatMoney(p.selling_price)}</td>
                            <td class="text-center">\${p.is_active ? '<span class="text-green-500 text-xs">●</span>' : '<span class="text-red-500 text-xs">●</span>'}</td>
                        </tr>
                    \`;

                    // Kritik Stok Cədvəli (Anbar səhifəsi üçün)
                    if(p.quantity <= 5) { // Məsələn 5-dən az
                        hasCritical = true;
                        criticalBody.innerHTML += \`
                            <tr>
                                <td class="text-white">\${p.name}</td>
                                <td class="text-center text-red-500 font-bold">\${p.quantity}</td>
                                <td class="text-center text-gray-500">5</td>
                                <td class="text-right text-red-400 text-xs">BİTİR</td>
                            </tr>
                        \`;
                    }
                });
                
                if(!hasCritical) criticalBody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-green-500">Hər şey qaydasındadır</td></tr>';
            }

            // 3. ANBAR
            setVal('w-cost', formatMoney(s.warehouse_cost));
            setVal('w-sale', formatMoney(s.warehouse_sale));

            // 4. LOTEREYA (Son satışlardan çıxarırıq)
            if (payload.latest_orders) {
                const tbody = document.getElementById('tbody-lottery');
                // Sadece lotereya kodu olanları süzürük (Localda 'lottery_code' göndərilməlidir)
                // Hazırda demo kimi son satışları göstərirəm, əgər 'lottery_code' gəlsə bura əlavə edəcəyik
                const lotteryOrders = payload.latest_orders.filter(o => o.receipt_code); // Şərti
                
                if(lotteryOrders.length > 0) tbody.innerHTML = '';
                lotteryOrders.forEach(o => {
                    // Demo: Qəbz nömrəsini lotereya kimi göstərirəm, realda 'lottery_code' olacaq
                    tbody.innerHTML += \`
                        <tr>
                            <td class="text-gray-400">\${o.time}</td>
                            <td class="text-white">#\${o.receipt_code}</td>
                            <td class="text-yellow-400 font-mono font-bold">\${o.receipt_code}99</td> 
                            <td class="text-right text-green-400">\${formatMoney(o.grand_total)}</td>
                        </tr>
                    \`;
                });
            }

            // 5. PROMOKODLAR
            if (payload.promocodes) {
                const tbody = document.getElementById('tbody-promos');
                if (payload.promocodes.length > 0) tbody.innerHTML = '';
                
                payload.promocodes.forEach(pr => {
                    tbody.innerHTML += \`
                        <tr>
                            <td class="font-bold font-mono text-purple-400">\${pr.code}</td>
                            <td>\${pr.discount_type === 'percent' ? pr.discount_value + '%' : pr.discount_value + ' AZN'}</td>
                            <td class="text-center text-white">\${pr.orders_count}</td>
                            <td class="text-center text-green-500">Aktiv</td>
                        </tr>
                    \`;
                });
            }
        }

        function filterTable(tbodyId, text) {
            const filter = text.toUpperCase();
            const rows = document.getElementById(tbodyId).getElementsByTagName("tr");
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName("td");
                let match = false;
                if (cells.length > 0) {
                    if (cells[0].innerText.toUpperCase().indexOf(filter) > -1 || 
                        cells[1].innerText.toUpperCase().indexOf(filter) > -1) {
                        match = true;
                    }
                }
                rows[i].style.display = match ? "" : "none";
            }
        }

        function setVal(id, val) {
            const el = document.getElementById(id);
            if(el) el.innerText = val;
        }
        function formatMoney(amount) {
            return parseFloat(amount || 0).toFixed(2) + ' ₼';
        }
    </script>
</body>
</html>
`;

app.get('/', (req, res) => res.send(dashboardHTML));

app.post('/api/report', (req, res) => {
    try {
        const data = req.body;
        console.log(`📡 Data: ${data.type} [${new Date().toLocaleTimeString()}]`);
        
        // Yaddaşda saxla
        currentData = data;
        
        io.emit('live_update', data);
        res.json({ status: true });
    } catch (e) {
        res.status(500).json({ status: false, error: e.message });
    }
});

io.on('connection', (socket) => {
    if(currentData) socket.emit('live_update', currentData);
});

const PORT = 3000;
server.listen(PORT, () => {
    console.log(`Monitor İşləyir: http://localhost:${PORT}`);
});