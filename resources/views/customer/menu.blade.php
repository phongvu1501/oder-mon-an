@extends('layouts.customer')

@section('title', 'Thực đơn · ' . $table->name)
@section('table-label', $table->name . ' · ' . $tableSession->session_code)

@section('content')
    <div data-customer-menu>
        <header class="customer-intro">
            <div>
                <h1>Hôm nay bạn dùng gì?</h1>
                <p>Chọn món, kiểm tra giỏ hàng và gửi thẳng tới bếp.</p>
            </div>
            <span class="table-pill"><i data-lucide="map-pin"></i>{{ $table->name }}</span>
        </header>

        @if ($categories->isNotEmpty())
            <div class="category-nav-wrap">
                <nav class="category-nav" aria-label="Danh mục món ăn">
                    @foreach ($categories as $category)
                        <a class="category-chip {{ $loop->first ? 'active' : '' }}" href="#category-{{ $category->id }}"
                            data-category-link>{{ $category->name }}</a>
                    @endforeach
                </nav>
            </div>
        @endif

        @forelse ($categories as $category)
            <section class="menu-section" id="category-{{ $category->id }}">
                <div class="menu-section-heading">
                    <h2>{{ $category->name }}</h2><span>{{ $category->products->count() }} món</span>
                </div>
                <div class="menu-grid">
                    @foreach ($category->products as $product)
                        <article class="menu-item">
                            <div class="menu-image-wrap">
                                @if ($product->image)
                                    <img class="menu-image" src="{{ Storage::url($product->image) }}"
                                        alt="{{ $product->name }}" loading="lazy"
                                        onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                                    <span class="menu-placeholder" hidden><i data-lucide="image-off"></i></span>
                                @else
                                    <span class="menu-placeholder"><i data-lucide="utensils"></i></span>
                                @endif
                                @unless ($product->is_available)
                                    <span class="sold-out">Hết món</span>
                                @endunless
                            </div>
                            <div class="menu-info">
                                <h3 class="menu-name">{{ $product->name }}</h3>
                                <p class="menu-desc">{{ $product->description ?: 'Món ngon được chuẩn bị khi bạn đặt.' }}
                                </p>
                                <div class="menu-card-footer">
                                    <span class="menu-price">{{ number_format($product->price) }} đ</span>
                                    <span class="menu-add-wrap">
                                        <span class="product-quantity" data-product-quantity="{{ $product->id }}"
                                            hidden>0</span>
                                        <button class="add-btn" type="button" data-add="{{ $product->id }}"
                                            aria-label="Thêm {{ $product->name }}" @disabled(!$product->is_available)><i
                                                data-lucide="plus"></i></button>
                                    </span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <section class="panel"><x-ui.empty-state icon="utensils" title="Chưa có món đang phục vụ"
                    description="Vui lòng hỏi nhân viên để được hỗ trợ." /></section>
        @endforelse

        <div class="cart-bar" id="cart-bar" hidden>
            <div class="cart-summary"><small><span data-cart-count>0</span> món đã chọn</small><strong data-cart-total>0
                    đ</strong></div>
            <button class="btn" type="button" id="open-cart">Xem giỏ hàng<i data-lucide="shopping-bag"></i></button>
        </div>

        <div class="cart-drawer" id="cart-drawer" aria-hidden="true">
            <section class="cart-sheet" role="dialog" aria-modal="true" aria-labelledby="cart-title">
                <header class="cart-sheet-header">
                    <div>
                        <h2 id="cart-title">Giỏ hàng</h2>
                        <p><span data-cart-count>0</span> món cho {{ $table->name }}</p>
                    </div><button class="btn btn-ghost icon-btn" id="close-cart" type="button"
                        aria-label="Đóng giỏ hàng"><i data-lucide="x"></i></button>
                </header>
                <div class="cart-content">
                    <div class="cart-edit">
                        <div id="cart-list"></div>
                        <div id="cart-empty" hidden><x-ui.empty-state icon="shopping-bag" title="Giỏ hàng đang trống"
                                description="Chọn món từ thực đơn để bắt đầu." /></div>
                        <div class="cart-note"><label class="form-label" for="customer-note">Ghi chú cho nhà bếp</label>
                            <textarea class="form-control" id="customer-note" maxlength="500" placeholder="Ví dụ: ít cay, không hành..."></textarea>
                        </div>
                    </div>
                    <div class="cart-confirm">
                        <div class="section-heading">
                            <div>
                                <h2>Xác nhận đơn hàng</h2>
                                <p>{{ $table->name }} · kiểm tra trước khi gửi tới bếp.</p>
                            </div><i data-lucide="clipboard-check"></i>
                        </div>
                        <ul class="confirm-order-list" id="confirm-order-list"></ul>
                    </div>
                </div>
                <footer class="cart-sheet-footer">
                    <div class="cart-edit">
                        <div class="bill-total"><span>Tạm tính</span><span data-cart-total>0 đ</span></div><button
                            class="btn btn-primary btn-block" type="button" id="cart-next">Tiếp tục xác nhận<i
                                data-lucide="arrow-right"></i></button>
                    </div>
                    <div class="cart-confirm">
                        <div class="bill-total"><span>Tổng tiền</span><span data-cart-total>0 đ</span></div>
                        <div class="cart-error" id="cart-error" role="alert"></div>
                        <div class="confirm-actions"><button class="btn btn-secondary" type="button" id="cart-back"><i
                                    data-lucide="arrow-left"></i>Quay lại</button><button class="btn btn-primary"
                                type="button" id="submit-order"><i data-lucide="send"></i>Xác nhận đặt món</button></div>
                    </div>
                </footer>
            </section>
        </div>

        @php
            $menuData = [
                'cartKey' => 'oder-cart-' . $tableSession->id,
                'endpoint' => route('menu.orders.store'),
                'statusUrl' => route('menu.status'),
                'idempotencyKey' => $idempotencyKey,
                'products' => $categories->flatMap->products->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'unit' => $product->unit,
                    'available' => (bool) $product->is_available,
                ])->values(),
            ];
        @endphp
        <script type="application/json" id="customer-menu-data">@json($menuData)</script>
    </div>
@endsection
