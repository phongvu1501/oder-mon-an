@extends('layouts.customer') @section('title', 'Thanh toán') @section('content')<section class="panel" style="text-align:center">
    <h1 class="page-title">Thanh toán {{ number_format($payment->amount) }} đ</h1>
    <p class="page-subtitle">Nội dung: <strong>{{ $payment->payment_code }}</strong></p>
    @if ($qrSvg)
        <div style="width:min(100%,360px);margin:20px auto">{!! $qrSvg !!}</div>
    <div class="alert alert-success">Sau khi chuyển khoản, vui lòng báo nhân viên xác nhận.</div>@else<div
            class="alert alert-error" style="margin-top:20px">Quán chưa cấu hình tài khoản ngân hàng. Vui lòng thanh toán
            tại quầy.</div>
    @endif
    <a class="btn btn-secondary" href="{{ route('menu.status') }}">Quay lại hóa đơn</a>
</section>@endsection
