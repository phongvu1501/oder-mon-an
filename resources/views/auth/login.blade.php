<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Đăng nhập · {{ config('restaurant.name') }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <main class="login-page">
        <section class="panel login-panel">
            <div class="brand login-brand"><span class="brand-mark"><i
                        data-lucide="utensils"></i></span>{{ config('restaurant.name') }}</div>
            <h1 class="page-title">Đăng nhập nhân viên</h1>
            <p class="page-subtitle login-subtitle">Sử dụng tài khoản được quản lý cấp.</p>
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
                @endif @if ($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif
                <form method="post" action="{{ route('login.store') }}">@csrf<div class="form-group field-gap"><label
                            class="form-label" for="email">Email <span class="required">*</span></label><input
                            class="form-control" id="email" name="email" type="email"
                            value="{{ old('email') }}" required autofocus autocomplete="username"></div>
                    <div class="form-group field-gap"><label class="form-label" for="password">Mật khẩu <span
                                class="required">*</span></label><input class="form-control" id="password"
                            name="password" type="password" required autocomplete="current-password"></div><button
                        class="btn btn-primary btn-block" type="submit"><i data-lucide="log-in"></i> Đăng nhập</button>
                </form>
        </section>
    </main>
</body>

</html>
