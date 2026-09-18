<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Chưa đăng nhập</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <main class="login-page">
        <section class="panel login-panel">
            <h1 class="page-title">401 · Chưa đăng nhập</h1>
            <p class="page-subtitle field-gap">Phiên đăng nhập không hợp lệ hoặc đã hết hạn.</p><a class="btn btn-primary"
                href="{{ route('login') }}">Đăng nhập lại</a>
        </section>
    </main>
</body>

</html>
