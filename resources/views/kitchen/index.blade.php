@extends('layouts.admin')

@section('title', 'Màn hình bếp')

@section('content')
    <x-ui.page-header title="Màn hình bếp" subtitle="Đơn được ưu tiên theo thời gian nhận trong từng công đoạn."
        eyebrow="Vận hành">
        <button class="btn btn-secondary" type="button" onclick="window.location.reload()"><i data-lucide="refresh-cw"></i>Làm
            mới</button>
    </x-ui.page-header>
    <div class="kitchen-grid">
        @foreach (['confirmed' => ['Mới nhận', 'inbox'], 'preparing' => ['Đang chuẩn bị', 'cooking-pot'], 'ready' => ['Sẵn sàng', 'bell-ring']] as $status => [$label, $icon])
            <section class="kitchen-column">
                <h2 class="section-title"><span class="actions"><i
                            data-lucide="{{ $icon }}"></i>{{ $label }}</span><span
                        class="badge badge-{{ $status }}">{{ ($orders[$status] ?? collect())->count() }}</span></h2>
                @forelse ($orders[$status] ?? [] as $order)
                    <article class="kitchen-ticket">
                        <div class="section-title"><a
                                href="{{ route('orders.show', $order) }}">{{ $order->order_code }}</a><strong>{{ $order->tableSession->restaurantTable->name }}</strong>
                        </div>
                        <div class="cell-meta">Nhận lúc {{ $order->created_at->format('H:i') }}</div>
                        <ul class="stat-list">
                            @foreach ($order->items as $item)
                                <li><span>{{ $item->product_name }} @if ($item->note)
                                            <small>· {{ $item->note }}</small>
                                        @endif
                                    </span>
                                    <strong>× {{ $item->quantity }}</strong>
                                </li>
                            @endforeach
                        </ul>
                        @can('orders.update')
                            @if ($status !== 'ready')
                                <form method="post" action="{{ route('kitchen.orders.update', $order) }}"
                                    style="margin-top:12px">@csrf @method('patch')<input type="hidden" name="status"
                                        value="{{ $status === 'confirmed' ? 'preparing' : 'ready' }}"><button
                                        class="btn btn-primary btn-block" type="submit"><i
                                            data-lucide="{{ $status === 'confirmed' ? 'flame' : 'bell' }}"></i>{{ $status === 'confirmed' ? 'Bắt đầu làm' : 'Báo sẵn sàng' }}</button>
                                </form>
                            @endif
                        @endcan
                    </article>
                @empty
                    <x-ui.empty-state icon="check-check" title="Không có đơn"
                        description="Khu vực này hiện đã được xử lý hết." />
                @endforelse
            </section>
        @endforeach
    </div>
@endsection
