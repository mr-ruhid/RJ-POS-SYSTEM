require('dotenv').config();

const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const bodyParser = require('body-parser');
const cors = require('cors');
const session = require('express-session');
const TelegramBot = require('node-telegram-bot-api');
const fs = require('fs');

// --- TƏNZİMLƏMƏLƏR ---
const ADMIN_USER = process.env.ADMIN_USER || "admin";
const ADMIN_PASS = process.env.ADMIN_PASS || "admin123";
const TELEGRAM_TOKEN = process.env.TELEGRAM_BOT_TOKEN;

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

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

// --- YADDAŞ ---
// Son gələn tam paketi yaddaşda saxlayırıq ki, bot sual verəndə cavab verə bilsin
let currentPayload = null;
let lastProcessedOrderCode = null; // Təkrar bildiriş getməsin deyə

// --- TELEGRAM BOT ---
let bot = null;
if (TELEGRAM_TOKEN) {
    try {
        bot = new TelegramBot(TELEGRAM_TOKEN, { polling: true });
        console.log("🤖 Telegram Bot Aktivdir");

        // /start Komandası
        bot.onText(/\/start/, (msg) => {
            const chatId = msg.chat.id;
            const name = msg.from.first_name;
            
            const opts = {
                reply_markup: {
                    keyboard: [
                        ['📊 Günlük Hesabat', '📅 Aylıq Hesabat'],
                        ['💰 Balansım', 'ℹ️ Məlumat']
                    ],
                    resize_keyboard: true,
                    one_time_keyboard: false
                }
            };
            
            bot.sendMessage(chatId, `Salam, ${name}! 👋\nSizin ID: \`${chatId}\`\n\nZəhmət olmasa bu ID-ni Mağaza admininə təqdim edin ki, hesabınızla əlaqələndirilsin.`, { parse_mode: 'Markdown', ...opts });
        });

        // Düymələrə reaksiya (Statistika)
        bot.on('message', (msg) => {
            const chatId = msg.chat.id;
            const text = msg.text;

            if (!currentPayload || !currentPayload.partners) {
                if (text !== '/start') bot.sendMessage(chatId, "⚠️ Hələlik məlumat yoxdur. Mağaza sinxronizasiya edilməyib.");
                return;
            }

            // Chat ID-yə görə partnyoru tapırıq
            const partner = currentPayload.partners.find(p => p.telegram_chat_id == chatId);

            if (!partner) {
                if (text !== '/start') bot.sendMessage(chatId, "❌ Sizin hesabınız hələ təsdiqlənməyib və ya əlaqələndirilməyib.");
                return;
            }

            // Partnyorun promokodlarını tapırıq
            const myPromos = currentPayload.promocodes.filter(pc => pc.partner_id === partner.id);
            
            if (text === '📊 Günlük Hesabat') {
                // Burada günlük satış hesabatı olmalıdır (Mağaza bunu hesablayıb göndərməlidir)
                // Hələlik ümumi statistikadan nümunə:
                let msg = `📅 **Günlük Hesabat**\n`;
                msg += `👤 Partnyor: ${partner.name}\n\n`;
                
                if (myPromos.length > 0) {
                    myPromos.forEach(p => {
                        msg += `🎫 Kod: *${p.code}* - ${p.orders_count} istifadə\n`;
                    });
                } else {
                    msg += "Sizin aktiv promokodunuz yoxdur.";
                }
                bot.sendMessage(chatId, msg, { parse_mode: 'Markdown' });
            } 
            else if (text === '💰 Balansım') {
                bot.sendMessage(chatId, `💰 **Cari Balans:** ${partner.balance} ₼\n(Yenilənmə vaxtı: ${new Date().toLocaleTimeString()})`, { parse_mode: 'Markdown' });
            }
            else if (text === '📅 Aylıq Hesabat') {
                 bot.sendMessage(chatId, "📅 Aylıq statistika hazırlanır...");
            }
        });

    } catch (error) {
        console.error("Telegram Bot Xətası:", error.message);
    }
}

// --- TELEGRAM BİLDİRİŞ FUNKSİYASI ---
function notifyPartnerAboutSale(order, promocodes, partners) {
    if (!bot || !order.promo_code) return;

    // Promokodu tap
    const promo = promocodes.find(p => p.code === order.promo_code);
    if (!promo) return;

    // Partnyoru tap
    const partner = partners.find(p => p.id === promo.partner_id);
    if (!partner || !partner.telegram_chat_id) return;

    // Komissiya hesabı (sadəlik üçün: endirim məbləğinin yarısı və ya sabit faiz)
    // Qeyd: Real komissiya məbləği Mağazadan gəlsə daha dəqiq olar.
    // Burada sadəcə məlumat veririk.
    
    const message = `
🎉 **Yeni Satış!**
    
🎫 Kod: *${order.promo_code}*
💵 Satış Məbləği: ${order.grand_total} ₼
⏰ Saat: ${order.time}

Təbriklər! Balansınız yeniləndi.
    `;

    bot.sendMessage(partner.telegram_chat_id, message, { parse_mode: 'Markdown' });
}


// ==========================================
// 1. HTML ŞABLONLAR
// ==========================================

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
            <h1 class="text-2xl font-bold text-white">Admin Girişi</h1>
            <p class="text-slate-400 text-sm mt-2">Monitorinq mərkəzinə daxil olun</p>
        </div>
        <form action="login" method="POST" class="space-y-5">
            <input type="text" name="username" placeholder="İstifadəçi Adı" required class="w-full bg-slate-900 border border-slate-700 rounded-lg py-3 px-4 text-white">
            <input type="password" name="password" placeholder="Şifrə" required class="w-full bg-slate-900 border border-slate-700 rounded-lg py-3 px-4 text-white">
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg transition">Daxil Ol</button>
        </form>
    </div>
</body>
</html>
`;

// Dashboard HTML - "Modal" çıxarıldı, "Partnyorlar" cədvəli sadələşdirildi
const dashboardHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>RJ POS - Monitorinq</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="socket.io/socket.io.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0f172a; color: #e2e8f0; font-family: sans-serif; overflow: hidden; }
        .sidebar { width: 260px; background: #1e293b; height: 100vh; position: fixed; left: 0; top: 0; border-right: 1px solid #334155; }
        .content { margin-left: 260px; padding: 20px; height: 100vh; overflow-y: auto; }
        .nav-link { display: flex; align-items: center; padding: 12px 20px; color: #94a3b8; cursor: pointer; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: #334155; color: #fff; border-left-color: #3b82f6; }
        .nav-link i { width: 25px; font-size: 1.2rem; }
        .stat-card { background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #253042; color: #94a3b8; position: sticky; top: 0; }
        td { padding: 12px; border-bottom: 1px solid #334155; color: #cbd5e1; }
        .hidden-page { display: none; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="sidebar flex flex-col">
        <div class="h-16 flex items-center px-6 border-b border-slate-700">
            <h1 class="text-xl font-bold text-white"><i class="fa-brands fa-telegram text-blue-500 mr-2"></i> RJ POS</h1>
        </div>
        <div class="flex-1 py-6 space-y-1">
            <div class="nav-link active" onclick="switchPage('dashboard', this)"><i class="fa-solid fa-chart-pie"></i> İcmal</div>
            <div class="nav-link" onclick="switchPage('partners', this)"><i class="fa-solid fa-users"></i> Partnyorlar</div>
            <div class="nav-link" onclick="switchPage('products', this)"><i class="fa-solid fa-box-open"></i> Məhsullar</div>
            <div class="nav-link" onclick="switchPage('warehouse', this)"><i class="fa-solid fa-warehouse"></i> Anbar</div>
            <div class="nav-link" onclick="switchPage('lottery', this)"><i class="fa-solid fa-ticket"></i> Lotereya</div>
            <div class="nav-link" onclick="switchPage('promocodes', this)"><i class="fa-solid fa-tags"></i> Promokodlar</div>
        </div>
        <div class="p-4 border-t border-slate-700">
            <div id="status" class="text-center text-xs text-red-500 font-bold mb-2">Offline</div>
            <a href="logout" class="block text-center text-xs text-gray-400 hover:text-white border border-gray-700 py-2 rounded">ÇIXIŞ</a>
        </div>
    </div>

    <div class="content">
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
                <table class="w-full"><thead><tr><th>Saat</th><th>Qəbz</th><th>Məbləğ</th><th>Promokod</th></tr></thead><tbody id="table-orders"></tbody></table>
            </div>
        </div>

        <!-- PARTNERS (MODALSIZ) -->
        <div id="page-partners" class="hidden-page">
            <h2 class="text-2xl font-bold text-white mb-6">Partnyorlar</h2>
            <div class="stat-card"><table class="w-full"><thead><tr><th>Ad</th><th>Telefon</th><th>Telegram ID</th><th>Balans</th></tr></thead><tbody id="table-partners"></tbody></table></div>
        </div>
        
        <!-- DIGER SƏHİFƏLƏR (MƏHSUL, ANBAR...) -->
        <div id="page-products" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Məhsullar</h2><div class="stat-card overflow-y-auto max-h-[700px]"><table class="w-full"><thead><tr><th>Ad</th><th>Barkod</th><th class="text-center">Stok</th><th class="text-right">Qiymət</th></tr></thead><tbody id="table-products"></tbody></table></div></div>
        <div id="page-warehouse" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Anbar</h2><div class="stat-card overflow-y-auto max-h-[700px]"><table class="w-full"><thead><tr><th>Məhsul</th><th>Kod</th><th class="text-center">Say</th><th class="text-right">Maya</th></tr></thead><tbody id="tbody-batches"></tbody></table></div></div>
        <div id="page-lottery" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Lotereya</h2><div class="stat-card"><table class="w-full"><thead><tr><th>Qəbz</th><th>Lotereya</th><th class="text-right">Məbləğ</th></tr></thead><tbody id="tbody-lottery"></tbody></table></div></div>
        <div id="page-promocodes" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Promokodlar</h2><div class="stat-card"><table class="w-full"><thead><tr><th>Kod</th><th>Endirim</th><th class="text-center">İstifadə</th><th class="text-center">Status</th></tr></thead><tbody id="table-promos"></tbody></table></div></div>

    </div>

    <script>
        const socket = io();
        function switchPage(id, el) {
            document.querySelectorAll('.hidden-page, #page-dashboard').forEach(d => { if(d.id !== 'page-'+id) d.style.display='none'; });
            document.getElementById('page-'+id).style.display='block';
            document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
            el.classList.add('active');
        }
        socket.on('connect', () => document.getElementById('status').innerText = 'Online (Yaşıl)');
        
        socket.on('live_update', (data) => {
            if(data.type === 'full_report') renderData(data.payload);
        });

        function renderData(p) {
            const s = p.stats;
            document.getElementById('stat-sales').innerText = s.today_sales + ' ₼';
            document.getElementById('stat-profit').innerText = s.today_profit + ' ₼';
            document.getElementById('stat-stock-val').innerText = s.warehouse_cost + ' ₼';
            document.getElementById('stat-partners').innerText = s.partner_count;

            // Satışlar
            if(p.latest_orders) {
                document.getElementById('table-orders').innerHTML = p.latest_orders.map(o => \`<tr><td class="text-gray-400">\${o.time}</td><td class="text-white">#\${o.receipt_code}</td><td class="text-green-400 font-bold">\${o.grand_total} ₼</td><td class="text-purple-400">\${o.promo_code || '-'}</td></tr>\`).join('');
            }
            // Partnyorlar
            if(p.partners) {
                document.getElementById('table-partners').innerHTML = p.partners.map(x => \`<tr><td class="font-bold text-white">\${x.name}</td><td class="text-gray-400">\${x.phone}</td><td class="font-mono text-blue-300">\${x.telegram_chat_id || '-'}</td><td class="text-green-400 font-bold">\${x.balance} ₼</td></tr>\`).join('');
            }
            // Məhsullar
            if(p.products) document.getElementById('table-products').innerHTML = p.products.map(x => \`<tr><td class="text-white">\${x.name}</td><td class="text-gray-400">\${x.barcode}</td><td class="text-center text-blue-400 font-bold">\${x.quantity}</td><td class="text-right text-gray-300">\${x.selling_price}</td></tr>\`).join('');
            // Anbar
            if(p.batches) document.getElementById('tbody-batches').innerHTML = p.batches.map(x => \`<tr><td class="text-white">\${x.product_name}</td><td class="text-yellow-500">\${x.batch_code}</td><td class="text-center text-white">\${x.current_quantity}</td><td class="text-right text-gray-400">\${x.cost_price}</td></tr>\`).join('');
            // Lotereya
            if(p.lottery_orders) document.getElementById('tbody-lottery').innerHTML = p.lottery_orders.map(x => \`<tr><td class="text-white">#\${x.receipt_code}</td><td class="text-yellow-400 font-bold">\${x.lottery_code}</td><td class="text-right text-green-400">\${x.grand_total}</td></tr>\`).join('');
            // Promokod
            if(p.promocodes) document.getElementById('table-promos').innerHTML = p.promocodes.map(x => \`<tr><td class="text-purple-400 font-bold">\${x.code}</td><td class="text-white">\${x.discount_value}</td><td class="text-center text-white">\${x.orders_count}</td><td class="text-center text-green-500">Aktiv</td></tr>\`).join('');
        }
    </script>
</body>
</html>
`;

// ==========================================
// ROUTES
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

app.get('/logout', (req, res) => { req.session.destroy(); res.redirect('./'); });

app.post('/api/report', (req, res) => {
    try {
        const data = req.body;
        const payload = data.payload;
        currentPayload = payload; // Yaddaşda saxla

        // Telegram Bildiriş (Satış zamanı)
        if (payload.latest_orders && payload.latest_orders.length > 0 && bot) {
            const lastOrder = payload.latest_orders[0];
            
            // Yalnız YENİ satışdırsa və PROMOKOD varsa
            if (lastOrder.receipt_code !== lastProcessedOrderCode && lastOrder.promo_code) {
                lastProcessedOrderCode = lastOrder.receipt_code;
                notifyPartnerAboutSale(lastOrder, payload.promocodes, payload.partners);
            }
        }

        io.emit('live_update', data);
        res.json({ status: true });
    } catch (e) {
        res.status(500).json({ status: false, error: e.message });
    }
});

io.on('connection', (socket) => {
    if (currentPayload) socket.emit('live_update', { type: 'full_report', payload: currentPayload });
});

server.listen(3000, () => console.log('Monitor 3000'));