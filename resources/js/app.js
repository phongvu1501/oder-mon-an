import './bootstrap';
import { createIcons, icons } from 'lucide';

const money = new Intl.NumberFormat('vi-VN');

function refreshIcons(root = document) {
    createIcons({ icons, attrs: { 'aria-hidden': 'true' }, nameAttr: 'data-lucide', root });
}

function initSidebar() {
    const sidebar = document.querySelector('[data-sidebar]');
    const overlay = document.querySelector('[data-sidebar-overlay]');
    const toggle = document.querySelector('[data-sidebar-toggle]');
    if (!sidebar || !overlay || !toggle) return;

    const close = () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('open');
        document.body.classList.remove('is-locked');
        toggle.setAttribute('aria-expanded', 'false');
    };
    const open = () => {
        sidebar.classList.add('open');
        overlay.classList.add('open');
        document.body.classList.add('is-locked');
        toggle.setAttribute('aria-expanded', 'true');
        sidebar.querySelector('a, button')?.focus();
    };
    toggle.addEventListener('click', () => sidebar.classList.contains('open') ? close() : open());
    overlay.addEventListener('click', close);
    sidebar.querySelector('[data-sidebar-close]')?.addEventListener('click', close);
    sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
}

function removeToast(toast) {
    if (!toast || toast.classList.contains('is-leaving')) return;
    toast.classList.add('is-leaving');
    window.setTimeout(() => toast.remove(), 180);
}

function armToast(toast) {
    const timeout = window.setTimeout(() => removeToast(toast), 5000);
    toast.querySelector('[data-toast-close]')?.addEventListener('click', () => {
        window.clearTimeout(timeout);
        removeToast(toast);
    });
}

function showToast(message, type = 'info', title = null) {
    const region = document.querySelector('[data-toast-region]');
    if (!region) return;
    const settings = {
        success: ['Thành công', 'circle-check'],
        error: ['Không thể thực hiện', 'circle-alert'],
        warning: ['Cần lưu ý', 'triangle-alert'],
        info: ['Thông tin', 'info'],
    };
    const [defaultTitle, icon] = settings[type] || settings.info;
    const toast = document.createElement('div');
    toast.className = `toast toast--${settings[type] ? type : 'info'}`;
    toast.dataset.toast = '';
    toast.setAttribute('role', 'status');
    toast.innerHTML = '<span class="toast-icon"><i></i></span><div class="toast-copy"><strong></strong><span></span></div><button class="toast-close" type="button" data-toast-close aria-label="Đóng thông báo"><i data-lucide="x"></i></button><span class="toast-progress" aria-hidden="true"></span>';
    toast.querySelector('.toast-icon i').setAttribute('data-lucide', icon);
    toast.querySelector('.toast-copy strong').textContent = title || defaultTitle;
    toast.querySelector('.toast-copy span').textContent = message;
    region.append(toast);
    refreshIcons(toast);
    armToast(toast);
}

function initToasts() {
    document.querySelectorAll('[data-toast]').forEach(armToast);
    window.appToast = showToast;
    const queued = sessionStorage.getItem('app.toast');
    if (queued) {
        try {
            const toast = JSON.parse(queued);
            showToast(toast.message, toast.type, toast.title);
        } catch { /* Ignore malformed browser state. */ }
        sessionStorage.removeItem('app.toast');
    }
}

function initConfirmDialog() {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;
    let pendingForm = null;
    let previousFocus = null;
    const message = modal.querySelector('#confirm-modal-message');
    const accept = modal.querySelector('[data-confirm-accept]');

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-locked');
        pendingForm = null;
        previousFocus?.focus();
    };
    document.querySelectorAll('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
        event.preventDefault();
        pendingForm = form;
        previousFocus = document.activeElement;
        message.textContent = form.dataset.confirm || 'Bạn có chắc muốn thực hiện thao tác này?';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-locked');
        accept.focus();
    }));
    modal.querySelectorAll('[data-confirm-cancel]').forEach((button) => button.addEventListener('click', close));
    accept.addEventListener('click', () => {
        const form = pendingForm;
        if (!form) return close();
        accept.disabled = true;
        accept.textContent = 'Đang xử lý...';
        HTMLFormElement.prototype.submit.call(form);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
    });
}

function initForms() {
    document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', (event) => {
        if (event.defaultPrevented || form.hasAttribute('data-no-loading')) return;
        const submit = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
        if (!submit) return;
        submit.disabled = true;
        submit.dataset.label = submit.innerHTML;
        submit.innerHTML = '<span class="spinner"></span><span>Đang xử lý...</span>';
    }));

    document.querySelectorAll('[data-avatar-input]').forEach((input) => input.addEventListener('change', () => {
        const preview = document.querySelector('[data-avatar-preview]');
        const file = input.files?.[0];
        if (!preview || !file) return;
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
    }));
}

function initFilters() {
    document.querySelectorAll('[data-filter-toggle]').forEach((toggle) => toggle.addEventListener('click', () => {
        const panel = toggle.closest('.filter-panel');
        const body = panel?.querySelector('[data-filter-body]');
        if (!body) return;
        body.hidden = !body.hidden;
        toggle.setAttribute('aria-expanded', String(!body.hidden));
        const icon = toggle.querySelector('svg');
        icon?.setAttribute('data-lucide', body.hidden ? 'chevron-down' : 'chevron-up');
        refreshIcons(toggle);
    }));
}

async function initDashboard() {
    const source = document.getElementById('dashboard-chart-data');
    if (!source) return;
    let data;
    try { data = JSON.parse(source.textContent); } catch { return; }
    const { default: Chart } = await import('chart.js/auto');

    const period = document.querySelector('[data-period-select]');
    const custom = document.querySelector('[data-custom-period]');
    period?.addEventListener('change', () => custom?.classList.toggle('is-visible', period.value === 'custom'));

    const empty = (name) => document.querySelector(`[data-chart-empty="${name}"]`)?.classList.add('is-visible');
    const timeline = data.timeline || {};
    const performance = document.getElementById('performance-chart');
    if (performance && timeline.labels?.length && ([...(timeline.orders || []), ...(timeline.revenue || [])]).some(Number)) {
        new Chart(performance, {
            data: {
                labels: timeline.labels,
                datasets: [
                    { type: 'line', label: 'Doanh thu', data: timeline.revenue, borderColor: '#059669', backgroundColor: 'rgba(16,185,129,.09)', borderWidth: 2, pointRadius: 2, tension: .32, fill: true, yAxisID: 'yRevenue' },
                    { type: 'bar', label: 'Đơn hàng', data: timeline.orders, backgroundColor: '#dbeafe', borderColor: '#0284c7', borderWidth: 1, borderRadius: 4, maxBarThickness: 30, yAxisID: 'yOrders' },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 7, padding: 18 } }, tooltip: { callbacks: { label: (context) => context.dataset.yAxisID === 'yRevenue' ? ` Doanh thu: ${money.format(context.raw)} đ` : ` Đơn hàng: ${context.raw}` } } },
                scales: { x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 9 } }, yRevenue: { beginAtZero: true, position: 'left', grid: { color: '#edf1ef' }, ticks: { callback: (value) => value >= 1000000 ? `${value / 1000000}tr` : money.format(value) } }, yOrders: { beginAtZero: true, position: 'right', grid: { display: false }, ticks: { precision: 0 } } },
            },
        });
    } else empty('performance');

    const statuses = data.statuses || {};
    const statusCanvas = document.getElementById('status-chart');
    if (statusCanvas && statuses.values?.some(Number)) {
        new Chart(statusCanvas, {
            type: 'doughnut',
            data: { labels: statuses.labels, datasets: [{ data: statuses.values, backgroundColor: ['#f59e0b', '#0284c7', '#10b981', '#8b5cf6', '#64748b', '#16a34a', '#dc2626'], borderColor: '#fff', borderWidth: 3 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 7, padding: 13, font: { size: 10 } } } } },
        });
    } else empty('status');
}

function initPermissionEditor() {
    const editor = document.querySelector('[data-permission-editor]');
    if (!editor) return;
    const cards = [...editor.querySelectorAll('[data-permission-card]')];
    const children = () => [...editor.querySelectorAll('[data-permission-child]')];
    const search = editor.querySelector('[data-permission-search]');
    const selectedOnly = editor.querySelector('[data-permission-selected-only]');
    const totals = editor.querySelectorAll('[data-permission-total]');
    const empty = editor.querySelector('[data-permission-empty]');

    const updateCard = (card) => {
        const items = [...card.querySelectorAll('[data-permission-child]')];
        const checked = items.filter((item) => item.checked).length;
        const parent = card.querySelector('[data-permission-parent]');
        parent.checked = items.length > 0 && checked === items.length;
        parent.indeterminate = checked > 0 && checked < items.length;
        card.querySelector('[data-permission-card-count]').textContent = `${checked} / ${items.length}`;
    };
    const applyFilter = () => {
        const needle = (search?.value || '').trim().toLocaleLowerCase('vi');
        let visible = 0;
        cards.forEach((card) => {
            const matchesText = !needle || card.dataset.search.includes(needle);
            const hasSelected = card.querySelector('[data-permission-child]:checked');
            const show = matchesText && (!selectedOnly?.checked || hasSelected);
            card.hidden = !show;
            if (show) visible++;
        });
        if (empty) empty.hidden = visible > 0;
    };
    const update = () => {
        cards.forEach(updateCard);
        const count = children().filter((item) => item.checked).length;
        totals.forEach((total) => { total.textContent = count; });
        applyFilter();
    };
    cards.forEach((card) => {
        card.querySelector('[data-permission-parent]')?.addEventListener('change', (event) => {
            card.querySelectorAll('[data-permission-child]').forEach((item) => { item.checked = event.target.checked; });
            update();
        });
        card.querySelectorAll('[data-permission-child]').forEach((item) => item.addEventListener('change', update));
    });
    editor.querySelector('[data-permission-select-all]')?.addEventListener('click', () => { children().forEach((item) => { item.checked = true; }); update(); });
    editor.querySelector('[data-permission-clear]')?.addEventListener('click', () => { children().forEach((item) => { item.checked = false; }); update(); });
    search?.addEventListener('input', applyFilter);
    selectedOnly?.addEventListener('change', applyFilter);
    update();
}

function initInventoryEditor() {
    const modal = document.getElementById('inventory-edit-modal');
    const form = modal?.querySelector('[data-inventory-form]');
    if (!modal || !form) return;
    let previousFocus = null;
    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-locked');
        previousFocus?.focus();
    };
    document.querySelectorAll('[data-inventory-edit]').forEach((button) => button.addEventListener('click', () => {
        previousFocus = button;
        form.action = button.dataset.action;
        form.elements.name.value = button.dataset.name;
        form.elements.sku.value = button.dataset.sku;
        form.elements.unit.value = button.dataset.unit;
        form.elements.minimum_stock.value = button.dataset.minimum;
        form.elements.cost_price.value = button.dataset.cost;
        form.elements.status.value = button.dataset.status;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-locked');
        form.elements.name.focus();
    }));
    modal.querySelectorAll('[data-inventory-close]').forEach((button) => button.addEventListener('click', close));
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal.classList.contains('is-open')) close(); });
}

function initStockLines() {
    const form = document.querySelector('[data-stock-form]');
    const lines = form?.querySelector('[data-stock-lines]');
    const template = form?.querySelector('[data-stock-line-template]');
    if (!form || !lines || !template) return;
    const reindex = () => lines.querySelectorAll('[data-stock-line]').forEach((row, index) => {
        row.querySelectorAll('[name]').forEach((field) => { field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`); });
    });
    form.querySelector('[data-add-stock-line]')?.addEventListener('click', () => {
        lines.append(template.content.cloneNode(true));
        reindex();
        refreshIcons(lines);
    });
    lines.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-stock-line]');
        if (!remove || lines.querySelectorAll('[data-stock-line]').length === 1) return;
        remove.closest('[data-stock-line]').remove();
        reindex();
    });
}

function initCustomerMenu() {
    const root = document.querySelector('[data-customer-menu]');
    const source = document.getElementById('customer-menu-data');
    if (!root || !source) return;
    let settings;
    try { settings = JSON.parse(source.textContent); } catch { return; }
    const products = new Map(settings.products.map((product) => [Number(product.id), product]));
    const storageKey = settings.cartKey;
    let cart = {};
    try { cart = JSON.parse(localStorage.getItem(storageKey) || '{}'); } catch { cart = {}; }
    Object.keys(cart).forEach((key) => {
        const product = products.get(Number(key));
        if (!product || !product.available || !cart[key]?.qty) delete cart[key];
    });

    const bar = document.getElementById('cart-bar');
    const drawer = document.getElementById('cart-drawer');
    const sheet = drawer?.querySelector('.cart-sheet');
    const list = document.getElementById('cart-list');
    const empty = document.getElementById('cart-empty');
    const note = document.getElementById('customer-note');
    const error = document.getElementById('cart-error');
    const next = document.getElementById('cart-next');
    const submit = document.getElementById('submit-order');

    const values = () => Object.values(cart).filter((item) => item.qty > 0 && products.has(Number(item.id)));
    const totals = () => values().reduce((result, item) => {
        const product = products.get(Number(item.id));
        result.count += item.qty;
        result.price += Number(product.price) * item.qty;
        return result;
    }, { count: 0, price: 0 });
    const persist = () => localStorage.setItem(storageKey, JSON.stringify(cart));
    const updateQuantities = () => {
        document.querySelectorAll('[data-product-quantity]').forEach((badge) => {
            const qty = cart[badge.dataset.productQuantity]?.qty || 0;
            badge.textContent = qty;
            badge.hidden = qty === 0;
        });
    };
    const render = () => {
        const items = values();
        const sum = totals();
        document.querySelectorAll('[data-cart-count]').forEach((node) => { node.textContent = sum.count; });
        document.querySelectorAll('[data-cart-total]').forEach((node) => { node.textContent = `${money.format(sum.price)} đ`; });
        if (bar) bar.hidden = sum.count === 0;
        if (empty) empty.hidden = items.length > 0;
        if (next) next.disabled = items.length === 0;
        if (list) {
            list.textContent = '';
            items.forEach((item) => {
                const product = products.get(Number(item.id));
                const row = document.createElement('div');
                row.className = 'cart-row';
                row.innerHTML = '<div class="cart-row-copy"><strong></strong><span></span><input class="form-control cart-item-note" maxlength="250" placeholder="Ghi chú riêng cho món"></div><div class="cart-row-side"><span class="cart-row-total"></span><div class="stepper"><button type="button" data-change="-1" aria-label="Giảm số lượng"><i data-lucide="minus"></i></button><span></span><button type="button" data-change="1" aria-label="Tăng số lượng"><i data-lucide="plus"></i></button></div></div>';
                row.querySelector('strong').textContent = product.name;
                row.querySelector('.cart-row-copy span').textContent = `${money.format(product.price)} đ / ${product.unit || 'phần'}`;
                row.querySelector('.cart-row-total').textContent = `${money.format(Number(product.price) * item.qty)} đ`;
                row.querySelector('.stepper span').textContent = item.qty;
                const itemNote = row.querySelector('.cart-item-note');
                itemNote.value = item.note || '';
                itemNote.addEventListener('change', () => { cart[String(item.id)].note = itemNote.value; persist(); });
                row.querySelectorAll('[data-change]').forEach((button) => button.addEventListener('click', () => change(product.id, Number(button.dataset.change))));
                list.append(row);
            });
            refreshIcons(list);
        }
        updateQuantities();
        persist();
    };
    const change = (id, amount) => {
        const key = String(id);
        const product = products.get(Number(id));
        if (!product || !product.available) return;
        cart[key] ||= { id: Number(id), qty: 0, note: '' };
        cart[key].qty = Math.max(0, Math.min(99, Number(cart[key].qty) + amount));
        if (!cart[key].qty) delete cart[key];
        sheet?.classList.remove('is-confirming');
        render();
    };
    const open = () => { drawer?.classList.add('open'); drawer?.setAttribute('aria-hidden', 'false'); document.body.classList.add('is-locked'); sheet?.querySelector('button')?.focus(); };
    const close = () => { drawer?.classList.remove('open'); drawer?.setAttribute('aria-hidden', 'true'); document.body.classList.remove('is-locked'); sheet?.classList.remove('is-confirming'); };

    root.querySelectorAll('[data-add]').forEach((button) => button.addEventListener('click', () => {
        const product = products.get(Number(button.dataset.add));
        change(product.id, 1);
        showToast(`Đã thêm ${product.name} vào giỏ hàng`, 'success');
    }));
    document.getElementById('open-cart')?.addEventListener('click', open);
    document.getElementById('close-cart')?.addEventListener('click', close);
    drawer?.addEventListener('click', (event) => { if (event.target === drawer) close(); });
    document.getElementById('cart-back')?.addEventListener('click', () => sheet?.classList.remove('is-confirming'));
    next?.addEventListener('click', () => {
        const confirmList = document.getElementById('confirm-order-list');
        if (confirmList) {
            confirmList.textContent = '';
            values().forEach((item) => {
                const product = products.get(Number(item.id));
                const row = document.createElement('li');
                const name = document.createElement('span');
                const price = document.createElement('strong');
                name.textContent = `${product.name} × ${item.qty}`;
                price.textContent = `${money.format(Number(product.price) * item.qty)} đ`;
                row.append(name, price);
                confirmList.append(row);
            });
        }
        sheet?.classList.add('is-confirming');
        sheet?.scrollTo({ top: 0, behavior: 'smooth' });
    });
    submit?.addEventListener('click', async () => {
        submit.disabled = true;
        error.textContent = '';
        try {
            const response = await fetch(settings.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ idempotency_key: settings.idempotencyKey, customer_note: note?.value || null, items: values().map((item) => ({ product_id: Number(item.id), quantity: item.qty, note: item.note || null })) }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Không thể gửi đơn hàng.');
            localStorage.removeItem(storageKey);
            sessionStorage.setItem('app.toast', JSON.stringify({ type: 'success', message: 'Đặt món thành công. Bếp đã nhận được đơn của bạn.' }));
            window.location.assign(settings.statusUrl);
        } catch (requestError) {
            error.textContent = requestError.message;
            showToast(requestError.message, 'error');
            submit.disabled = false;
        }
    });

    const chips = [...document.querySelectorAll('[data-category-link]')];
    chips.forEach((chip) => chip.addEventListener('click', (event) => {
        event.preventDefault();
        document.querySelector(chip.getAttribute('href'))?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
    const sections = [...document.querySelectorAll('.menu-section')];
    if ('IntersectionObserver' in window && sections.length) {
        const observer = new IntersectionObserver((entries) => {
            const current = entries.filter((entry) => entry.isIntersecting).sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
            if (!current) return;
            chips.forEach((chip) => chip.classList.toggle('active', chip.getAttribute('href') === `#${current.target.id}`));
        }, { rootMargin: '-130px 0px -55% 0px', threshold: [0, .2, .6] });
        sections.forEach((section) => observer.observe(section));
    }
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && drawer?.classList.contains('open')) close(); });
    render();
}

document.addEventListener('DOMContentLoaded', () => {
    refreshIcons();
    initSidebar();
    initToasts();
    initConfirmDialog();
    initForms();
    initFilters();
    initDashboard();
    initPermissionEditor();
    initInventoryEditor();
    initStockLines();
    initCustomerMenu();
});
