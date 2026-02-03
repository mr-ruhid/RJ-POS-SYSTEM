<!DOCTYPE html>
<html>
<head>
    <title>Xəta Bildirişi</title>
</head>
<body style="font-family: Arial, sans-serif; padding: 20px;">

    <h2 style="color: #d9534f;">Yeni Xəta Bildirişi</h2>

    <p><strong>Müştəri/İstifadəçi:</strong> {{ $data['user_name'] }} (ID: {{ $data['user_id'] }})</p>
    <p><strong>Tarix:</strong> {{ $data['time'] }}</p>
    <p><strong>URL:</strong> {{ $data['url'] }}</p>

    <hr>

    <h3>Xətanın Təsviri:</h3>
    <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #d9534f;">
        {!! nl2br(e($data['description'])) !!}
    </div>

    <hr>

    <h3>Sistem Məlumatları (Log):</h3>
    <ul>
        <li><strong>IP Ünvanı:</strong> {{ $data['ip'] }}</li>
        <li><strong>User Agent:</strong> {{ $data['user_agent'] }}</li>
    </ul>

    <p style="font-size: 12px; color: gray;">Bu mesaj RJ POS sistemindən avtomatik göndərilib.</p>
</body>
</html>
