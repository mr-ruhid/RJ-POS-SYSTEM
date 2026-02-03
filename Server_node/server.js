require('dotenv').config();

const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const bodyParser = require('body-parser');
const cors = require('cors');
const session = require('express-session');
const TelegramBot = require('node-telegram-bot-api');
const fs = require('fs');
const path = require('path');

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
const DATA_FILE = 'server_data.json';
let localData = {
    // DÜZƏLİŞ: partners artıq Obyekt (Dictionary) kimi saxlanılır: { "partner_id": "chat_id" }
    partners: {}, 
    pending_partners: [], 
    last_processed_order: null
};

if (fs.existsSync(DATA_FILE)) {
    try {
        const raw = JSON.parse(fs.readFileSync(DATA_FILE));
        localData = { ...localData, ...raw };
        // Əgər köhnə versiyadan qalıbsa və Array-dirsə, Obyektə çevir
        if (Array.isArray(localData.partners)) localData.partners = {};
        if (!Array.isArray(localData.pending_partners)) localData.pending_partners = [];
    } catch (e) { console.error("Data oxuma xətası:", e); }
}

function saveData() {
    fs.writeFileSync(DATA_FILE, JSON.stringify(localData, null, 2));
}

// --- TELEGRAM BOT ---
let bot = null;
if (TELEGRAM_TOKEN) {
    try {
        bot = new TelegramBot(TELEGRAM_TOKEN, { polling: true });
        console.log("🤖 Telegram Bot Aktivdir");

        bot.onText(/\/start/, (msg) => {
            const chatId = msg.chat.id;
            const name = msg.from.first_name;
            
            // Yaddaşda bu ID varmı? (Object.values ilə yoxlayırıq)
            const isLinked = Object.values(localData.partners).includes(chatId.toString()) || Object.values(localData.partners).includes(chatId);
            
            if(isLinked) {
                const opts = {
                    reply_markup: {
                        keyboard: [['💰 Balansım', 'ℹ️ Məlumat']],
                        resize_keyboard: true
                    }
                };
                bot.sendMessage(chatId, `Salam, ${name}! ✅ Sizin hesabınız aktivdir.`, opts);
                return;
            }

            const opts = {
                reply_markup: {
                    inline_keyboard: [
                        [
                            { text: "✅ Partnyorluğu Təsdiqlə", callback_data: 'confirm_reg' },
                            { text: "❌ Ləğv et", callback_data: 'cancel_reg' }
                        ]
                    ]
                }
            };
            bot.sendMessage(chatId, `Salam, ${name}! 👋\nRJ POS sisteminə qoşulmaq üçün təsdiq edin.`, opts);
        });

        bot.on('callback_query', (query) => {
            const chatId = query.message.chat.id;
            const msgId = query.message.message_id;
            const data = query.data;

            if (data === 'confirm_reg') {
                const pendingExists = localData.pending_partners.find(p => p.chat_id == chatId);
                // Artıq mövcud olanları təkrar əlavə etmə
                const isLinked = Object.values(localData.partners).includes(chatId.toString());

                if (!pendingExists && !isLinked) {
                    const newPending = {
                        chat_id: chatId,
                        name: query.from.first_name + (query.from.last_name ? ' ' + query.from.last_name : ''),
                        username: query.from.username || 'yoxdur',
                        date: new Date().toLocaleString()
                    };
                    localData.pending_partners.push(newPending);
                    saveData();
                    
                    // Adminə xəbər ver
                    io.emit('new_telegram_request', newPending);
                    io.emit('pending_partners_list', localData.pending_partners);
                }

                bot.editMessageText(`✅ Sorğunuz qəbul edildi!\n\n🆔 ID: \`${chatId}\`\n\nAdmin təsdiqlədikdən sonra satış bildirişləri gələcək.`, {
                    chat_id: chatId,
                    message_id: msgId,
                    parse_mode: 'Markdown'
                });

            } else if (data === 'cancel_reg') {
                bot.editMessageText("❌ İmtina edildi.", { chat_id: chatId, message_id: msgId });
            }
        });

        // Menyu Düymələri
        bot.on('message', (msg) => {
            const chatId = msg.chat.id;
            const text = msg.text;
            if (text === '💰 Balansım') {
                bot.sendMessage(chatId, "💰 Balans məlumatı növbəti satışda yenilənəcək.");
            }
        });

    } catch (error) { console.error("Bot xətası:", error.message); }
}

// --- TELEGRAM BİLDİRİŞ FUNKSİYASI ---
function notifyPartnerAboutSale(order, promocodes, partnersListFromPayload) {
    if (!bot || !order.promo_code) return;

    console.log(`📨 Telegram: Satış var! Kod: ${order.promo_code}`);

    // Promokodu tapırıq
    const promo = promocodes.find(p => p.code === order.promo_code);
    
    if (!promo) {
        console.log("❌ Telegram: Promokod tapılmadı.");
        return;
    }

    // Partnyoru tapırıq
    // DİQQƏT: ID-ləri sətir/rəqəm fərqinə görə '==' ilə yoxlayırıq
    const partner = partnersListFromPayload.find(p => p.id == promo.partner_id);

    if (!partner) {
        console.log(`❌ Telegram: Partnyor tapılmadı (ID: ${promo.partner_id})`);
        return;
    }

    // Telegram ID-ni Server Yaddaşından götürürük
    const telegramChatId = localData.partners[partner.id];

    if (telegramChatId) {
        const msg = `
🎉 **Yeni Satış!**
    
🎫 Kod: *${order.promo_code}*
💵 Məbləğ: ${order.grand_total} ₼
⏰ Saat: ${order.time || new Date().toLocaleTimeString()}

💰 Cari Balans: *${partner.balance} ₼*
        `;
        
        bot.sendMessage(telegramChatId, msg, { parse_mode: 'Markdown' })
           .then(() => console.log(`✅ Mesaj göndərildi: ${partner.name}`))
           .catch(err => console.error(`❌ Mesaj xətası: ${err.message}`));
    } else {
        console.log(`⚠️ Telegram: Partnyorun (${partner.name}) Telegram ID-si yoxdur.`);
    }
}

// ==========================================
// HTML ŞABLONLAR
// ==========================================

const loginHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center">
    <form action="login" method="POST" class="bg-slate-800 p-8 rounded-xl w-96 border border-slate-700">
        <h1 class="text-white text-2xl mb-6 font-bold text-center">Admin Girişi</h1>
        <input name="username" placeholder="İstifadəçi" class="w-full mb-4 p-3 rounded bg-slate-900 border border-slate-600 text-white focus:border-blue-500 outline-none">
        <input type="password" name="password" placeholder="Şifrə" class="w-full mb-6 p-3 rounded bg-slate-900 border border-slate-600 text-white focus:border-blue-500 outline-none">
        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition">Daxil Ol</button>
    </form>
</body>
</html>
`;

const dashboardHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="socket.io/socket.io.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #0f172a; color: #e2e8f0; font-family: sans-serif; overflow: hidden; }
        .sidebar { width: 260px; background: #1e293b; height: 100vh; position: fixed; left: 0; top: 0; border-right: 1px solid #334155; }
        .content { margin-left: 260px; padding: 20px; height: 100vh; overflow-y: auto; }
        .nav-link { display: flex; align-items: center; padding: 12px 20px; color: #94a3b8; cursor: pointer; transition: 0.3s; border-left: 4px solid transparent; }
        .nav-link:hover, .nav-link.active { background: #334155; color: #fff; border-left-color: #3b82f6; }
        .stat-card { background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; background: #253042; color: #94a3b8; position: sticky; top: 0; }
        td { padding: 12px; border-bottom: 1px solid #334155; color: #cbd5e1; }
        .hidden-page { display: none; }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 1000; }
        .hidden-modal { display: none; }
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
            <div class="nav-link active" onclick="switchPage('dashboard', this)"><i class="fa-solid fa-chart-pie mr-3"></i> İcmal</div>
            <div class="nav-link" onclick="switchPage('partners', this)"><i class="fa-solid fa-users mr-3"></i> Partnyorlar</div>
            <div class="nav-link" onclick="switchPage('products', this)"><i class="fa-solid fa-box-open mr-3"></i> Məhsullar</div>
            <div class="nav-link" onclick="switchPage('warehouse', this)"><i class="fa-solid fa-warehouse mr-3"></i> Anbar</div>
            <div class="nav-link" onclick="switchPage('lottery', this)"><i class="fa-solid fa-ticket mr-3"></i> Lotereya</div>
            <div class="nav-link" onclick="switchPage('promocodes', this)"><i class="fa-solid fa-tags mr-3"></i> Promokodlar</div>
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

        <!-- PARTNERS -->
        <div id="page-partners" class="hidden-page">
            <h2 class="text-2xl font-bold text-white mb-6">Partnyor İdarəetməsi</h2>
            <div class="stat-card"><table class="w-full"><thead><tr><th>Ad</th><th>Telefon</th><th>Telegram ID</th><th>Balans</th></tr></thead><tbody id="table-partners"><tr><td colspan="5" class="text-center py-4 text-gray-500">Mağazadan məlumat gəlməyib</td></tr></tbody></table></div>
        </div>
        
        <!-- MƏHSULLAR -->
        <div id="page-products" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Məhsullar</h2><div class="stat-card overflow-y-auto max-h-[700px]"><table class="w-full"><thead><tr><th>Ad</th><th>Barkod</th><th class="text-center">Stok</th><th class="text-right">Qiymət</th></tr></thead><tbody id="table-products"></tbody></table></div></div>
        
        <!-- ANBAR (BATCHES FALLBACK) -->
        <div id="page-warehouse" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Anbar</h2><div class="stat-card overflow-y-auto max-h-[700px]"><table class="w-full"><thead><tr><th>Məhsul</th><th>Kod</th><th class="text-center">Say</th><th class="text-right">Maya</th></tr></thead><tbody id="tbody-batches"></tbody></table></div></div>
        
        <!-- LOTEREYA (FALLBACK) -->
        <div id="page-lottery" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Lotereya Kodları</h2><div class="stat-card"><table class="w-full"><thead><tr><th>Qəbz</th><th>Tarix</th><th>Lotereya Kodu</th><th class="text-right">Məbləğ</th></tr></thead><tbody id="tbody-lottery"></tbody></table></div></div>
        
        <!-- PROMOKODLAR -->
        <div id="page-promocodes" class="hidden-page"><h2 class="text-2xl font-bold text-white mb-6">Promokodlar</h2><div class="stat-card"><table class="w-full"><thead><tr><th>Kod</th><th>Endirim</th><th class="text-center">İstifadə</th><th class="text-center">Status</th></tr></thead><tbody id="table-promos"></tbody></table></div></div>
    </div>

    <script>
        const socket = io();
        let currentPayload = null;

        function switchPage(id, el) {
            document.querySelectorAll('[id^="page-"]').forEach(el => el.classList.add('hidden-page'));
            document.getElementById('page-' + id).classList.remove('hidden-page');
            document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
            el.classList.add('active');
        }

        socket.on('connect', () => { 
            document.getElementById('status').innerText = 'Online (Yaşıl)'; 
            socket.emit('request_last_data');
            socket.emit('request_pending_partners');
        });
        
        socket.on('disconnect', () => { document.getElementById('status').innerText = 'Offline (Qırmızı)'; });

        socket.on('live_update', (data) => { if (data.type === 'full_report') renderData(data.payload); });
        
        // Gözləyən siyahısı üçün (Modalda)
        socket.on('pending_partners_list', (list) => {
            // Serverdən gələn siyahı - burada istifadə etmirik, çünki Dashboard sadəcə monitorinqdir
            // Amma konsola yazırıq ki, əlaqəni görək
            console.log("Pending Partners:", list);
        });

        function renderData(p) {
            currentPayload = p;
            const s = p.stats;
            document.getElementById('stat-sales').innerText = formatMoney(s.today_sales);
            document.getElementById('stat-profit').innerText = formatMoney(s.today_profit);
            document.getElementById('stat-stock-val').innerText = formatMoney(s.warehouse_cost);
            document.getElementById('stat-partners').innerText = s.partner_count;

            if (p.latest_orders) {
                const tbody = document.getElementById('table-orders');
                tbody.innerHTML = p.latest_orders.map(o => \`<tr><td class="text-gray-400">\${o.time}</td><td class="text-white">#\${o.receipt_code}</td><td class="text-green-400 font-bold">\${formatMoney(o.grand_total)}</td><td class="text-purple-400">\${o.promo_code || '-'}</td></tr>\`).join('');
            }

            if (p.partners) {
                const tbody = document.getElementById('table-partners');
                tbody.innerHTML = p.partners.map(x => \`<tr><td class="font-bold text-white">\${x.name}</td><td class="text-gray-400">\${x.phone}</td><td class="font-mono text-blue-300">\${x.telegram_chat_id || '-'}</td><td class="text-green-400 font-bold">\${formatMoney(x.balance)}</td></tr>\`).join('');
            }
            
            if (p.products) document.getElementById('table-products').innerHTML = p.products.map(x => \`<tr><td class="text-white">\${x.name}</td><td class="text-gray-400">\${x.barcode}</td><td class="text-center text-blue-400 font-bold">\${x.quantity}</td><td class="text-right text-gray-300">\${formatMoney(x.selling_price)}</td></tr>\`).join('');
            
            // Anbar (Batch yoxdursa Product istifadə et)
            const warehouseData = p.batches || p.products; 
            if (warehouseData) {
                document.getElementById('tbody-batches').innerHTML = warehouseData.map(x => \`<tr><td class="text-white">\${x.product_name || x.name}</td><td class="text-yellow-500 font-mono">\${x.batch_code || x.barcode}</td><td class="text-center text-white">\${x.current_quantity || x.quantity}</td><td class="text-right text-gray-400">\${formatMoney(x.cost_price)}</td></tr>\`).join('');
            }

            // Lotereya (Lottery order yoxdursa latest_orders istifadə et)
            const lotteryData = p.lottery_orders || p.latest_orders.filter(o => o.lottery_code); // Filterləyirik
            if (lotteryData.length > 0) {
                 document.getElementById('tbody-lottery').innerHTML = lotteryData.map(x => \`<tr><td class="text-white">#\${x.receipt_code}</td><td class="text-gray-400">\${x.time}</td><td class="text-yellow-400 font-bold font-mono text-lg">\${x.lottery_code || '-'}</td><td class="text-right text-green-400">\${formatMoney(x.grand_total)}</td></tr>\`).join('');
            }

            if (p.promocodes) document.getElementById('table-promos').innerHTML = p.promocodes.map(x => \`<tr><td class="text-purple-400 font-bold">\${x.code}</td><td class="text-white">\${x.discount_value}</td><td class="text-center text-white">\${x.orders_count}</td><td class="text-center text-green-500">Aktiv</td></tr>\`).join('');
        }

        function formatMoney(amount) { return parseFloat(amount || 0).toFixed(2) + ' ₼'; }
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

app.get('/api/pending-partners', (req, res) => {
    res.json(localData.pending_partners);
});

app.post('/api/report', (req, res) => {
    try {
        const payload = req.body.payload;
        
        // 1. Partnyorları yaddaşa yaz (ID Map üçün)
        if (payload.partners && Array.isArray(payload.partners)) {
            // Yaddaşdakı datanı yeniləyirik, amma mövcud ID-ləri qoruyuruq
            // Əgər mağaza partnyorun Telegram ID-sini göndərirsə, onu əsas götürürük
            payload.partners.forEach(p => {
                if (p.telegram_chat_id) {
                    localData.partners[p.id] = p.telegram_chat_id;
                }
            });

            // Gözləyən siyahısından təmizləyirik (artıq sistemdə varsa)
            const activeIds = Object.values(localData.partners);
            localData.pending_partners = localData.pending_partners.filter(u => !activeIds.includes(u.chat_id.toString()));
            
            saveData();
        }

        // 2. Satış Bildirişi (Telegram)
        if (payload.latest_orders && payload.latest_orders.length > 0 && bot) {
            const lastOrder = payload.latest_orders[0];
            // Yalnız yeni satış və promokod varsa
            if (lastOrder.receipt_code !== localData.last_processed_order && lastOrder.promo_code) {
                localData.last_processed_order = lastOrder.receipt_code;
                saveData();
                notifyPartnerAboutSale(lastOrder, payload.promocodes, payload.partners);
            }
        }

        io.emit('live_update', { type: 'full_report', payload: payload, time: new Date().toLocaleTimeString() });
        res.json({ status: true });
    } catch (e) {
        res.status(500).json({ status: false, error: e.message });
    }
});

io.on('connection', (socket) => {
    if (currentPayload) socket.emit('live_update', { type: 'full_report', payload: currentPayload });
});

server.listen(3000, () => console.log('Monitor 3000'));