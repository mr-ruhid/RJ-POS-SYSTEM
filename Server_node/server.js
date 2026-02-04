require('dotenv').config();

const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const bodyParser = require('body-parser');
const cors = require('cors');
const session = require('express-session');

// --- TƏNZİMLƏMƏLƏR ---
const ADMIN_USER = process.env.ADMIN_USER || "admin";
const ADMIN_PASS = process.env.ADMIN_PASS || "admin123";

const app = express();
const server = http.createServer(app);

// SERVER TƏRƏFİ SOCKET AYARLARI
const io = new Server(server, { 
    cors: { origin: "*" },
    path: '/socket.io' // Nginx bu yolu daxilə ötürür
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
// 1. GİRİŞ SƏHİFƏSİ
// ==========================================
const loginHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - RJ POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center">
    <form action="login" method="POST" class="bg-slate-800 p-8 rounded-xl w-96 border border-slate-700 shadow-2xl">
        <h1 class="text-white text-2xl mb-6 font-bold text-center">Admin Girişi</h1>
        <input name="username" placeholder="İstifadəçi" class="w-full mb-4 p-3 rounded bg-slate-900 border border-slate-600 text-white focus:border-blue-500 outline-none">
        <input type="password" name="password" placeholder="Şifrə" class="w-full mb-6 p-3 rounded bg-slate-900 border border-slate-600 text-white focus:border-blue-500 outline-none">
        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition">Daxil Ol</button>
    </form>
</body>
</html>
`;

// ==========================================
// 2. DASHBOARD SƏHİFƏSİ
// ==========================================
const dashboardHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>Monitor - RJ POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0f172a; color: #e2e8f0; font-family: sans-serif; overflow: hidden; }
        .sidebar { width: 260px; background: #1e293b; height: 100vh; position: fixed; left: 0; top: 0; border-right: 1px solid #334155; }
        .content { margin-left: 260px; padding: 20px; height: 100vh; overflow-y: auto; }
        .nav-link { display: flex; align-items: center; padding: 12px 20px; color: #94a3b8; cursor: pointer; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: #334155; color: #fff; border-left-color: #3b82f6; }
        .stat-card { background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #253042; color: #94a3b8; position: sticky; top: 0; font-size: 0.8rem; }
        td { padding: 10px 12px; border-bottom: 1px solid #334155; color: #cbd5e1; font-size: 0.9rem; }
        tr:hover td { background: #263344; }
        .hidden-page { display: none; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        #debug-log { font-family: monospace; font-size: 10px; color: #aaa; margin-bottom: 10px; height: 20px; overflow: hidden; }
    </style>
</head>
<body>
    <div class="sidebar flex flex-col">
        <div class="h-16 flex items-center px-6 border-b border-slate-700">
            <h1 class="text-xl font-bold text-white"><i class="fa-solid fa-chart-line text-blue-500 mr-2"></i> RJ POS</h1>
        </div>
        <div class="flex-1 py-6 space-y-1">
            <div class="nav-link active" onclick="switchPage('dashboard', this)"><i class="fa-solid fa-gauge-high mr-3"></i> İcmal</div>
            <div class="nav-link" onclick="switchPage('partners', this)"><i class="fa-solid fa-users mr-3"></i> Partnyorlar</div>
            <div class="nav-link" onclick="switchPage('products', this)"><i class="fa-solid fa-box-open mr-3"></i> Məhsullar</div>
            <div class="nav-link" onclick="switchPage('warehouse', this)"><i class="fa-solid fa-warehouse mr-3"></i> Anbar</div>
            <div class="nav-link" onclick="switchPage('lottery', this)"><i class="fa-solid fa-ticket mr-3"></i> Lotereya</div>
            <div class="nav-link" onclick="switchPage('promocodes', this)"><i class="fa-solid fa-tags mr-3"></i> Promokodlar</div>
        </div>
        <div class="p-4 border-t border-slate-700">
            <div id="status" class="text-center text-xs text-red-500 font-bold mb-2">● Offline</div>
            <a href="logout" class="block text-center text-xs text-gray-400 hover:text-white border border-gray-700 py-2 rounded">ÇIXIŞ</a>
        </div>
    </div>

    <div class="content">
        <div id="debug-log">Bağlantı qurulur...</div>

        <!-- DASHBOARD -->
        <div id="page-dashboard">
            <h2 class="text-2xl font-bold text-white mb-6">Canlı Monitorinq</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stat-card border-l-4 border-blue-500"><p class="text-gray-400 text-xs uppercase">Satış</p><h3 class="text-3xl font-bold text-white mt-1" id="stat-sales">0.00 ₼</h3></div>
                <div class="stat-card border-l-4 border-green-500"><p class="text-gray-400 text-xs uppercase">Mənfəət</p><h3 class="text-3xl font-bold text-green-400 mt-1" id="stat-profit">0.00 ₼</h3></div>
                <div class="stat-card border-l-4 border-orange-500"><p class="text-gray-400 text-xs uppercase">Anbar</p><h3 class="text-2xl font-bold text-white mt-1" id="stat-stock-val">0.00 ₼</h3></div>
                <div class="stat-card border-l-4 border-purple-500"><p class="text-gray-400 text-xs uppercase">Partnyorlar</p><h3 class="text-3xl font-bold text-white mt-1" id="stat-partners">0</h3></div>
            </div>
            <div class="stat-card">
                <h3 class="text-lg font-bold text-white mb-4">Son Satışlar</h3>
                <table class="w-full">
                    <thead><tr><th>Saat</th><th>Qəbz</th><th>Ödəniş</th><th>Qazanc</th><th class="text-right">Məbləğ</th></tr></thead>
                    <tbody id="table-orders"></tbody>
                </table>
            </div>
        </div>

        <!-- DIGER SEHIFELER -->
        <div id="page-partners" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Partnyorlar</h2><div class="stat-card"><table class="w-full"><thead><tr><th>Ad</th><th>Telefon</th><th>Telegram</th><th>Balans</th></tr></thead><tbody id="table-partners"></tbody></table></div></div>
        <div id="page-products" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Məhsullar</h2><div class="stat-card overflow-y-auto max-h-[700px]"><table class="w-full"><thead><tr><th>Ad</th><th>Barkod</th><th class="text-center">Stok</th><th class="text-right">Qiymət</th></tr></thead><tbody id="table-products"></tbody></table></div></div>
        <div id="page-warehouse" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Anbar</h2><div class="stat-card overflow-y-auto max-h-[700px]"><table class="w-full"><thead><tr><th>Məhsul</th><th>Kod</th><th class="text-center">Say</th><th class="text-right">Maya</th></tr></thead><tbody id="tbody-batches"></tbody></table></div></div>
        <div id="page-lottery" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Lotereya Kodları</h2><div class="stat-card"><table class="w-full"><thead><tr><th>Qəbz</th><th>Tarix</th><th>Lotereya Kodu</th><th class="text-right">Məbləğ</th></tr></thead><tbody id="tbody-lottery"></tbody></table></div></div>
        <div id="page-promocodes" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Promokodlar</h2><div class="stat-card"><table class="w-full"><thead><tr><th>Kod</th><th>Endirim</th><th class="text-center">İstifadə</th><th class="text-center">Status</th></tr></thead><tbody id="table-promos"></tbody></table></div></div>
    </div>

    <script>
        // [VACİB] Socket Yolu: Nginx-dəki '/monitor/socket.io/' yoluna uyğun
        const socket = io({ 
            path: '/monitor/socket.io',
            transports: ['polling', 'websocket'], // Polling əsasdır
            reconnection: true
        });
        
        let currentPayload = null;

        function log(msg) {
            const el = document.getElementById('debug-log');
            el.innerText = msg;
            console.log(msg);
        }

        function switchPage(id, el) {
            document.querySelectorAll('[id^="page-"]').forEach(el => el.classList.add('hidden-page'));
            document.getElementById('page-' + id).classList.remove('hidden-page');
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            el.classList.add('active');
        }

        socket.on('connect', () => { 
            document.getElementById('status').innerText = '● Online'; 
            document.getElementById('status').className = 'text-center text-xs text-green-500 font-bold mb-2';
            log('Serverə qoşuldu.');
        });
        
        socket.on('connect_error', (err) => {
            log('Qoşulma Xətası: ' + err.message);
            document.getElementById('status').innerText = '● Xəta'; 
            document.getElementById('status').className = 'text-center text-xs text-red-500 font-bold mb-2';
        });

        socket.on('disconnect', () => { 
            document.getElementById('status').innerText = '● Offline';
            document.getElementById('status').className = 'text-center text-xs text-red-500 font-bold mb-2';
            log('Serverdən ayrıldı.');
        });

        socket.on('live_update', (data) => {
            log('Data gəldi: ' + data.type + ' (' + data.time + ')');
            if (data.type === 'full_report') {
                try {
                    renderData(data.payload);
                    log('Data uğurla render edildi!');
                } catch (e) {
                    log('RENDER XƏTASI: ' + e.message);
                    console.error(e);
                }
            }
        });

        function renderData(p) {
            const s = p.stats || {};
            
            setText('stat-sales', formatMoney(s.today_sales));
            setText('stat-profit', formatMoney(s.today_profit));
            setText('stat-stock-val', formatMoney(s.warehouse_cost));
            setText('stat-partners', s.partner_count || 0);

            // Son Satışlar
            if (p.latest_orders && Array.isArray(p.latest_orders)) {
                const tbody = document.getElementById('table-orders');
                tbody.innerHTML = p.latest_orders.map(o => {
                    let profitHtml = '<span class="text-gray-500">-</span>';
                    if (o.calculated_commission > 0) {
                        profitHtml = \`<span class="text-green-400 font-bold">+\${o.calculated_commission}</span>\`;
                    }
                    return \`<tr><td class="text-gray-400">\${o.time}</td><td class="text-white">#\${o.receipt_code}</td><td class="text-center text-sm">\${o.payment_method === 'card' ? 'KART' : 'NAĞD'}</td><td class="text-center">\${profitHtml}</td><td class="text-right text-green-400 font-bold">\${formatMoney(o.grand_total)}</td></tr>\`;
                }).join('');
            }

            // Partnyorlar
            if (p.partners && Array.isArray(p.partners)) {
                document.getElementById('table-partners').innerHTML = p.partners.map(x => \`<tr><td class="font-bold text-white">\${x.name}</td><td class="text-gray-400">\${x.phone || '-'}</td><td class="font-mono text-blue-300">\${x.telegram_chat_id || '-'}</td><td class="text-green-400 font-bold">\${formatMoney(x.balance)}</td></tr>\`).join('');
            }
            
            // Məhsullar
            if (p.products && Array.isArray(p.products)) {
                document.getElementById('table-products').innerHTML = p.products.map(x => \`<tr><td class="text-white">\${x.name}</td><td class="text-gray-400">\${x.barcode}</td><td class="text-center text-blue-400 font-bold">\${x.quantity}</td><td class="text-right text-gray-300">\${formatMoney(x.selling_price)}</td></tr>\`).join('');
            }

            // Anbar
            const warehouseData = (p.batches && p.batches.length > 0) ? p.batches : p.products;
            if (warehouseData && Array.isArray(warehouseData)) {
                document.getElementById('tbody-batches').innerHTML = warehouseData.map(x => \`<tr><td class="text-white">\${x.product_name || x.name}</td><td class="text-yellow-500 font-mono">\${x.batch_code || x.barcode}</td><td class="text-center text-white">\${x.current_quantity || x.quantity}</td><td class="text-right text-gray-400">\${formatMoney(x.cost_price)}</td></tr>\`).join('');
            }

            // [LOTEREYA DÜZƏLİŞİ]
            let lotteryData = [];
            if (p.lottery_orders && Array.isArray(p.lottery_orders) && p.lottery_orders.length > 0) {
                lotteryData = p.lottery_orders;
            } else if (p.latest_orders && Array.isArray(p.latest_orders)) {
                lotteryData = p.latest_orders.filter(o => o.lottery_code);
            }

            const lotteryBody = document.getElementById('tbody-lottery');
            if (lotteryData.length > 0) {
                lotteryBody.innerHTML = lotteryData.map(x => \`
                    <tr>
                        <td class="text-white">#\${x.receipt_code}</td>
                        <td class="text-gray-400">\${x.time}</td>
                        <td class="text-yellow-400 font-bold font-mono text-lg">\${x.lottery_code}</td>
                        <td class="text-right text-green-400">\${formatMoney(x.grand_total)}</td>
                    </tr>
                \`).join('');
            } else {
                lotteryBody.innerHTML = '<tr><td colspan="4" class="text-center py-8 text-gray-500 italic">Lotereya satışı yoxdur</td></tr>';
            }

            // Promokodlar
            if (p.promocodes && Array.isArray(p.promocodes)) {
                document.getElementById('table-promos').innerHTML = p.promocodes.map(x => \`<tr><td class="text-purple-400 font-bold">\${x.code}</td><td class="text-white">\${x.discount_value}</td><td class="text-center text-white">\${x.orders_count || 0}</td><td class="text-center text-green-500">Aktiv</td></tr>\`).join('');
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
`;

// ==========================================
// 3. ROUTES
// ==========================================

app.get('/', (req, res) => {
    if (req.session.authenticated) return res.send(dashboardHTML);
    res.send(loginHTML);
});

app.post('/login', (req, res) => {
    if (req.body.username === ADMIN_USER && req.body.password === ADMIN_PASS) {
        req.session.authenticated = true;
        res.redirect('./');
    } else {
        res.redirect('./?error=1');
    }
});

app.get('/logout', (req, res) => { 
    req.session.destroy(); 
    res.redirect('./'); 
});

// [API] Yalnız Monitorinq Məlumatlarını Qəbul Edir
app.post('/api/report', (req, res) => {
    try {
        const payload = req.body.payload;
        currentPayload = payload;
        io.emit('live_update', { type: 'full_report', payload: payload, time: new Date().toLocaleTimeString() });
        res.json({ status: true });
    } catch (e) {
        res.status(500).json({ status: false, error: e.message });
    }
});

io.on('connection', (socket) => {
    if (currentPayload) socket.emit('live_update', { type: 'full_report', payload: currentPayload });
});

server.listen(3000, () => console.log('📺 Monitor Serveri: Port 3000'));