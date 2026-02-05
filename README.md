<div align="center">

<h1>🛍️ RJ POS - Müasir Satış və Anbar İdarəetmə Sistemi</h1>

<p>
Laravel və Node.js üzərində qurulmuş, Hibrid (Online/Offline) rejimdə işləyən, Telegram inteqrasiyalı peşəkar POS sistemi.
</p>

<p>
<a href="https://laravel.com"><img src="https://www.google.com/search?q=https://img.shields.io/badge/Laravel-FF2D20%3Fstyle%3Dfor-the-badge%26logo%3Dlaravel%26logoColor%3Dwhite" alt="Laravel"></a>
<a href="https://nodejs.org"><img src="https://www.google.com/search?q=https://img.shields.io/badge/Node.js-339933%3Fstyle%3Dfor-the-badge%26logo%3Dnodedotjs%26logoColor%3Dwhite" alt="Node.js"></a>
<a href="https://socket.io"><img src="https://www.google.com/search?q=https://img.shields.io/badge/Socket.io-010101%3Fstyle%3Dfor-the-badge%26logo%3Dsocketdotio%26logoColor%3Dwhite" alt="Socket.io"></a>
<a href="https://tailwindcss.com"><img src="https://www.google.com/search?q=https://img.shields.io/badge/Tailwind_CSS-38B2AC%3Fstyle%3Dfor-the-badge%26logo%3Dtailwind-css%26logoColor%3Dwhite" alt="Tailwind CSS"></a>
</p>

<a href="https://www.google.com/search?q=https://pos.ruhidjavadov.site"><strong>🌐 Yeniliklər və Pluginlər (Rəsmi Sayt)</strong></a>

</div>

🚀 Layihə Haqqında

RJ POS, kiçik və orta sahibkarlar üçün nəzərdə tutulmuş sürətli və çevik satış sistemidir. Sistem Lokal (Mağaza) və Server (Monitorinq) olmaqla iki hissədən ibarətdir və onlar arasında real vaxt rejimində (Real-time) məlumat mübadiləsi aparır.

🔥 Əsas Özəlliklər

Modul

Təsvir

💻 Hibrid Rejim

İnternet olmadıqda belə satış etməyə davam edin. İnternet gələndə məlumatlar serverə avtomatik yüklənir.

⚡ Canlı Monitorinq

Node.js və Socket.IO sayəsində satışları, anbarı və qazancı anlıq olaraq mərkəzi ekrandan izləyin.

📱 Telegram İnteqrasiyası

Partnyorlar öz satışlarını və qazanclarını Telegram bot vasitəsilə anlıq izləyə bilirlər.

📦 Anbar & Partiyalar

Məhsulların partiya (batch) ilə qəbulu, maya dəyəri və son istifadə tarixi izlənməsi (FIFO).

🤝 Partnyor Sistemi

Promokodlar vasitəsilə partnyorluq, faizlə komissiya hesablanması və balans idarəetməsi.

🔄 Qaytarma Sistemi

Satılan məhsulların çek nömrəsi ilə asan qaytarılması və stoka bərpası.

📊 Dəqiq Hesabatlar

Gəlir, Xalis Mənfəət, Maya Dəyəri, Vergi və Komissiya xərclərinin detallı analizi.

🎟️ Lotereya

Satış zamanı avtomatik 5 rəqəmli unikal lotereya kodu verilməsi.

☕ Dəstək Ol (Donate)

Əgər bu layihə işinizə yaradısa və inkişafına dəstək olmaq istəyirsinizsə, mənə bir kofe ısmarlaya bilərsiniz! ☕

<div align="center">

<a href="https://kofe.al/@ruhidjavadoff">
<img src="https://www.google.com/search?q=https://kofe.al/assets/img/kofeal-badge.png" height="50" alt="Kofe.al ilə dəstək ol">
</a>





<a href="mailto:ruhidjavadoff@gmail.com">
<img src="https://www.google.com/search?q=https://img.shields.io/badge/PayPal-00457C%3Fstyle%3Dfor-the-badge%26logo%3Dpaypal%26logoColor%3Dwhite" alt="PayPal">
</a>





<b>PayPal:</b> <code>ruhidjavadoff@gmail.com</code>

</div>

🛠️ Quraşdırma (Qısa)

1. Lokal Mağaza (Laravel)

git clone [https://github.com/ruhidjavadoff/rj-pos.git](https://github.com/ruhidjavadoff/rj-pos.git)
cd rj-pos
composer install
npm install && npm run build
php artisan migrate --seed
php artisan serve


2. Monitorinq Serveri (Node.js)

cd node-sync-server
npm install
pm2 start server.js --name "monitor"
pm2 start telegramapi.js --name "telegram-api"


Qeyd: Ətraflı dokumentasiya və pluginlər üçün pos.ruhidjavadov.site ünvanına daxil olun.

📞 Əlaqə

Layihə ilə bağlı suallarınız və ya təklifləriniz üçün əlaqə saxlaya bilərsiniz:

<div align="center">

<a href="https://www.google.com/search?q=https://wa.me/994506636031">
<img src="https://www.google.com/search?q=https://img.shields.io/badge/WhatsApp-25D366%3Fstyle%3Dfor-the-badge%26logo%3Dwhatsapp%26logoColor%3Dwhite" alt="WhatsApp">
</a>

<a href="mailto:ruhidjavadoff@gmail.com">
<img src="https://www.google.com/search?q=https://img.shields.io/badge/Email-D14836%3Fstyle%3Dfor-the-badge%26logo%3Dgmail%26logoColor%3Dwhite" alt="Email">
</a>

</div>
