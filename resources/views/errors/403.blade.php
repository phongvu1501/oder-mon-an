<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Không có quyền</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <main class="login-page">
        <section class="panel login-panel">
            <h1 class="page-title">403 · Không có quyền</h1>
            <p class="page-subtitle field-gap">Tài khoản không được phép truy cập chức năng này.</p><a
                class="btn btn-primary" href="{{ url()->previous() }}">Quay lại</a>
        </section>
    </main>
</body>

</html>
