const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const bodyParser = require('body-parser');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
const server = http.createServer(app);
const io = new Server(server, { cors: { origin: "*" } });

// Böyük həcmli data (satış siyahısı) gələ bilər deyə limit artırılır
app.use(cors());
app.use(bodyParser.json({ limit: '50mb' }));
app.use(bodyParser.urlencoded({ limit: '50mb', extended: true }));

// 1. Canlı Monitorinq Səhifəsi (Dashboard)
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'dashboard.html'));
});

// 2. Məlumat Qəbulu (Laravel bura göndərəcək)
// URL: http://server-ip:3000/api/report
app.post('/api/report', (req, res) => {
    try {
        const data = req.body;
        
        // Konsola yazırıq ki, gəldiyini görək
        console.log(`📡 Yeni Məlumat Gəldi: ${data.type} - ${new Date().toLocaleTimeString()}`);

        // Canlı Ekrana (Brauzerə) ötürürük
        io.emit('live_update', {
            type: data.type, // 'full_report' (tam paket)
            payload: data.payload,
            time: new Date().toLocaleTimeString()
        });

        // (İstəyə bağlı) Tarixçə itməsin deyə sadə bir fayla yazırıq (JSON Log)
        const logEntry = JSON.stringify({ time: new Date(), ...data }) + "\n";
        fs.appendFile('history.log', logEntry, (err) => {
            if (err) console.error("Log xətası:", err);
        });

        res.json({ status: true, message: 'Server: Məlumat qəbul edildi və ekrana ötürüldü!' });

    } catch (error) {
        console.error("Server Xətası:", error);
        res.status(500).json({ status: false, message: 'Server Xətası: ' + error.message });
    }
});

// Socket.IO Bağlantı hadisələri
io.on('connection', (socket) => {
    console.log('⚡ Yeni müştəri qoşuldu (Dashboard açıqdır)');
    
    socket.on('disconnect', () => {
        console.log('❌ Müştəri ayrıldı');
    });
});

// Serveri başladırıq
const PORT = 3000;
server.listen(PORT, () => {
    console.log(`🚀 Monitorinq Serveri İşləyir: http://localhost:${PORT}`);
});