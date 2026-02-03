require('dotenv').config(); // .env faylını oxumaq üçün kitabxana

const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const bodyParser = require('body-parser');
const cors = require('cors');
const session = require('express-session');

// --- ADMIN GİRİŞ MƏLUMATLARI (GİZLİ) ---
// Artıq şifrələr kodun içində deyil, serverin .env faylından oxunur
const ADMIN_USER = process.env.ADMIN_USER || "admin"; // Əgər tapmasa default 'admin'
const ADMIN_PASS = process.env.ADMIN_PASS || "admin123"; // Əgər tapmasa default 'admin123'
// -----------------------------

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

app.use(cors());
app.use(bodyParser.json({ limit: '50mb' }));
app.use(bodyParser.urlencoded({ limit: '50mb', extended: true }));

// Sessiya Tənzimləmələri
app.use(session({
    secret: process.env.SESSION_SECRET || 'gizli_açar_rj_pos_secure', // Sessiya açarını da gizlədirik
    resave: false,
    saveUninitialized: true,
    cookie: { secure: false } 
}));

// Yaddaşda son gələn data (Yeni qoşulanlar görsün deyə)
let currentData = null;

// ==========================================
// 1. HTML ŞABLONLAR (Daxili)
// ==========================================

// --- GİRİŞ SƏHİFƏSİ ---
const loginHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RJ POS - Giriş</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center">
    <div class="bg-slate-800 p-8 rounded-xl shadow-2xl border border-slate-700 w-96">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-500/20 text-blue-500 mb-4">
                <i class="fa-solid fa-shield-halved text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">Admin Girişi</h1>
            <p class="text-slate-400 text-sm mt-2">Monitorinq mərkəzinə daxil olun</p>
        </div>
        <div id="error-msg" class="hidden bg-red-500/10 border border-red-500/50 text-red-500 text-sm p-3 rounded-lg mb-4 text-center">
            İstifadəçi adı və ya şifrə yanlışdır!
        </div>
        <form action="/login" method="POST" class="space-y-5">
            <div>
                <label class="block text-slate-300 text-sm font-medium mb-1">İstifadəçi Adı</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-slate-500"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" required class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2.5 pl-10 pr-4 text-white focus:outline-none focus:border-blue-500 transition">
                </div>
            </div>
            <div>
                <label class="block text-slate-300 text-sm font-medium mb-1">Şifrə</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-slate-500"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" required class="w-full bg-slate-900 border border-slate-700 rounded-lg py-2.5 pl-10 pr-4 text-white focus:outline-none focus:border-blue-500 transition">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition shadow-lg shadow-blue-500/30">
                Daxil Ol <i class="fa-solid fa-arrow-right ml-2"></i>
            </button>
        </form>
    </div>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('error')) { document.getElementById('error-msg').classList.remove('hidden'); }
    </script>
</body>
</html>
`;

// --- DASHBOARD (MONITOR) SƏHİFƏSİ ---
const dashboardHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RJ POS - Monitorinq</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/socket.io/socket.io.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0f172a; color: #e2e8f0; font-family: sans-serif; overflow: hidden; }
        .sidebar { width: 250px; background: #1e293b; height: 100vh; position: fixed; left: 0; top: 0; border-right: 1px solid #334155; }
        .content { margin-left: 250px; padding: 20px; height: 100vh; overflow-y: auto; }
        .nav-link { display: flex; align-items: center; padding: 12px 20px; color: #94a3b8; transition: all 0.3s; cursor: pointer; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: #334155; color: #fff; border-left-color: #3b82f6; }
        .nav-link i { width: 25px; font-size: 1.2rem; }
        .stat-card { background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #334155; color: #cbd5e1; font-size: 0.85rem; position: sticky; top: 0; }
        td { padding: 12px; border-bottom: 1px solid #334155; color: #cbd5e1; font-size: 0.9rem; }
        tr:hover td { background: #263344; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        .hidden-page { display: none; }
        .animate-fade-in-down { animation: fadeInDown 0.5s ease-out; }
        @keyframes fadeInDown { 0% { opacity: 0; transform: translateY(-10px); } 100% { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar flex flex-col">
        <div class="h-16 flex items-center px-6 border-b border-slate-700">
            <h1 class="text-xl font-bold text-white"><i class="fa-solid fa-chart-pie mr-2 text-blue-500"></i> RJ POS</h1>
        </div>
        <div class="flex-1 py-4 space-y-1">
            <div class="nav-link active" onclick="switchPage('dashboard', this)"><i class="fa-solid fa-gauge-high"></i> İcmal</div>
            <div class="nav-link" onclick="switchPage('products', this)"><i class="fa-solid fa-box-open"></i> Məhsullar</div>
            <div class="nav-link" onclick="switchPage('promocodes', this)"><i class="fa-solid fa-tags"></i> Promokodlar</div>
        </div>
        <div class="p-4 border-t border-slate-700">
            <div id="connection-status" class="flex items-center space-x-2 text-sm text-gray-400"><span class="w-2 h-2 rounded-full bg-red-500"></span><span>Offline</span></div>
            <a href="/logout" class="block text-center text-xs text-red-400 hover:text-red-300 mt-3 font-bold">ÇIXIŞ ET</a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <!-- 1. DASHBOARD -->
        <div id="page-dashboard">
            <h2 class="text-2xl font-bold text-white mb-6">Canlı Monitorinq</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stat-card border-l-4 border-blue-500"><p class="text-gray-400 text-sm uppercase">Günlük Satış</p><h3 class="text-3xl font-bold text-white mt-1" id="stat-sales">0.00 ₼</h3><p class="text-xs text-blue-400 mt-1" id="stat-count">0 çek</p></div>
                <div class="stat-card border-l-4 border-green-500"><p class="text-gray-400 text-sm uppercase">Xalis Mənfəət</p><h3 class="text-3xl font-bold text-green-400 mt-1" id="stat-profit">0.00 ₼</h3></div>
                <div class="stat-card border-l-4 border-orange-500"><p class="text-gray-400 text-sm uppercase">Anbar Dəyəri</p><h3 class="text-2xl font-bold text-white mt-1" id="stat-stock-val">0.00 ₼</h3><p class="text-xs text-gray-400 mt-1">Potensial: <span class="text-green-400" id="stat-potential">+0.00</span></p></div>
                <div class="stat-card border-l-4 border-purple-500"><p class="text-gray-400 text-sm uppercase">Partnyorlar</p><h3 class="text-3xl font-bold text-white mt-1" id="stat-partners">0</h3></div>
            </div>
            <div class="stat-card">
                <h3 class="text-lg font-bold text-white mb-4 border-b border-slate-700 pb-2">Son Əməliyyatlar</h3>
                <table class="w-full">
                    <thead><tr><th>Saat</th><th>Qəbz №</th><th>Ödəniş</th><th>Məhsul Sayı</th><th class="text-right">Məbləğ</th></tr></thead>
                    <tbody id="table-orders"><tr><td colspan="5" class="text-center py-4 text-gray-500">Məlumat yoxdur</td></tr></tbody>
                </table>
            </div>
        </div>

        <!-- 2. PRODUCTS -->
        <div id="page-products" class="hidden-page">
            <div class="flex justify-between items-center mb-6"><h2 class="text-2xl font-bold text-white">Məhsul Bazası</h2><input type="text" id="search-product" onkeyup="filterTable('table-products', this.value)" placeholder="Axtar..." class="bg-slate-700 border border-slate-600 text-white px-4 py-2 rounded focus:outline-none focus:border-blue-500"></div>
            <div class="stat-card overflow-hidden"><div class="overflow-y-auto max-h-[700px]"><table class="w-full"><thead><tr><th>Ad</th><th>Barkod</th><th class="text-center">Stok</th><th class="text-right">Maya Dəyəri</th><th class="text-right">Satış Qiyməti</th><th class="text-center">Status</th></tr></thead><tbody id="table-products"><tr><td colspan="6" class="text-center py-4">Yüklənir...</td></tr></tbody></table></div></div>
        </div>

        <!-- 3. PROMOCODES -->
        <div id="page-promocodes" class="hidden-page">
            <h2 class="text-2xl font-bold text-white mb-6">Aktiv Promokodlar</h2>
            <div class="stat-card"><table class="w-full"><thead><tr><th>Kod</th><th>Endirim</th><th class="text-center">İstifadə Sayı</th><th class="text-center">Status</th></tr></thead><tbody id="table-promos"><tr><td colspan="4" class="text-center py-4">Məlumat yoxdur</td></tr></tbody></table></div>
        </div>
    </div>

    <script>
        const socket = io();
        function switchPage(pageId, element) {
            document.getElementById('page-dashboard').classList.add('hidden-page');
            document.getElementById('page-products').classList.add('hidden-page');
            document.getElementById('page-promocodes').classList.add('hidden-page');
            document.getElementById('page-' + pageId).classList.remove('hidden-page');
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }
        socket.on('connect', () => { document.getElementById('connection-status').innerHTML = '<span class="w-2 h-2 rounded-full bg-green-500"></span><span class="text-green-400">Online</span>'; socket.emit('request_last_data'); });
        socket.on('disconnect', () => { document.getElementById('connection-status').innerHTML = '<span class="w-2 h-2 rounded-full bg-red-500"></span><span class="text-red-400">Offline</span>'; });
        socket.on('live_update', (data) => { if (data.type === 'full_report') renderData(data.payload); });

        function renderData(payload) {
            const stats = payload.stats;
            updateText('stat-sales', formatMoney(stats.today_sales));
            updateText('stat-count', stats.today_count + ' çek');
            updateText('stat-profit', formatMoney(stats.today_profit));
            updateText('stat-partners', stats.partner_count);
            updateText('stat-stock-val', formatMoney(stats.warehouse_cost));
            updateText('stat-potential', '+' + formatMoney(stats.potential_profit));

            const ordersBody = document.getElementById('table-orders');
            if (payload.latest_orders.length > 0) {
                ordersBody.innerHTML = payload.latest_orders.map(o => \`<tr><td class="font-mono text-gray-400">\${o.time}</td><td class="font-bold text-white">#\${o.receipt_code || '---'}</td><td class="text-center"><span class="px-2 py-1 rounded text-xs font-bold \${o.payment_method === 'card' ? 'bg-blue-900 text-blue-300' : 'bg-green-900 text-green-300'}">\${o.payment_method === 'card' ? 'KART' : 'NAĞD'}</span></td><td class="text-center text-gray-400">\${o.items_count}</td><td class="text-right font-bold text-green-400">+\${formatMoney(o.grand_total)}</td></tr>\`).join('');
            }
            const prodBody = document.getElementById('table-products');
            if (payload.products && payload.products.length > 0) {
                prodBody.innerHTML = payload.products.map(p => \`<tr><td class="font-bold text-white">\${p.name}</td><td class="font-mono text-gray-400">\${p.barcode}</td><td class="text-center font-bold \${p.quantity < 5 ? 'text-red-500' : 'text-blue-400'}">\${p.quantity}</td><td class="text-right text-gray-500">\${formatMoney(p.cost_price)}</td><td class="text-right font-bold text-white">\${formatMoney(p.selling_price)}</td><td class="text-center">\${p.is_active ? '<span class="text-green-500 text-xs">●</span>' : '<span class="text-red-500 text-xs">●</span>'}</td></tr>\`).join('');
            }
            const promoBody = document.getElementById('table-promos');
            if (payload.promocodes && payload.promocodes.length > 0) {
                promoBody.innerHTML = payload.promocodes.map(pr => \`<tr><td class="font-bold font-mono text-purple-400">\${pr.code}</td><td>\${pr.discount_type === 'percent' ? pr.discount_value + '%' : pr.discount_value + ' AZN'}</td><td class="text-center text-white">\${pr.orders_count}</td><td class="text-center text-green-500">Aktiv</td></tr>\`).join('');
            }
        }
        function filterTable(tableId, query) {
            const filter = query.toUpperCase();
            const table = document.getElementById(tableId);
            const tr = table.getElementsByTagName("tr");
            for (let i = 0; i < tr.length; i++) {
                const tdName = tr[i].getElementsByTagName("td")[0];
                const tdCode = tr[i].getElementsByTagName("td")[1];
                if (tdName || tdCode) {
                    const txtValueName = tdName.textContent || tdName.innerText;
                    const txtValueCode = tdCode ? (tdCode.textContent || tdCode.innerText) : '';
                    if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueCode.toUpperCase().indexOf(filter) > -1) { tr[i].style.display = ""; } else { tr[i].style.display = "none"; }
                }
            }
        }
        function updateText(id, val) { const el = document.getElementById(id); if(el) el.innerText = val; }
        function formatMoney(amount) { return parseFloat(amount || 0).toFixed(2) + ' ₼'; }
    </script>
</body>
</html>
`;

// ==========================================
// 2. ROUTES
// ==========================================

// Middleware: Giriş Yoxlanışı
const requireAuth = (req, res, next) => {
    if (req.session && req.session.authenticated) {
        return next();
    } else {
        return res.redirect('/');
    }
};

// 1. Giriş Səhifəsi
app.get('/', (req, res) => {
    if (req.session.authenticated) {
        return res.redirect('/monitor');
    }
    res.send(loginHTML);
});

// 2. Giriş Postu
app.post('/login', (req, res) => {
    const { username, password } = req.body;
    if (username === ADMIN_USER && password === ADMIN_PASS) {
        req.session.authenticated = true;
        res.redirect('/monitor');
    } else {
        res.redirect('/?error=1');
    }
});

// 3. Monitorinq Ekranı (Qorunan)
app.get('/monitor', requireAuth, (req, res) => {
    res.send(dashboardHTML);
});

// 4. Çıxış
app.get('/logout', (req, res) => {
    req.session.destroy();
    res.redirect('/');
});

// 5. API (Local Mağaza bura göndərir - AÇIQDIR)
app.post('/api/report', (req, res) => {
    try {
        const data = req.body;
        console.log(`📡 Data Gəldi: ${data.type}`);
        currentData = data;
        io.emit('live_update', data);
        res.json({ status: true });
    } catch (e) {
        res.status(500).json({ status: false, error: e.message });
    }
});

// Socket.IO
io.on('connection', (socket) => {
    if(currentData) socket.emit('live_update', currentData);
    socket.on('request_last_data', () => { if(currentData) socket.emit('live_update', currentData); });
});

const PORT = 3000;
server.listen(PORT, () => {
    console.log(`🚀 Server İşləyir: Port ${PORT}`);
});