import { chromium } from 'playwright';
import fs from 'node:fs/promises';

const baseUrl = process.env.APP_TEST_URL ?? 'http://127.0.0.1:8000';
const customerUrl = process.env.CUSTOMER_TEST_URL;
const edge = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const output = 'storage/app/testing/screenshots';

await fs.mkdir(output, { recursive: true });
const browser = await chromium.launch({ executablePath: edge, headless: true });
const results = [];

for (const viewport of [{ name: 'mobile', width: 390, height: 844 }, { name: 'desktop', width: 1366, height: 768 }]) {
    const context = await browser.newContext({ viewport: { width: viewport.width, height: viewport.height } });
    const page = await context.newPage();
    const errors = [];
    page.on('console', (message) => { if (message.type() === 'error') errors.push(message.text()); });
    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
    await page.screenshot({ path: `${output}/login-${viewport.name}.png`, fullPage: true });
    await page.fill('#email', 'superadmin@odermonan.test');
    await page.fill('#password', 'Password@123');
    await Promise.all([page.waitForURL('**/admin/dashboard'), page.click('button[type=submit]')]);
    await page.screenshot({ path: `${output}/dashboard-${viewport.name}.png`, fullPage: true });
    await page.goto(`${baseUrl}/admin/products`, { waitUntil: 'networkidle' });
    await page.screenshot({ path: `${output}/products-${viewport.name}.png`, fullPage: true });
    const adminOverflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);

    let customerOverflow = false;
    if (customerUrl) {
        await page.goto(customerUrl, { waitUntil: 'networkidle' });
        await page.screenshot({ path: `${output}/menu-${viewport.name}.png`, fullPage: true });
        await page.locator('[data-add]').first().click();
        await page.click('#open-cart');
        await page.screenshot({ path: `${output}/cart-${viewport.name}.png`, fullPage: true });
        customerOverflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth);
    }

    results.push({ viewport: viewport.name, adminOverflow, customerOverflow, consoleErrors: errors });
    await context.close();
}

await browser.close();
console.log(JSON.stringify(results, null, 2));
