import { chromium } from 'playwright';
import fs from 'node:fs/promises';

const baseUrl = process.env.APP_TEST_URL ?? 'http://127.0.0.1:8000';
const customerUrl = process.env.CUSTOMER_TEST_URL;
const edge = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const output = 'storage/app/testing/screenshots';
const viewports = [
    { name: 'desktop-1920', width: 1920, height: 1080 },
    { name: 'desktop-1440', width: 1440, height: 900 },
    { name: 'desktop-1366', width: 1366, height: 768 },
    { name: 'desktop-1024', width: 1024, height: 768 },
    { name: 'tablet-768', width: 768, height: 1024 },
    { name: 'mobile-430', width: 430, height: 932 },
    { name: 'mobile-414', width: 414, height: 896 },
    { name: 'mobile-390', width: 390, height: 844 },
    { name: 'mobile-375', width: 375, height: 812 },
];
const fullAdminPaths = ['/admin/categories', '/admin/tables', '/admin/orders', '/admin/payments', '/admin/inventory', '/admin/inventory/transactions', '/admin/suppliers', '/admin/users', '/admin/roles', '/admin/reports', '/admin/activity-logs', '/kitchen', '/profile'];

await fs.mkdir(output, { recursive: true });
const browser = await chromium.launch({ executablePath: edge, headless: true });
const results = [];

const loginContext = await browser.newContext({ viewport: { width: 1366, height: 768 } });
const loginPage = await loginContext.newPage();
await loginPage.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
await loginPage.fill('#email', 'superadmin@odermonan.test');
await loginPage.fill('#password', 'Password@123');
await Promise.all([loginPage.waitForURL('**/admin/dashboard'), loginPage.click('button[type=submit]')]);
const authState = await loginContext.storageState();
await loginContext.close();

const hasPageOverflow = (page) => page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1);

for (const viewport of viewports) {
    const context = await browser.newContext({ viewport: { width: viewport.width, height: viewport.height }, storageState: authState });
    const page = await context.newPage();
    const errors = [];
    const failedResponses = [];
    page.on('console', (message) => { if (message.type() === 'error') errors.push(message.text()); });
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('response', (response) => { if (response.status() >= 500) failedResponses.push(`${response.status()} ${response.url()}`); });

    await page.goto(`${baseUrl}/admin/dashboard`, { waitUntil: 'networkidle' });
    await page.screenshot({ path: `${output}/dashboard-${viewport.name}.png`, fullPage: true });
    const chartVisible = await page.locator('#performance-chart').evaluate((canvas) => canvas.width > 0 && canvas.height > 0);
    const adminOverflow = [{ path: '/admin/dashboard', overflow: await hasPageOverflow(page) }];

    if (viewport.width <= 820) {
        await page.click('[data-sidebar-toggle]');
        const drawerOpen = await page.locator('[data-sidebar]').evaluate((node) => node.classList.contains('open'));
        if (!drawerOpen) errors.push('Mobile sidebar did not open.');
        await page.click('[data-sidebar-overlay]');
    }

    await page.goto(`${baseUrl}/admin/products`, { waitUntil: 'networkidle' });
    adminOverflow.push({ path: '/admin/products', overflow: await hasPageOverflow(page) });

    if (viewport.name === 'desktop-1366' || viewport.name === 'mobile-390') {
        for (const path of fullAdminPaths) {
            await page.goto(`${baseUrl}${path}`, { waitUntil: 'networkidle' });
            adminOverflow.push({ path, overflow: await hasPageOverflow(page) });
        }
    }

    let permissionEditor = null;
    if (viewport.name === 'desktop-1366') {
        await page.goto(`${baseUrl}/admin/roles`, { waitUntil: 'networkidle' });
        const editLink = page.locator('a[href*="/admin/roles/"][href$="/edit"]').first();
        if (await editLink.count()) {
            await editLink.click();
            const firstCard = page.locator('[data-permission-card]').first();
            const parent = firstCard.locator('[data-permission-parent]');
            const child = firstCard.locator('[data-permission-child]').first();
            await parent.check();
            const allChecked = await firstCard.locator('[data-permission-child]').evaluateAll((nodes) => nodes.every((node) => node.checked));
            await child.uncheck();
            const indeterminate = await parent.evaluate((node) => node.indeterminate);
            await page.click('[data-permission-select-all]');
            const selectedAll = await page.locator('[data-permission-child]').evaluateAll((nodes) => nodes.every((node) => node.checked));
            await page.click('[data-permission-clear]');
            const cleared = await page.locator('[data-permission-child]').evaluateAll((nodes) => nodes.every((node) => !node.checked));
            await page.fill('[data-permission-search]', 'orders');
            const visibleCards = await page.locator('[data-permission-card]:visible').count();
            permissionEditor = { allChecked, indeterminate, selectedAll, cleared, visibleCards };
            await page.screenshot({ path: `${output}/permissions-${viewport.name}.png`, fullPage: true });
        }
    }

    let customer = null;
    if (customerUrl) {
        await page.goto(customerUrl, { waitUntil: 'networkidle' });
        const initialOverflow = await hasPageOverflow(page);
        const addButton = page.locator('[data-add]:not([disabled])').first();
        if (await addButton.count()) {
            await addButton.click();
            const toastVisible = await page.locator('[data-toast]').isVisible();
            await page.click('#open-cart');
            await page.locator('#cart-list .cart-row').first().waitFor();
            await page.locator('#cart-list [data-change="1"]').first().click();
            await page.fill('#customer-note', 'Kiểm thử giao diện');
            await page.click('#cart-next');
            const confirmVisible = await page.locator('.cart-confirm').first().isVisible();
            await page.screenshot({ path: `${output}/cart-${viewport.name}.png`, fullPage: true });
            let submitted = false;
            if (viewport.name === 'mobile-390') {
                await Promise.all([page.waitForURL('**/menu/status'), page.click('#submit-order')]);
                submitted = await page.getByText('Đơn của', { exact: false }).first().isVisible();
                await page.screenshot({ path: `${output}/status-${viewport.name}.png`, fullPage: true });
            }
            customer = { initialOverflow, cartOverflow: await hasPageOverflow(page), toastVisible, confirmVisible, submitted };
        } else {
            customer = { initialOverflow, error: 'No available product button.' };
        }
        await page.screenshot({ path: `${output}/menu-${viewport.name}.png`, fullPage: true });
    }

    results.push({ viewport: viewport.name, chartVisible, adminOverflow, permissionEditor, customer, consoleErrors: errors, failedResponses });
    await context.close();
}

await browser.close();
console.log(JSON.stringify(results, null, 2));

if (results.some((result) => result.consoleErrors.length || result.failedResponses.length || result.adminOverflow.some((page) => page.overflow) || result.customer?.initialOverflow || result.customer?.cartOverflow)) {
    process.exitCode = 1;
}
