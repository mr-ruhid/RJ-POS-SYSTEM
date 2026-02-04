require('dotenv').config();
const express = require('express');
const bodyParser = require('body-parser');
const TelegramBot = require('node-telegram-bot-api');
const fs = require('fs');
const cors = require('cors');

// --- AYARLAR ---
const PORT = process.env.TG_API_PORT || 4000;
const TELEGRAM_TOKEN = process.env.TELEGRAM_BOT_TOKEN;

const app = express();
app.use(cors());
app.use(bodyParser.json({ limit: '50mb' }));

// --- YADDAŞ ---
const DATA_FILE = 'telegram_bridge_data.json';
let storage = {
    partner_chats: {}, // { partner_id: chat_id }
    pending_requests: [],
    processed_orders: []
};

if (fs.existsSync(DATA_FILE)) {
    try {
        const raw = JSON.parse(fs.readFileSync(DATA_FILE));
        storage = { ...storage, ...raw };
    } catch (e) { console.error("Data xətası:", e); }
}

function save() {
    fs.writeFileSync(DATA_FILE, JSON.stringify(storage, null, 2));
}

// --- BOT ---
let bot = null;
if (TELEGRAM_TOKEN) {
    try {
        bot = new TelegramBot(TELEGRAM_TOKEN, { polling: true });
        console.log("🤖 Telegram Bot (4000) Aktivdir");

        // [YENİ] SIFIRLAMA KOMANDASI (TEST ÜÇÜN)
        bot.onText(/\/reset/, (msg) => {
            const chatId = msg.chat.id;

            // 1. Gözləyən siyahıdan silirik
            storage.pending_requests = storage.pending_requests.filter(u => u.chat_id != chatId);

            // 2. Aktiv partnyor siyahısından silirik (Key-i tapıb silirik)
            for (const [partnerId, id] of Object.entries(storage.partner_chats)) {
                if (id == chatId) {
                    delete storage.partner_chats[partnerId];
                }
            }

            save();
            bot.sendMessage(chatId, "🔄 Hesabınız serverdən silindi (Reset). İndi yenidən /start yaza bilərsiniz.");
        });

        // Start
        bot.onText(/\/start/, (msg) => {
            const chatId = msg.chat.id;

            // Yoxlayırıq: Bu adam artıq varmı?
            const isRegistered = Object.values(storage.partner_chats).includes(chatId.toString()) || Object.values(storage.partner_chats).includes(chatId);
            const isPending = storage.pending_requests.find(u => u.chat_id == chatId);

            if (isRegistered) {
                bot.sendMessage(chatId, `✅ Siz artıq sistemdə varsınız. Hesabatlara baxa bilərsiniz.`);
            } else if (isPending) {
                bot.sendMessage(chatId, `⏳ Sorğunuz artıq göndərilib. Admin təsdiqini gözləyin.\n(Sıfırlamaq üçün /reset yazın)`);
            } else {
                const opts = {
                    reply_markup: {
                        inline_keyboard: [
                            [{ text: "✅ Partnyorluğu Təsdiqlə", callback_data: 'confirm_reg' }],
                            [{ text: "❌ Ləğv et", callback_data: 'cancel_reg' }]
                        ]
                    }
                };
                bot.sendMessage(chatId, `Salam, ${msg.from.first_name}! 👋\nSistemə qoşulmaq üçün təsdiqləyin.`, opts);
            }
        });

        bot.on('callback_query', (query) => {
            const chatId = query.message.chat.id;
            if (query.data === 'confirm_reg') {
                const exists = storage.pending_requests.find(u => u.chat_id == chatId);
                const isLinked = Object.values(storage.partner_chats).includes(chatId.toString());

                if (!exists && !isLinked) {
                    storage.pending_requests.push({
                        chat_id: chatId,
                        name: query.from.first_name,
                        username: query.from.username || 'yoxdur',
                        date: new Date().toLocaleString()
                    });
                    save();
                }
                bot.editMessageText(`✅ Sorğunuz qəbul edildi!\n🆔 ID: \`${chatId}\`\nAdmin təsdiqini gözləyin.`, {
                    chat_id: chatId, message_id: query.message.message_id, parse_mode: 'Markdown'
                });
            } else if (query.data === 'cancel_reg') {
                bot.editMessageText("❌ Ləğv edildi. Yenidən başlamaq üçün /start yazın.", { chat_id: chatId, message_id: query.message.message_id });
            }
        });

    } catch (e) { console.error("Bot xətası:", e); }
}

// --- API ---

// 1. Gözləyən istifadəçiləri Mağazaya ver
app.get('/api/pending-partners', (req, res) => {
    res.json(storage.pending_requests);
});

// 2. Mağaza partnyoru yaratdı -> Mesaj at
app.post('/api/partner-welcome', (req, res) => {
    const { chat_id, name, promo_code, discount, commission } = req.body;
    if (bot && chat_id) {
        storage.pending_requests = storage.pending_requests.filter(u => u.chat_id != chat_id);
        save();

        const msg = `✅ **Təbrik edirik, ${name}!**\n\n🎫 Kod: \`${promo_code}\`\n💰 Komissiya: ${commission}%\n📉 Müştəri Endirimi: ${discount}`;
        bot.sendMessage(chat_id, msg, { parse_mode: 'Markdown' });
        res.json({ success: true });
    } else {
        res.status(400).json({ success: false });
    }
});

// 3. SYNC (Satış Bildirişi)
app.post('/api/telegram-sync', (req, res) => {
    try {
        const { type, payload } = req.body;

        if (type === 'telegram_sync' && payload) {

            // A. Partnyor Siyahısını Yenilə
            if (payload.partners) {
                payload.partners.forEach(p => {
                    if (p.telegram_chat_id) {
                        storage.partner_chats[p.id] = p.telegram_chat_id;
                    }
                });

                const activeChatIds = Object.values(storage.partner_chats);
                storage.pending_requests = storage.pending_requests.filter(u => !activeChatIds.includes(u.chat_id.toString()));
                save();
            }

            // B. Satış Bildirişi
            if (payload.latest_orders && bot) {
                payload.latest_orders.forEach(order => {
                    if (order.promo_code && !storage.processed_orders.includes(order.receipt_code)) {

                        // SyncService-də "partner_id" göndərdiyimiz üçün birbaşa tapırıq
                        const partnerId = order.partner_id;
                        const chatId = storage.partner_chats[partnerId];

                        if (chatId) {
                            const earnings = order.calculated_commission || 0;
                            const msg = `
🎉 **Yeni Satış!**

🎫 Kod: \`${order.promo_code}\`
💵 Satış: ${order.grand_total} ₼
💰 **Qazancınız:** +${earnings} ₼

⏰ Saat: ${order.time}
                            `;

                            bot.sendMessage(chatId, msg, { parse_mode: 'Markdown' }).catch(e => console.error(e.message));
                            storage.processed_orders.push(order.receipt_code);
                        }
                    }
                });
                if (storage.processed_orders.length > 500) storage.processed_orders = storage.processed_orders.slice(-500);
                save();
            }
        }

        res.json({ success: true });

    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

app.listen(PORT, () => console.log(`Telegram API: ${PORT}`));
