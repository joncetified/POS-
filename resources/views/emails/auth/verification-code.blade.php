<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kode Verifikasi</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827;">
    <p>Halo {{ $user->name }},</p>
    <p>Gunakan kode berikut untuk menyelesaikan register akun {{ config('app.name') }}.</p>
    <p style="font-size: 28px; font-weight: 700; letter-spacing: 6px;">{{ $code }}</p>
    <p>Kode ini berlaku 10 menit. Abaikan email ini kalau kamu tidak membuat akun.</p>
</body>
</html>
