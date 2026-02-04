require('dotenv').config();
const express = require('express');
const bodyParser = require('body-parser');
const TelegramBot = require('node-telegram-bot-api');
const fs = require('fs');
const cors = require('cors');

// --- AYARLAR ---
const PORT = process.env.TG_API_PORT || 4000; // Bu API 4000-ci portda işləyəcək
const TELEGRAM_TOKEN = process.env.TELEGRAM_BOT_TOKEN;
const API_KEY = process.env.CLIENT_API_KEY; 

const app = express();
app.use(cors());
app.use(bodyParser.json({ limit: '50mb' }));

// --- YADDAŞ SİSTEMİ (JSON) ---
const DATA_FILE = 'telegram_bridge_data.json';
let storage = {
    partner_chats: {}, 
    pending_requests: [], 
    processed_orders: [] 
};

// Yaddaşı oxuyuruq
if (fs.existsSync(DATA_FILE)) {
    try {
        const raw = JSON.parse(fs.readFileSync(DATA_FILE));
        storage = { ...storage, ...raw };
    } catch (e) { console.error("Data oxuma xətası:", e); }
}

function save() {
    fs.writeFileSync(DATA_FILE, JSON.stringify(storage, null, 2));
}

// --- TELEGRAM BOT MƏNTİQİ ---
let bot = null;

if (TELEGRAM_TOKEN) {
    try {
        bot = new TelegramBot(TELEGRAM_TOKEN, { polling: true });
        console.log("🤖 Telegram Bot (Bridge) Aktivdir");

        // 1. /start Komandası
        bot.onText(/\/start/, (msg) => {
            const chatId = msg.chat.id;
            const name = msg.from.first_name;

            const isRegistered = Object.values(storage.partner_chats).includes(chatId.toString()) || Object.values(storage.partner_chats).includes(chatId);

            if (isRegistered) {
                const opts = {
                    reply_markup: {
                        keyboard: [['📊 Hesabatlar', '💰 Balans']],
                        resize_keyboard: true
                    }
                };
                bot.sendMessage(chatId, `Salam, ${name}! ✅ Sizin hesabınız aktivdir.`, opts);
            } else {
                const opts = {
                    reply_markup: {
                        inline_keyboard: [
                            [{ text: "✅ Partnyorluğu Təsdiqlə", callback_data: 'confirm_reg' }],
                            [{ text: "❌ Ləğv et", callback_data: 'cancel_reg' }]
                        ]
                    }
                };
                bot.sendMessage(chatId, `Salam, ${name}! 👋\nRJ POS sisteminə qoşulmaq üçün zəhmət olmasa təsdiqləyin.`, opts);
            }
        });

        // 2. Düymə (Callback) Məntiqi
        bot.on('callback_query', (query) => {
            const chatId = query.message.chat.id;
            const msgId = query.message.message_id;
            const data = query.data;

            if (data === 'confirm_reg') {
                const exists = storage.pending_requests.find(u => u.chat_id == chatId);
                const isLinked = Object.values(storage.partner_chats).includes(chatId.toString());

                if (!exists && !isLinked) {
                    const newRequest = {
                        chat_id: chatId,
                        name: query.from.first_name + (query.from.last_name ? ' ' + query.from.last_name : ''),
                        username: query.from.username || 'yoxdur',
                        date: new Date().toLocaleString()
                    };
                    storage.pending_requests.push(newRequest);
                    save();
                }

                bot.editMessageText(`✅ Sorğunuz qəbul edildi!\n\n🆔 ID: \`${chatId}\`\n\nAdmin təsdiqini gözləyin.`, {
                    chat_id: chatId, message_id: msgId, parse_mode: 'Markdown'
                });

            } else if (data === 'cancel_reg') {
                bot.editMessageText("❌ İmtina edildi.", { chat_id: chatId, message_id: msgId });
            }
        });

        bot.on('message', (msg) => {
            if (msg.text === '💰 Balans') {
                bot.sendMessage(msg.chat.id, "💰 Balans məlumatı satış olduqda yenilənəcək.");
            }
        });

    } catch (e) { console.error("Bot başlatma xətası:", e); }
}

// --- API ENDPOINTLƏR ---

// [YENİ] Test üçün əsas səhifə (Brauzerdə açanda 404 verməsin)
app.get('/', (req, res) => {
    res.send('🚀 Telegram API Serveri İşləyir (Port 4000)');
});

// 1. [GET] Gözləyən istifadəçilər
app.get('/api/pending-partners', (req, res) => {
    res.json(storage.pending_requests);
});

// 2. [POST] Partnyor yaradıldı -> Bot mesajı
app.post('/api/partner-welcome', (req, res) => {
    const { chat_id, name, promo_code, discount, commission } = req.body;
    
    if (bot && chat_id) {
        storage.pending_requests = storage.pending_requests.filter(u => u.chat_id != chat_id);
        save();

        const msg = `✅ **Təbrik edirik, ${name}!**\nHesabınız təsdiqləndi.\n\n🎫 Kod: \`${promo_code}\`\n📉 Endirim: ${discount}\n💰 Komissiya: ${commission}%`;
        
        bot.sendMessage(chat_id, msg, { parse_mode: 'Markdown' });
        res.json({ success: true });
    } else {
        res.status(400).json({ success: false, message: "Bot aktiv deyil" });
    }
});

// 3. [POST] Sync Zamanı
app.post('/api/telegram-sync', (req, res) => {
    try {
        const { type, payload } = req.body;

        if (type === 'telegram_sync' && payload) {
            
            if (payload.partners && Array.isArray(payload.partners)) {
                payload.partners.forEach(p => {
                    if (p.telegram_chat_id) {
                        storage.partner_chats[p.id] = p.telegram_chat_id;
                    }
                });
                
                const activeChatIds = Object.values(storage.partner_chats);
                storage.pending_requests = storage.pending_requests.filter(u => !activeChatIds.includes(u.chat_id.toString()));
                
                save();
            }

            if (payload.latest_orders && bot) {
                payload.latest_orders.forEach(order => {
                    if (order.promo_code && !storage.processed_orders.includes(order.receipt_code)) {
                        
                        const promo = payload.promocodes ? payload.promocodes.find(pc => pc.code === order.promo_code) : null;
                        
                        if (promo) {
                            const partnerId = promo.partner_id;
                            const chatId = storage.partner_chats[partnerId];

                            if (chatId) {
                                const msg = `🎉 **Yeni Satış!**\n\n🎫 Kod: \`${order.promo_code}\`\n💵 Satış: **${order.grand_total} ₼**\n⏰ Saat: ${order.time}`;
                                bot.sendMessage(chatId, msg, { parse_mode: 'Markdown' }).catch(e => console.error("Send error:", e.message));
                                storage.processed_orders.push(order.receipt_code);
                            }
                        }
                    }
                });
                if (storage.processed_orders.length > 500) storage.processed_orders = storage.processed_orders.slice(-500);
                save();
            }
        }

        res.json({ success: true });

    } catch (e) {
        console.error(e);
        res.status(500).json({ success: false, error: e.message });
    }
});

// Serveri başlat
app.listen(PORT, () => {
    console.log(`🚀 Telegram API Körpüsü aktivdir: Port ${PORT}`);
});