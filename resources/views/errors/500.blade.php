<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Lỗi hệ thống</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <main class="login-page">
        <section class="panel login-panel">
            <h1 class="page-title">500 · Lỗi hệ thống</h1>
            <p class="page-subtitle field-gap">Hệ thống chưa thể xử lý yêu cầu. Vui lòng thử lại sau.</p><a
                class="btn btn-primary" href="{{ url()->previous() }}">Thử lại</a>
        </section>
    </main>
</body>

</html>
