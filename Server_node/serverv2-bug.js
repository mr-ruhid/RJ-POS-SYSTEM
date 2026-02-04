require('dotenv').config();

const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const bodyParser = require('body-parser');
const cors = require('cors');
const session = require('express-session');
const path = require('path');

// --- TƏNZİMLƏMƏLƏR ---
const ADMIN_USER = process.env.ADMIN_USER || "admin";
const ADMIN_PASS = process.env.ADMIN_PASS || "admin123";

const app = express();
const server = http.createServer(app);

// SERVER TƏRƏFİ SOCKET AYARLARI
const io = new Server(server, { 
    cors: { origin: "*" },
    path: '/socket.io'
});

app.set('trust proxy', 1);
app.use(cors());
app.use(bodyParser.json({ limit: '50mb' }));
app.use(bodyParser.urlencoded({ limit: '50mb', extended: true }));

app.use(session({
    secret: process.env.SESSION_SECRET || 'gizli_açar_rj_pos_secure',
    resave: false,
    saveUninitialized: true,
    cookie: { secure: false, maxAge: 24 * 60 * 60 * 1000 }
}));

let currentPayload = null;

// ==========================================
// 1. GİRİŞ SƏHİFƏSİ (GLASSMORPHISM DIZAYN)
// ==========================================
const loginHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - RJ POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Geist', system-ui, -apple-system, sans-serif; }
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            animation: gradientShift 10s ease infinite;
            background-size: 200% 200%;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .input-field:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center">
    <form action="login" method="POST" class="glass-card p-10 rounded-3xl w-[420px] shadow-2xl">
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-4 bg-white/10 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-white text-3xl"></i>
            </div>
            <h1 class="text-white text-3xl font-bold">RJ POS Monitor</h1>
            <p class="text-white/70 text-sm mt-2">Admin Panelinə Xoş Gəlmisiniz</p>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="text-white/80 text-sm font-medium mb-2 block">İstifadəçi Adı</label>
                <input name="username" placeholder="admin" 
                    class="input-field w-full p-4 rounded-xl text-white placeholder-white/40 outline-none">
            </div>
            
            <div>
                <label class="text-white/80 text-sm font-medium mb-2 block">Şifrə</label>
                <input type="password" name="password" placeholder="••••••••" 
                    class="input-field w-full p-4 rounded-xl text-white placeholder-white/40 outline-none">
            </div>
        </div>
        
        <button class="btn-login w-full text-white font-bold py-4 rounded-xl mt-6">
            <i class="fas fa-sign-in-alt mr-2"></i> Daxil Ol
        </button>
        
        <p class="text-center text-white/50 text-xs mt-6">
            <i class="fas fa-lock mr-1"></i> Təhlükəsiz bağlantı
        </p>
    </form>
</body>
</html>
`;

// ==========================================
// 2. DASHBOARD SƏHİFƏSİ (FULL DİZAYN + FIX)
// ==========================================
const dashboardHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>Monitor - RJ POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Geist', system-ui, -apple-system, sans-serif; }
        
        :root {
            --bg-primary: #0a0e1a;
            --bg-secondary: #121829;
            --bg-tertiary: #1a2035;
            --bg-hover: #242d45;
            --border-color: #2a3550;
            --text-primary: #ffffff;
            --text-secondary: #9ca3af;
            --accent-blue: #3b82f6;
            --accent-purple: #8b5cf6;
            --accent-green: #10b981;
            --accent-orange: #f59e0b;
            --accent-red: #ef4444;
        }
        
        body { 
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
        }
        
        /* SIDEBAR */
        .sidebar { 
            width: 280px;
            background: var(--bg-secondary);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1px solid var(--border-color);
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            height: 80px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 24px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
        }
        
        .nav-link { 
            display: flex;
            align-items: center;
            padding: 14px 20px;
            margin: 4px 12px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
            transform: translateX(4px);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            color: var(--text-primary);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .content { 
            margin-left: 280px;
            padding: 32px;
            min-height: 100vh;
        }
        
        .stat-card { 
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .data-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
            background: var(--bg-tertiary);
        }
        
        .data-table th { 
            text-align: left;
            padding: 16px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            color: var(--text-secondary);
            border-bottom: 2px solid var(--border-color);
        }
        
        .data-table td { 
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .data-table tbody tr:hover { 
            background: var(--bg-hover);
        }
        
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-secondary); }
        ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
        
        .hidden-page { display: none; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; animation: pulse 2s infinite; }
        .status-online { background: var(--accent-green); }
        .status-offline { background: var(--accent-red); }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

        /* Button Style */
        .btn-export {
            background: var(--accent-green);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-export:hover { filter: brightness(1.1); transform: translateY(-1px); }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-chart-line text-white text-2xl mr-3"></i>
            <h1 class="text-xl font-bold text-white">RJ POS Monitor</h1>
        </div>
        
        <div class="flex-1 py-6">
            <div class="nav-link active" onclick="switchPage('dashboard', this)">
                <i class="fa-solid fa-gauge-high mr-3"></i> <span>İcmal</span>
            </div>
            <div class="nav-link" onclick="switchPage('sales', this)">
                <i class="fa-solid fa-receipt mr-3"></i> <span>Satış Tarixçəsi</span>
            </div>
            <div class="nav-link" onclick="switchPage('products', this)">
                <i class="fa-solid fa-box-open mr-3"></i> <span>Məhsullar</span>
            </div>
            <div class="nav-link" onclick="switchPage('partners', this)">
                <i class="fa-solid fa-users mr-3"></i> <span>Partnyorlar</span>
            </div>
            <div class="nav-link" onclick="switchPage('warehouse', this)">
                <i class="fa-solid fa-warehouse mr-3"></i> <span>Anbar</span>
            </div>
            <div class="nav-link" onclick="switchPage('lottery', this)">
                <i class="fa-solid fa-ticket mr-3"></i> <span>Lotereya</span>
            </div>
            <div class="nav-link" onclick="switchPage('promocodes', this)">
                <i class="fa-solid fa-tags mr-3"></i> <span>Promokodlar</span>
            </div>
        </div>
        
        <div class="p-4 border-t border-gray-700">
            <div id="status" class="text-center text-sm font-semibold mb-3 text-red-500">
                <span class="status-dot status-offline"></span> Offline
            </div>
            <a href="logout" class="block w-full text-center py-2 bg-gray-800 text-gray-400 rounded-lg hover:bg-red-500 hover:text-white transition">
                <i class="fas fa-sign-out-alt mr-2"></i> Çıxış
            </a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">
        
        <!-- 1. DASHBOARD -->
        <div id="page-dashboard">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-white">Canlı Monitorinq</h2>
                    <p class="text-gray-400 mt-1">Son yenilənmə: <span id="update-time">-</span></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Satış -->
                <div class="stat-card border-l-4 border-blue-500">
                    <p class="text-gray-400 text-sm uppercase font-bold">Xalis Satış</p>
                    <h3 class="text-4xl font-bold text-white mt-2" id="stat-sales">0.00 ₼</h3>
                    <!-- QAYTARMA GÖSTƏRİCİSİ -->
                    <p class="text-xs text-red-400 mt-2 font-bold" id="stat-refunds" style="display:none">Qaytarma: -0.00 ₼</p>
                </div>

                <!-- Mənfəət -->
                <div class="stat-card border-l-4 border-green-500">
                    <p class="text-gray-400 text-sm uppercase font-bold">Xalis Mənfəət</p>
                    <h3 class="text-4xl font-bold text-green-400 mt-2" id="stat-profit">0.00 ₼</h3>
                    <!-- KOMİSSİYA GÖSTƏRİCİSİ -->
                    <p class="text-xs text-yellow-400 mt-2 font-bold" id="stat-partners-cut" style="display:none">Komissiya: -0.00 ₼</p>
                </div>

                <!-- Anbar -->
                <div class="stat-card border-l-4 border-orange-500">
                    <p class="text-gray-400 text-sm uppercase font-bold">Anbar Dəyəri</p>
                    <h3 class="text-3xl font-bold text-white mt-2" id="stat-stock-val">0.00 ₼</h3>
                    <p class="text-xs text-orange-400 mt-2">Maya Dəyəri</p>
                </div>

                <!-- Partnyorlar -->
                <div class="stat-card border-l-4 border-purple-500">
                    <p class="text-gray-400 text-sm uppercase font-bold">Partnyorlar</p>
                    <h3 class="text-4xl font-bold text-white mt-2" id="stat-partners">0</h3>
                    <p class="text-xs text-purple-300 mt-2">Aktiv</p>
                </div>
            </div>

            <!-- Son Satışlar -->
            <div class="stat-card">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold text-white">Son Əməliyyatlar</h3>
                    <span class="text-xs bg-blue-900 text-blue-300 px-2 py-1 rounded border border-blue-700 animate-pulse">Canlı Axın</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead><tr><th>Saat</th><th>Qəbz №</th><th>Ödəniş</th><th>Qazanc</th><th class="text-right">Məbləğ</th></tr></thead>
                        <tbody id="table-orders-short"><tr><td colspan="5" class="text-center py-8 text-gray-500">Məlumat yoxdur</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. SATIŞ TARİXÇƏSİ (FULL) -->
        <div id="page-sales" class="hidden-page">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-white">Satış Tarixçəsi</h2>
                <div class="flex gap-3">
                    <input type="text" id="filter-sales" onkeyup="filterSalesTable()" placeholder="Axtar..." class="input-field bg-slate-800 border border-slate-600 text-white px-4 py-2 rounded-lg">
                    <button onclick="exportToExcel('table-orders-full', 'Satislar')" class="btn-export"><i class="fas fa-file-excel"></i> Export</button>
                </div>
            </div>
            <div class="stat-card overflow-hidden">
                <div class="overflow-y-auto max-h-[700px]">
                    <table class="data-table" id="table-orders-full">
                        <thead><tr><th>Saat</th><th>Qəbz №</th><th>Ödəniş</th><th>Partnyor</th><th>Komissiya</th><th>Lotereya</th><th class="text-right">Məbləğ</th></tr></thead>
                        <tbody id="tbody-sales-full"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 3. MƏHSULLAR -->
        <div id="page-products" class="hidden-page">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-white">Məhsullar</h2>
                <div class="flex gap-3">
                    <input type="text" id="filter-products" onkeyup="filterTable('table-products', this.value)" placeholder="Məhsul adı..." class="input-field bg-slate-800 border border-slate-600 text-white px-4 py-2 rounded-lg">
                    <button onclick="exportToExcel('table-products', 'Mehsullar')" class="btn-export"><i class="fas fa-file-excel"></i> Export</button>
                </div>
            </div>
            <div class="stat-card overflow-auto max-h-[700px]">
                <table class="data-table" id="table-products">
                    <thead><tr><th>Ad</th><th>Barkod</th><th class="text-center">Stok</th><th class="text-right">Qiymət</th><th class="text-center">Status</th></tr></thead>
                    <tbody id="tbody-products"></tbody>
                </table>
            </div>
        </div>
        
        <!-- 4. PARTNYORLAR -->
        <div id="page-partners" class="hidden-page">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-white">Partnyorlar</h2>
                <button onclick="exportToExcel('table-partners', 'Partnyorlar')" class="btn-export"><i class="fas fa-file-excel"></i> Export</button>
            </div>
            <div class="stat-card">
                <table class="data-table" id="table-partners">
                    <thead><tr><th>Ad</th><th>Telefon</th><th>Telegram ID</th><th class="text-right">Balans</th></tr></thead>
                    <tbody id="tbody-partners"></tbody>
                </table>
            </div>
        </div>

        <!-- 5. ANBAR -->
        <div id="page-warehouse" class="hidden-page">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-white">Anbar & Partiyalar</h2>
                <button onclick="exportToExcel('table-warehouse', 'Anbar')" class="btn-export"><i class="fas fa-file-excel"></i> Export</button>
            </div>
            <div class="stat-card overflow-auto max-h-[700px]">
                <table class="data-table" id="table-warehouse">
                    <thead><tr><th>Məhsul</th><th>Kod</th><th class="text-center">Say</th><th class="text-right">Maya</th><th>Tarix</th></tr></thead>
                    <tbody id="tbody-batches"></tbody>
                </table>
            </div>
        </div>

        <!-- 6. LOTEREYA -->
        <div id="page-lottery" class="hidden-page">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-white">Lotereya Kodları</h2>
                <button onclick="exportToExcel('table-lottery', 'Lotereya')" class="btn-export"><i class="fas fa-file-excel"></i> Export</button>
            </div>
            <div class="stat-card">
                <table class="data-table" id="table-lottery">
                    <thead><tr><th>Qəbz</th><th>Tarix</th><th>Lotereya Kodu</th><th class="text-right">Məbləğ</th></tr></thead>
                    <tbody id="tbody-lottery"></tbody>
                </table>
            </div>
        </div>

        <!-- 7. PROMOKODLAR -->
        <div id="page-promocodes" class="hidden-page">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-white">Promokodlar</h2>
                <button onclick="exportToExcel('table-promocodes', 'Promokodlar')" class="btn-export"><i class="fas fa-file-excel"></i> Export</button>
            </div>
            <div class="stat-card">
                <table class="data-table" id="table-promocodes">
                    <thead><tr><th>Kod</th><th>Endirim</th><th class="text-center">İstifadə</th><th class="text-center">Status</th></tr></thead>
                    <tbody id="tbody-promos"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const socket = io({ 
            path: '/monitor/socket.io',
            transports: ['polling', 'websocket'], 
            reconnection: true
        });
        
        let currentPayload = null;

        function switchPage(id, el) {
            document.querySelectorAll('[id^="page-"]').forEach(el => el.classList.add('hidden-page'));
            document.getElementById('page-' + id).classList.remove('hidden-page');
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            el.classList.add('active');
        }

        function exportToExcel(tableId, name) {
            const table = document.getElementById(tableId);
            const wb = XLSX.utils.table_to_book(table, {sheet: "Sheet 1"});
            XLSX.writeFile(wb, name + "_" + new Date().toISOString().slice(0,10) + ".xlsx");
        }
        
        function filterSalesTable() {
            const input = document.getElementById('filter-sales');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('table-orders-full');
            const tr = table.getElementsByTagName('tr');
            for (let i = 0; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[1]; // Qəbz No
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    tr[i].style.display = txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
                }
            }
        }
        
        function filterTable(id, val) {
             const table = document.getElementById(id);
             const tr = table.getElementsByTagName('tr');
             const filter = val.toUpperCase();
             for (let i = 0; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td')[0];
                if (td) {
                    const txt = td.textContent || td.innerText;
                    tr[i].style.display = txt.toUpperCase().indexOf(filter) > -1 ? "" : "none";
                }
             }
        }

        socket.on('connect', () => { 
            const status = document.getElementById('status');
            status.innerHTML = '<span class="status-dot status-online"></span>Online';
            status.className = 'text-center text-sm font-semibold mb-3 text-green-500';
            socket.emit('request_last_data');
        });
        
        socket.on('disconnect', () => { 
            const status = document.getElementById('status');
            status.innerHTML = '<span class="status-dot status-offline"></span>Offline';
            status.className = 'text-center text-sm font-semibold mb-3 text-red-500';
        });

        socket.on('live_update', (data) => {
            if (data.type === 'full_report') {
                try {
                    renderData(data.payload);
                    document.getElementById('update-time').innerText = data.time || new Date().toLocaleTimeString();
                } catch(e) { console.error(e); }
            }
        });

        function renderData(p) {
            const s = p.stats || {};
            
            // --- HESABLAMALAR (JS TƏRƏFİNDƏN DƏQİQLƏŞDİRMƏ) ---
            let totalCommission = 0;
            let totalRefunds = 0;

            if (p.latest_orders && Array.isArray(p.latest_orders)) {
                totalCommission = p.latest_orders.reduce((sum, o) => sum + parseFloat(o.calculated_commission || 0), 0);
                totalRefunds = p.latest_orders.reduce((sum, o) => sum + parseFloat(o.refunded_amount || 0), 0);
            }

            // Mənfəətdən komissiyanı çıxırıq
            const netProfit = parseFloat(s.today_profit || 0) - totalCommission;

            // Rəqəmlər
            setText('stat-sales', formatMoney(s.today_sales));
            
            const refEl = document.getElementById('stat-refunds');
            if(totalRefunds > 0) {
                refEl.innerText = 'Qaytarma: -' + formatMoney(totalRefunds);
                refEl.style.display = 'block';
            } else {
                refEl.style.display = 'none';
            }

            setText('stat-profit', formatMoney(netProfit));
            
            const comEl = document.getElementById('stat-partners-cut');
            if(totalCommission > 0) {
                comEl.innerText = 'Komissiya: -' + formatMoney(totalCommission);
                comEl.style.display = 'block';
            } else {
                comEl.style.display = 'none';
            }

            setText('stat-stock-val', formatMoney(s.warehouse_cost));
            setText('stat-partners', s.partner_count || 0);

            // 1. SATIŞLAR (Qısa və Tam)
            if (p.latest_orders && Array.isArray(p.latest_orders)) {
                // Qısa cədvəl (Dashboard)
                document.getElementById('table-orders-short').innerHTML = p.latest_orders.slice(0, 10).map(o => renderOrderRow(o, 'short')).join('');
                
                // Tam cədvəl (Sales Page)
                document.getElementById('tbody-sales-full').innerHTML = p.latest_orders.map(o => renderOrderRow(o, 'full')).join('');
            }

            // 2. PARTNYORLAR
            if (p.partners && Array.isArray(p.partners)) {
                document.getElementById('tbody-partners').innerHTML = p.partners.map(x => \`<tr><td class="font-bold text-white">\${x.name}</td><td class="text-gray-400">\${x.phone || '-'}</td><td class="font-mono text-blue-300">\${x.telegram_chat_id || '-'}</td><td class="text-green-400 font-bold text-right">\${formatMoney(x.balance)}</td></tr>\`).join('');
            }
            
            // 3. MƏHSULLAR
            if (p.products && Array.isArray(p.products)) {
                document.getElementById('tbody-products').innerHTML = p.products.map(x => \`<tr><td class="text-white">\${x.name}</td><td class="text-gray-400 font-mono">\${x.barcode}</td><td class="text-center text-blue-400 font-bold">\${x.quantity}</td><td class="text-right text-gray-300">\${formatMoney(x.selling_price)}</td><td class="text-center">\${x.is_active ? '<span class="text-green-500 text-xs">●</span>' : '<span class="text-red-500 text-xs">●</span>'}</td></tr>\`).join('');
            }

            // 4. ANBAR
            const warehouseData = (p.batches && p.batches.length > 0) ? p.batches : p.products;
            if (warehouseData && Array.isArray(warehouseData)) {
                document.getElementById('tbody-batches').innerHTML = warehouseData.map(x => \`<tr><td class="text-white">\${x.product_name || x.name}</td><td class="text-yellow-500 font-mono">\${x.batch_code || x.barcode}</td><td class="text-center text-white">\${x.current_quantity || x.quantity}</td><td class="text-right text-gray-400">\${formatMoney(x.cost_price)}</td><td class="text-gray-500 text-xs">\${x.created_at || '-'}</td></tr>\`).join('');
            }

            // 5. LOTEREYA
            const lotteryData = p.lottery_orders || (p.latest_orders ? p.latest_orders.filter(o => o.lottery_code) : []);
            if (lotteryData.length > 0) {
                 document.getElementById('tbody-lottery').innerHTML = lotteryData.map(x => \`<tr><td class="text-white font-mono">#\${x.receipt_code}</td><td class="text-gray-400">\${x.time}</td><td class="text-yellow-400 font-bold font-mono text-lg">\${x.lottery_code}</td><td class="text-right text-green-400 font-bold">\${formatMoney(x.grand_total)}</td></tr>\`).join('');
            } else {
                 document.getElementById('tbody-lottery').innerHTML = '<tr><td colspan="4" class="text-center py-8 text-gray-500 italic">Lotereya satışı yoxdur</td></tr>';
            }

            // 6. PROMOKODLAR
            if (p.promocodes && Array.isArray(p.promocodes)) {
                document.getElementById('tbody-promos').innerHTML = p.promocodes.map(x => \`<tr><td class="text-purple-400 font-bold font-mono">\${x.code}</td><td class="text-white">\${x.discount_value}</td><td class="text-center text-white">\${x.orders_count || 0}</td><td class="text-center text-green-500">Aktiv</td></tr>\`).join('');
            }
        }

        function renderOrderRow(o, type) {
            let profitHtml = '<span class="text-gray-600">-</span>';
            if (o.calculated_commission > 0) {
                profitHtml = \`<span class="text-green-400 font-bold">+\${o.calculated_commission}</span>\`;
            }
            
            let amountHtml = \`<span class="text-right text-green-400 font-bold">\${formatMoney(o.grand_total)}</span>\`;
            if (o.refunded_amount > 0) {
                let netSale = parseFloat(o.grand_total) - parseFloat(o.refunded_amount);
                amountHtml = \`<div class="flex flex-col items-end"><span class="text-green-400 font-bold">\${formatMoney(netSale)}</span><span class="text-red-500 text-[10px]">Qaytar: -\${formatMoney(o.refunded_amount)}</span></div>\`;
            }

            if (type === 'short') {
                return \`<tr><td class="text-gray-400">\${o.time}</td><td class="text-blue-400 font-bold">#\${o.receipt_code}</td><td class="text-center text-xs uppercase">\${o.payment_method}</td><td class="text-center">\${profitHtml}</td><td class="text-right">\${amountHtml}</td></tr>\`;
            } else {
                return \`<tr><td class="text-gray-400">\${o.time}</td><td class="text-blue-400 font-bold">#\${o.receipt_code}</td><td class="text-center uppercase text-sm">\${o.payment_method}</td><td class="text-purple-300">\${o.promo_code || '-'}</td><td class="text-center">\${profitHtml}</td><td class="text-yellow-500 font-mono">\${o.lottery_code || '-'}</td><td class="text-right">\${amountHtml}</td></tr>\`;
            }
        }

        function setText(id, val) { 
            const el = document.getElementById(id);
            if(el) el.innerText = val; 
        }
        function formatMoney(amount) { 
            return parseFloat(amount || 0).toFixed(2) + ' ₼'; 
        }
    </script>
</body>
</html>