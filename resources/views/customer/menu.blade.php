@extends('layouts.customer') @section('title', 'Menu · ' . $table->name) @section('table-label', $table->name . ' · ' .
$tableSession->session_code) @section('content')
    <nav class="category-nav">
        @foreach ($categories as $category)
            <a class="category-chip" href="#category-{{ $category->id }}">{{ $category->name }}</a>
        @endforeach
    </nav>
    @forelse($categories as $category)
        <section class="menu-section" id="category-{{ $category->id }}">
            <h2>{{ $category->name }}</h2>
            <div class="menu-grid">
                @foreach ($category->products as $product)
                    <article class="menu-item">
                        @if ($product->image)
                            <img class="menu-image" src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                            loading="lazy">@else<div class="menu-image menu-placeholder">
                                {{ mb_strtoupper(mb_substr($product->name, 0, 1)) }}</div>
                        @endif
                        <div class="menu-info">
                            <h3 class="menu-name">{{ $product->name }}</h3>
                            <p class="menu-desc">{{ $product->description }}</p>
                            <div class="menu-price">{{ number_format($product->price) }} đ</div><button class="add-btn"
                                type="button" data-add="{{ $product->id }}" aria-label="Thêm {{ $product->name }}"><i
                                    data-lucide="plus"></i></button>
                        </div>
                    </article>
                @endforeach
            </div>
    </section>@empty<div class="empty">Hiện chưa có món đang phục vụ.</div>
    @endforelse
    <button class="cart-bar" type="button" id="open-cart"><span><strong id="cart-count">0 món</strong><br><small>Xem giỏ
                hàng</small></span><strong id="cart-total">0 đ</strong></button>
    <div class="cart-drawer" id="cart-drawer">
        <section class="cart-sheet" role="dialog" aria-modal="true" aria-label="Giỏ hàng">
            <div class="page-header">
                <h2 class="page-title">Giỏ hàng</h2><button class="btn btn-secondary icon-btn" id="close-cart"
                    type="button" aria-label="Đóng"><i data-lucide="x"></i></button>
            </div>
            <div id="cart-items"></div><label class="form-label" for="customer-note">Ghi chú chung</label>
            <textarea class="form-control" id="customer-note" maxlength="500"></textarea>
            <div class="bill-total"><span>Tổng tạm tính</span><span id="sheet-total">0 đ</span></div><button
                class="btn btn-primary btn-block" type="button" id="submit-order"><i data-lucide="send"></i> Gọi
                món</button>
            <div id="cart-error" class="field-error"></div>
        </section>
    </div>
    @endsection @push('scripts')
    <script>
        const products = @json($categories->flatMap->products->mapWithKeys(fn($p) => [$p->id => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->price]]));
        const cartKey = 'oder-cart-{{ $tableSession->id }}';
        let cart = JSON.parse(localStorage.getItem(cartKey) || '{}');
        const money = n => new Intl.NumberFormat('vi-VN').format(n) + ' đ';
        const save = () => {
            localStorage.setItem(cartKey, JSON.stringify(cart));
            render()
        };

        function render() {
            const box = document.getElementById('cart-items');
            box.replaceChildren();
            let count = 0,
                total = 0;
            Object.values(cart).forEach(row => {
                const p = products[row.id];
                if (!p) {
                    delete cart[row.id];
                    return
                }
                count += row.qty;
                total += p.price * row.qty;
                const el = document.createElement('div');
                el.className = 'cart-row';
                const info = document.createElement('div');
                const name = document.createElement('strong');
                name.textContent = p.name;
                const price = document.createElement('div');
                price.className = 'cell-meta';
                price.textContent = money(p.price);
                const note = document.createElement('input');
                note.className = 'form-control';
                note.placeholder = 'Ghi chú món';
                note.maxLength = 250;
                note.value = row.note || '';
                note.addEventListener('change', () => {
                    row.note = note.value;
                    save()
                });
                info.append(name, price, note);
                const step = document.createElement('div');
                step.className = 'stepper';
                const minus = document.createElement('button');
                minus.type = 'button';
                minus.textContent = '−';
                minus.onclick = () => {
                    row.qty--;
                    if (row.qty < 1) delete cart[row.id];
                    save()
                };
                const qty = document.createElement('strong');
                qty.textContent = row.qty;
                const plus = document.createElement('button');
                plus.type = 'button';
                plus.textContent = '+';
                plus.onclick = () => {
                    row.qty = Math.min(99, row.qty + 1);
                    save()
                };
                step.append(minus, qty, plus);
                el.append(info, step);
                box.append(el)
            });
            if (!count) box.innerHTML = '<div class="empty">Chưa có món trong giỏ.</div>';
            document.getElementById('cart-count').textContent = count + ' món';
            document.getElementById('cart-total').textContent = money(total);
            document.getElementById('sheet-total').textContent = money(total);
            document.getElementById('submit-order').disabled = !count;
        }
        document.querySelectorAll('[data-add]').forEach(btn => btn.onclick = () => {
            const id = btn.dataset.add;
            cart[id] ??= {
                id: Number(id),
                qty: 0,
                note: ''
            };
            cart[id].qty = Math.min(99, cart[id].qty + 1);
            save()
        });
        const drawer = document.getElementById('cart-drawer');
        document.getElementById('open-cart').onclick = () => drawer.classList.add('open');
        document.getElementById('close-cart').onclick = () => drawer.classList.remove('open');
        drawer.onclick = e => {
            if (e.target === drawer) drawer.classList.remove('open')
        };
        document.getElementById('submit-order').onclick = async function() {
            this.disabled = true;
            document.getElementById('cart-error').textContent = '';
            try {
                const response = await fetch('{{ route('menu.orders.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify({
                        idempotency_key: '{{ $idempotencyKey }}',
                        customer_note: document.getElementById('customer-note').value,
                        items: Object.values(cart).map(x => ({
                            product_id: x.id,
                            quantity: x.qty,
                            note: x.note
                        }))
                    })
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat()[0] ||
                    'Không thể gửi order');
                localStorage.removeItem(cartKey);
                location.href = '{{ route('menu.status') }}'
            } catch (error) {
                document.getElementById('cart-error').textContent = error.message;
                this.disabled = false
            }
        };
        render();
    </script>
@endpush
