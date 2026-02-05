<div align="center">

<h1>🛍️ RJ POS - Modern Sales & Warehouse Management System</h1>

<p>
A professional hybrid POS system built with Laravel and Node.js, featuring real-time monitoring and seamless Telegram integration.
</p>

<p>
<a href="https://laravel.com"><img src="https://www.google.com/search?q=https://img.shields.io/badge/Laravel-FF2D20%3Fstyle%3Dfor-the-badge%26logo%3Dlaravel%26logoColor%3Dwhite" alt="Laravel"></a>
<a href="https://nodejs.org"><img src="https://www.google.com/search?q=https://img.shields.io/badge/Node.js-339933%3Fstyle%3Dfor-the-badge%26logo%3Dnodedotjs%26logoColor%3Dwhite" alt="Node.js"></a>
<a href="https://socket.io"><img src="https://www.google.com/search?q=https://img.shields.io/badge/Socket.io-010101%3Fstyle%3Dfor-the-badge%26logo%3Dsocketdotio%26logoColor%3Dwhite" alt="Socket.io"></a>
<a href="https://tailwindcss.com"><img src="https://www.google.com/search?q=https://img.shields.io/badge/Tailwind_CSS-38B2AC%3Fstyle%3Dfor-the-badge%26logo%3Dtailwind-css%26logoColor%3Dwhite" alt="Tailwind CSS"></a>
</p>

</div>

🌟 Support & Donate

If this system helps your business, consider supporting the development!

<div align="center">

<a href="https://kofe.al/@ruhidjavadoff">
<img src="https://www.google.com/search?q=https://kofe.al/assets/img/kofeal-badge.png" height="50" alt="Support on Kofe.al">
</a>





<a href="https://www.google.com/search?q=https://www.paypal.com/paypalme/ruhidjavadoff">
<img src="https://img.shields.io/badge/Donate%20via-PayPal-00457C?style=for-the-badge&logo=paypal&logoColor=white" alt="Donate via PayPal" height="40">
</a>

</div>

🚀 Key Features

RJ POS is designed for speed and reliability, split into a Local Client (Store) and a Central Server (Monitor).

Module

Description

💻 Hybrid Mode

Continue sales offline. Data syncs automatically to the server when the internet connection is restored.

⚡ Live Monitoring

Real-time dashboard powered by Node.js & Socket.IO. Watch sales, stock levels, and profits instantly from anywhere.

📱 Telegram Bot

Partners receive instant notifications about sales made with their promocodes. Includes balance and report checks via bot.

📦 Warehouse & Batches

Advanced stock management using FIFO logic. Tracks individual product batches, cost prices, and expiration dates.

🤝 Partner System

Manage affiliates with custom Promocodes and Commission Rates (%). Calculates partner earnings automatically per sale.

🔄 Returns Management

Full refund system with receipt lookup. Automatically restores stock and adjusts daily financial reports.

📊 Smart Analytics

Detailed reports on Net Profit, Gross Revenue, Taxes, and Commission expenses with date filters.

🎟️ Lottery System

Generates unique 5-digit lottery codes for eligible sales automatically.

🛠 Tech Stack

Category

Technology

Backend (Store)

Laravel 11, MySQL

Backend (Monitor)

Node.js, Express, Socket.IO

Frontend

Blade, Alpine.js, Tailwind CSS

Integrations

Telegram Bot API, Excel Export (XLSX)

⚙️ Installation Guide

1. Local Store (Laravel)

git clone [https://github.com/ruhidjavadoff/rj-pos.git](https://github.com/ruhidjavadoff/rj-pos.git)
cd rj-pos
composer install
npm install && npm run build
php artisan migrate --seed
php artisan serve


2. Monitoring Server (Node.js)

cd node-sync-server
npm install
# Start Monitor (Port 3000)
pm2 start server.js --name "monitor"
# Start Telegram Bridge (Port 4000)
pm2 start telegramapi.js --name "telegram-api"


🌐 Plugins & Updates

Check out the official site for new plugins, updates, and documentation:

👉 pos.ruhidjavadov.site

📞 Contact

For custom integration or support:

<div align="center">

<a href="https://www.google.com/search?q=https://wa.me/994506636031">
<img src="https://www.google.com/search?q=https://img.shields.io/badge/WhatsApp-25D366%3Fstyle%3Dfor-the-badge%26logo%3Dwhatsapp%26logoColor%3Dwhite" alt="WhatsApp">
</a>

<a href="mailto:ruhidjavadoff@gmail.com">
<img src="https://www.google.com/search?q=https://img.shields.io/badge/Email-D14836%3Fstyle%3Dfor-the-badge%26logo%3Dgmail%26logoColor%3Dwhite" alt="Email">
</a>

</div>
