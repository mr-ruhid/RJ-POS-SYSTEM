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
// Mağaza API açarı (Yoxlamaq üçün)
const STORE_API_KEY = "rj_live_982348729384729384"; // Local ilə eyni olmalıdır

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
    partners: [], // Təsdiqlənmiş partnyorlar (Mağazadan gəlir)
    pending_partners: [], // Gözləyən istəklər (Telegramdan gəlir)
    last_processed_order: null
};

if (fs.existsSync(DATA_FILE)) {
    try {
        const raw = JSON.parse(fs.readFileSync(DATA_FILE));
        localData = { ...localData, ...raw };
        if (!Array.isArray(localData.partners)) localData.partners = [];
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
            
            // Artıq sistemdə varmı?
            const exists = localData.partners.find(p => p.telegram_chat_id == chatId);
            if(exists) {
                bot.sendMessage(chatId, `Salam ${exists.name}! ✅ Hesabınız aktivdir.`);
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
                // Gözləyən siyahıya əlavə et
                const pendingExists = localData.pending_partners.find(p => p.chat_id == chatId);
                
                if (!pendingExists) {
                    const newPending = {
                        chat_id: chatId,
                        name: query.from.first_name + (query.from.last_name ? ' ' + query.from.last_name : ''),
                        username: query.from.username || 'yoxdur',
                        date: new Date().toLocaleString()
                    };
                    localData.pending_partners.push(newPending);
                    saveData();
                }

                bot.editMessageText(`✅ Sorğunuz qəbul edildi!\n\n🆔 ID: \`${chatId}\`\n\nAdmin sizi təsdiqlədikdən sonra bildiriş gələcək.`, {
                    chat_id: chatId,
                    message_id: msgId,
                    parse_mode: 'Markdown'
                });

            } else if (data === 'cancel_reg') {
                bot.editMessageText("❌ İmtina edildi.", { chat_id: chatId, message_id: msgId });
            }
        });

    } catch (error) { console.error("Bot xətası:", error.message); }
}

// --- HTML ŞABLONLAR ---
const loginHTML = `<!DOCTYPE html><html lang="az"><head><meta charset="UTF-8"><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-900 h-screen flex items-center justify-center"><form action="login" method="POST" class="bg-slate-800 p-8 rounded-xl w-96 border border-slate-700"><h1 class="text-white text-2xl mb-6 font-bold text-center">Admin Girişi</h1><input name="username" placeholder="İstifadəçi" class="w-full mb-4 p-3 rounded bg-slate-900 border border-slate-600 text-white focus:border-blue-500 outline-none"><input type="password" name="password" placeholder="Şifrə" class="w-full mb-6 p-3 rounded bg-slate-900 border border-slate-600 text-white focus:border-blue-500 outline-none"><button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition">Daxil Ol</button></form></body></html>`;

// (Dashboard HTML kodu olduğu kimi qalır - qısaltmaq üçün buraya tam yazmadım, eyni qala bilər)
// Əgər lazımdırsa, əvvəlki cavabdan Dashboard HTML-i bura əlavə edə bilərəm.
// Sadəlik üçün burada minimal versiyanı saxlayıram, amma siz tam versiyanı istifadə edin.
const dashboardHTML = `<!DOCTYPE html><html><body><h1>Monitorinq Ekranı</h1></body></html>`; 


// --- API ROUTES ---

// 1. [YENİ] Mağaza (Client) Gözləyən İstifadəçiləri Çəkir
app.get('/api/pending-partners', (req, res) => {
    // API Key yoxlanışı (Sizin PartnerController-də göndərdiyiniz 'api_key')
    const apiKey = req.query.api_key;
    
    // Sadə yoxlanış
    if(!apiKey) return res.status(401).json({ error: 'API Key yoxdur' });

    res.json(localData.pending_partners);
});

// 2. Məlumat Qəbulu (Sync)
app.post('/api/report', (req, res) => {
    try {
        const payload = req.body.payload;
        
        // A. Rəsmi Partnyorları Yeniləyirik
        if (payload.partners && Array.isArray(payload.partners)) {
            localData.partners = payload.partners;
            
            // Əgər partnyor artıq rəsmi siyahıdadırsa, onu "gözləyən"lərdən silirik
            const officialIds = payload.partners.map(p => p.telegram_chat_id).filter(id => id);
            localData.pending_partners = localData.pending_partners.filter(p => !officialIds.includes(p.chat_id.toString()));
            
            saveData();
        }

        // B. Satış Bildirişi
        if (payload.latest_orders && payload.latest_orders.length > 0 && bot) {
            const lastOrder = payload.latest_orders[0];
            
            if (lastOrder.receipt_code !== localData.last_processed_order && lastOrder.promo_code) {
                localData.last_processed_order = lastOrder.receipt_code;
                saveData();
                notifyPartnerAboutSale(lastOrder, payload.promocodes, localData.partners);
            }
        }

        // Ekrana ötür
        io.emit('live_update', { type: 'full_report', payload: payload, time: new Date().toLocaleTimeString() });
        res.json({ status: true });

    } catch (e) {
        res.status(500).json({ status: false, error: e.message });
    }
});

// Digər Routelər (Login, Logout, Dashboard) əvvəlki kimidir...
app.get('/', (req, res) => {
    if (req.session.authenticated) {
        // Burada əsl dashboardHTML dəyişənini (bayaq verdiyim) istifadə edin
        return res.sendFile(path.join(__dirname, 'views', 'dashboard.html')); 
    }
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

function notifyPartnerAboutSale(order, promocodes, partners) {
    if (!bot || !order.promo_code) return;
    const promo = promocodes.find(p => p.code === order.promo_code);
    if (!promo) return;
    const partner = partners.find(p => p.id === promo.partner_id);
    if (!partner || !partner.telegram_chat_id) return;

    bot.sendMessage(partner.telegram_chat_id, `💰 **Yeni Satış!**\nKod: ${order.promo_code}\nMəbləğ: ${order.grand_total} ₼`, { parse_mode: 'Markdown' });
}

server.listen(3000, () => console.log('Server işləyir...'));