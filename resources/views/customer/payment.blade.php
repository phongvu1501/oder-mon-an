@extends('layouts.customer')

@section('title', 'Thanh toán')

@section('content')
    <section class="panel" style="max-width:620px;margin:18px auto;text-align:center">
        <span class="empty-state__icon" style="margin:0 auto 14px"><i data-lucide="scan-line"></i></span>
        <h1 class="page-title">Thanh toán {{ number_format($payment->amount) }} đ</h1>
        <p class="page-subtitle" style="margin-right:auto;margin-left:auto">Nội dung chuyển khoản:
            <strong>{{ $payment->payment_code }}</strong></p>
        @if ($qrSvg)
            <div class="payment-qr">{!! $qrSvg !!}</div>
            <div class="alert alert-success">Sau khi chuyển khoản, vui lòng báo nhân viên xác nhận giao dịch.</div>
        @else
            <div class="alert alert-error" style="margin-top:20px">Nhà hàng chưa cấu hình tài khoản ngân hàng. Vui lòng
                thanh toán tại quầy.</div>
        @endif
        <a class="btn btn-secondary" href="{{ route('menu.status') }}"><i data-lucide="arrow-left"></i>Quay lại hóa đơn</a>
    </section>
@endsection
