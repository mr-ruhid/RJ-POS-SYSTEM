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
    partners_info: {}, // { chat_id: { name, balance, id } }
    pending_requests: [],
    processed_orders: [],
    history: [] // SATIŞ TARİXÇƏSİ: [{ partner_id, commission, amount, date }]
};

if (fs.existsSync(DATA_FILE)) {
    try {
        const raw = JSON.parse(fs.readFileSync(DATA_FILE));
        storage = { ...storage, ...raw };
        if(!storage.partners_info) storage.partners_info = {};
        if(!storage.history) storage.history = [];
    } catch (e) { console.error("Data xətası:", e); }
}

function save() {
    fs.writeFileSync(DATA_FILE, JSON.stringify(storage, null, 2));
}

// --- MENYULAR ---
const mainMenu = {
    reply_markup: {
        keyboard: [
            ['📊 Hesabatlar', '💰 Balansım']
        ],
        resize_keyboard: true
    }
};

const reportMenu = {
    reply_markup: {
        keyboard: [
            ['📅 Günlük', '🗓 Həftəlik'],
            ['tj Aylıq', '📆 İllik'],
            ['Σ Ümumi', '🔙 Geri']
        ],
        resize_keyboard: true
    }
};

// --- BOT ---
let bot = null;
if (TELEGRAM_TOKEN) {
    try {
        bot = new TelegramBot(TELEGRAM_TOKEN, { polling: true });
        console.log("🤖 Telegram Bot (4000) Aktivdir");

        // 1. Start
        bot.onText(/\/start/, (msg) => {
            const chatId = msg.chat.id;
            const name = msg.from.first_name;

            const isRegistered = Object.values(storage.partner_chats).includes(chatId.toString()) || Object.values(storage.partner_chats).includes(chatId);

            if (isRegistered) {
                bot.sendMessage(chatId, `Salam, ${name}! ✅ Hesabınız aktivdir.`, mainMenu);
            } else {
                const opts = {
                    reply_markup: {
                        inline_keyboard: [
                            [{ text: "✅ Partnyorluğu Təsdiqlə", callback_data: 'confirm_reg' }],
                            [{ text: "❌ Ləğv et", callback_data: 'cancel_reg' }]
                        ]
                    }
                };
                bot.sendMessage(chatId, `Salam, ${name}! 👋\nRJ POS sisteminə qoşulmaq üçün təsdiqləyin.`, opts);
            }
        });

        // 2. Callback
        bot.on('callback_query', (query) => {
            const chatId = query.message.chat.id;
            const msgId = query.message.message_id;

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
                    chat_id: chatId, message_id: msgId, parse_mode: 'Markdown'
                });
            } else if (query.data === 'cancel_reg') {
                bot.editMessageText("❌ Ləğv edildi.", { chat_id: chatId, message_id: msgId });
            }
        });

        // 3. Menyu Məntiqi (HESABATLAR)
        bot.on('message', (msg) => {
            const chatId = msg.chat.id;
            const text = msg.text;

            // Partnyor ID-sini tapırıq
            let partnerId = null;
            for (const [pid, cid] of Object.entries(storage.partner_chats)) {
                if (cid == chatId) { partnerId = pid; break; }
            }

            if (!partnerId && text !== '/start') {
                // Əgər qeydiyyatlı deyilsə reaksiya vermirik (və ya xəbərdarlıq edirik)
                return;
            }

            // --- BALANS ---
            if (text === '💰 Balansım') {
                const info = storage.partners_info[chatId];
                const balance = info ? info.balance : 0;
                bot.sendMessage(chatId, `💰 **Cari Balansınız:** ${balance} ₼\n_(Ödənişlər çıxıldıqdan sonra)_`, { parse_mode: 'Markdown' });
            }

            // --- HESABAT MENYUSU ---
            else if (text === '📊 Hesabatlar') {
                bot.sendMessage(chatId, "Zəhmət olmasa dövrü seçin:", reportMenu);
            }
            else if (text === '🔙 Geri') {
                bot.sendMessage(chatId, "Əsas menyu:", mainMenu);
            }

            // --- STATİSTİKA HESABLAMALARI ---
            else if (['📅 Günlük', '🗓 Həftəlik', 'tj Aylıq', '📆 İllik', 'Σ Ümumi'].includes(text)) {
                const now = new Date();
                let filterFn = () => false;
                let title = "";

                if (text === '📅 Günlük') {
                    title = "Bu Gün";
                    const todayStr = now.toISOString().split('T')[0]; // YYYY-MM-DD
                    filterFn = (item) => item.date.startsWith(todayStr);
                }
                else if (text === '🗓 Həftəlik') {
                    title = "Son 7 Gün";
                    const sevenDaysAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
                    filterFn = (item) => new Date(item.date) >= sevenDaysAgo;
                }
                else if (text === 'tj Aylıq') {
                    title = "Bu Ay";
                    const monthStr = now.toISOString().slice(0, 7); // YYYY-MM
                    filterFn = (item) => item.date.startsWith(monthStr);
                }
                else if (text === '📆 İllik') {
                    title = "Bu İl";
                    const yearStr = now.getFullYear().toString();
                    filterFn = (item) => item.date.startsWith(yearStr);
                }
                else if (text === 'Σ Ümumi') {
                    title = "Bütün Dövr";
                    filterFn = () => true;
                }

                // Hesablama
                const myHistory = storage.history.filter(h => h.partner_id == partnerId);
                const filtered = myHistory.filter(filterFn);

                const totalCommission = filtered.reduce((sum, item) => sum + parseFloat(item.commission || 0), 0);
                const totalSales = filtered.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
                const count = filtered.length;

                const reportMsg = `
📊 **${title} üzrə Hesabat**

✅ Satış Sayı: ${count}
💵 Ümumi Satış: ${totalSales.toFixed(2)} ₼
💰 **Sizin Qazanc:** ${totalCommission.toFixed(2)} ₼
                `;
                bot.sendMessage(chatId, reportMsg, { parse_mode: 'Markdown' });
            }
        });

    } catch (e) { console.error("Bot xətası:", e); }
}

// --- API ---

app.get('/', (req, res) => res.send('Telegram Bridge API (Port 4000) is running...'));

app.get('/api/pending-partners', (req, res) => {
    res.json(storage.pending_requests);
});

app.post('/api/partner-welcome', (req, res) => {
    const { chat_id, name, promo_code, discount, commission } = req.body;
    if (bot && chat_id) {
        storage.pending_requests = storage.pending_requests.filter(u => u.chat_id != chat_id);

        // Info yaddaşa alırıq
        storage.partners_info[chat_id] = { name: name, balance: 0 };
        save();

        const msg = `✅ **Təbrik edirik, ${name}!**\nHesabınız təsdiqləndi.\n\n🎫 Kod: \`${promo_code}\`\n💰 Komissiya: ${commission}%\n📉 Müştəri Endirimi: ${discount}`;
        bot.sendMessage(chat_id, msg, { parse_mode: 'Markdown', ...mainMenu });
        res.json({ success: true });
    } else {
        res.status(400).json({ success: false });
    }
});

app.post('/api/telegram-sync', (req, res) => {
    try {
        const { type, payload } = req.body;

        if (type === 'telegram_sync' && payload) {

            // A. Partnyor Məlumatlarını Yenilə
            if (payload.partners) {
                payload.partners.forEach(p => {
                    if (p.telegram_chat_id) {
                        storage.partner_chats[p.id] = p.telegram_chat_id;

                        // Balansı yeniləyirik
                        storage.partners_info[p.telegram_chat_id] = {
                            id: p.id,
                            name: p.name,
                            balance: p.balance
                        };
                    }
                });
                const activeChatIds = Object.values(storage.partner_chats);
                storage.pending_requests = storage.pending_requests.filter(u => !activeChatIds.includes(u.chat_id.toString()));
                save();
            }

            // B. Satış Bildirişi və Tarixçəyə Yazma
            if (payload.latest_orders && bot) {
                payload.latest_orders.forEach(order => {
                    if (order.promo_code && !storage.processed_orders.includes(order.receipt_code)) {

                        const promo = payload.promocodes ? payload.promocodes.find(pc => pc.code === order.promo_code) : null;

                        if (promo) {
                            const partnerId = promo.partner_id;
                            const chatId = storage.partner_chats[partnerId];

                            if (chatId) {
                                const commission = order.calculated_commission || 0;
                                const currentBalance = storage.partners_info[chatId]?.balance || 0;

                                // 1. Tarixçəyə yazırıq (Hesabatlar üçün)
                                // Tarix formatını YYYY-MM-DDT... kimi saxlayırıq ki, asan filterlənsin
                                const now = new Date();
                                storage.history.push({
                                    partner_id: partnerId,
                                    receipt: order.receipt_code,
                                    amount: order.grand_total,
                                    commission: commission,
                                    date: now.toISOString() // Tam tarix
                                });

                                // 2. Mesaj Göndəririk
                                const msg = `
🎉 **Yeni Satış!**

🎫 Kod: \`${order.promo_code}\`
💵 Satış: ${order.grand_total} ₼
💰 **Qazanc:** +${commission} ₼

🏦 Cari Balans: ${currentBalance} ₼
⏰ Saat: ${order.time}
                                `;

                                bot.sendMessage(chatId, msg, { parse_mode: 'Markdown' }).catch(e => console.error(e.message));
                                storage.processed_orders.push(order.receipt_code);
                            }
                        }
                    }
                });
                // Təmizlik (Tarixçəni çox şişirtməmək üçün)
                if (storage.processed_orders.length > 1000) storage.processed_orders = storage.processed_orders.slice(-1000);
                // Tarixçəni sonsuz saxlamaq olar, amma fayl böyüyəcək. Hələlik limit qoymuram.

                save();
            }
        }
        res.json({ success: true });
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

app.listen(PORT, () => console.log(`Telegram API: ${PORT}`));
