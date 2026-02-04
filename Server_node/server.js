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

// --- HTML ŞABLONLAR ---
const loginHTML = `<!DOCTYPE html><html lang="az"><head><meta charset="UTF-8"><title>Giriş</title><script src="https://cdn.tailwindcss.com"></script></head><body class="bg-slate-900 h-screen flex items-center justify-center"><form action="login" method="POST" class="bg-slate-800 p-8 rounded-xl w-96 border border-slate-700"><h1 class="text-white text-2xl mb-6 font-bold text-center">Admin Girişi</h1><input name="username" placeholder="İstifadəçi" class="w-full mb-4 p-3 rounded bg-slate-900 text-white"><input type="password" name="password" placeholder="Şifrə" class="w-full mb-6 p-3 rounded bg-slate-900 text-white"><button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition">Daxil Ol</button></form></body></html>`;

// (Dashboard HTML kodu olduğu kimi qalır - Yuxarıdakı uzun HTML-i bura daxil edin)
const dashboardHTML = `
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <title>Monitor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.socket.io/4.7.4/socket.io.min.js"></script>
    <style>
        body { background-color: #0f172a; color: #e2e8f0; font-family: sans-serif; overflow: hidden; }
        .sidebar { width: 260px; background: #1e293b; height: 100vh; position: fixed; left: 0; top: 0; border-right: 1px solid #334155; }
        .content { margin-left: 260px; padding: 20px; height: 100vh; overflow-y: auto; }
        .stat-card { background: #1e293b; border-radius: 12px; padding: 20px; border: 1px solid #334155; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="p-6 text-xl font-bold text-white">RJ POS Monitor</div>
        <div class="p-4"><a href="logout" class="text-red-400 text-sm">ÇIXIŞ</a></div>
    </div>
    <div class="content">
        <h2 class="text-2xl font-bold text-white mb-6">Canlı Monitorinq</h2>
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="stat-card border-l-4 border-blue-500"><h3>Satış</h3><p id="stat-sales" class="text-2xl font-bold text-white">0 ₼</p></div>
            <div class="stat-card border-l-4 border-green-500"><h3>Mənfəət</h3><p id="stat-profit" class="text-2xl font-bold text-green-400">0 ₼</p></div>
        </div>
        <div id="feed" class="text-gray-400">Son yenilənmə: <span id="time">-</span></div>
    </div>
    <script>
        const socket = io({ path: '/monitor/socket.io' });
        socket.on('connect', () => console.log('Connected'));
        socket.on('live_update', (data) => {
            if(data.type === 'full_report') {
                const s = data.payload.stats;
                document.getElementById('stat-sales').innerText = s.today_sales + ' ₼';
                document.getElementById('stat-profit').innerText = s.today_profit + ' ₼';
                document.getElementById('time').innerText = data.time;
            }
        });
    </script>
</body>
</html>
`;

// --- ROUTES ---
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