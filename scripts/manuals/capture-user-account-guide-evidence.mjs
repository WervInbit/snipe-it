import fs from 'node:fs';
import path from 'node:path';
import {
    browserLaunchOptions,
    captureOutputDir,
    loadGuideDependency,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');

const baseUrl = process.env.SNIPEIT_GUIDE_BASE_URL || 'https://dev.inbit';
const username = process.env.SNIPEIT_GUIDE_USER || 'admin';
const password = process.env.SNIPEIT_GUIDE_PASSWORD;
const phase = process.argv[2] || 'active';
const outDir = process.env.GUIDE_CAPTURE_DIR || captureOutputDir('user-management');

if (!password) {
    throw new Error('Set SNIPEIT_GUIDE_PASSWORD for the local screenshot account.');
}

fs.mkdirSync(outDir, { recursive: true });

async function login(page) {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
    if (!page.url().includes('/login')) return;

    await page.locator('input[name="username"]').fill(username);
    await page.locator('input[name="password"]').fill(password);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/login')),
        page.locator('button[type="submit"]').click(),
    ]);
}

async function open(page, route) {
    await page.goto(`${baseUrl}${route}`, { waitUntil: 'domcontentloaded' });
    if (page.url().includes('/login')) {
        await login(page);
        await page.goto(`${baseUrl}${route}`, { waitUntil: 'domcontentloaded' });
    }
}

async function capture(page, fileName) {
    const file = path.join(outDir, fileName);
    await page.screenshot({ path: file, fullPage: false });
    return file;
}

async function captureActive(page) {
    const files = [];

    await open(page, '/users');
    await page.locator('#usersTable').waitFor();
    await page.waitForTimeout(800);
    files.push(await capture(page, 'USR-LIST-DESKTOP-01.png'));

    await open(page, '/users/create');
    files.push(await capture(page, 'USR-CREATE-FORM-DESKTOP-01.png'));

    const optional = page.getByText('Optionele informatie', { exact: true });
    if (await page.getByText('Groepen', { exact: true }).isHidden()) {
        await optional.click();
    }
    await page.locator('select[name="groups[]"]').scrollIntoViewIfNeeded();
    files.push(await capture(page, 'USR-GROUP-EDIT-DESKTOP-01.png'));

    await open(page, '/users/10/edit#permissions');
    files.push(await capture(page, 'USR-PERMISSIONS-DESKTOP-01.png'));

    await open(page, '/users/10');
    files.push(await capture(page, 'USR-DETAIL-DESKTOP-01.png'));
    files.push(await capture(page, 'USR-RESET-LINK-DESKTOP-01.png'));

    await open(page, '/users/10/edit');
    files.push(await capture(page, 'USR-EDIT-PASSWORD-DESKTOP-01.png'));
    files.push(await capture(page, 'USR-EDIT-ACTIVATED-DESKTOP-01.png'));

    const accountMenu = page.getByText('Production Admin', { exact: true }).first();
    await accountMenu.click();
    files.push(await capture(page, 'AC-ACCOUNT-MENU-DESKTOP-01.png'));

    await open(page, '/account/password');
    files.push(await capture(page, 'AC-SELF-PASSWORD-DESKTOP-01.png'));

    await open(page, '/users/12');
    files.push(await capture(page, 'USR-DEACTIVATED-DESKTOP-01.png'));
    files.push(await capture(page, 'USR-ASSIGNMENTS-DESKTOP-01.png'));
    await page.getByText('Check Alles In / Verwijder Gebruiker', { exact: true }).scrollIntoViewIfNeeded();
    files.push(await capture(page, 'USR-DELETE-DESKTOP-01.png'));

    return files;
}

async function captureDeleted(page) {
    const files = [];

    await open(page, '/users?status=deleted');
    await page.getByText('Miladb', { exact: true }).waitFor({ timeout: 10000 });
    files.push(await capture(page, 'USR-DELETED-LIST-DESKTOP-01.png'));

    await open(page, '/users/12');
    await page.getByRole('button', { name: /herstel|restore/i }).scrollIntoViewIfNeeded();
    files.push(await capture(page, 'USR-RESTORE-DESKTOP-01.png'));

    return files;
}

async function captureRestored(page) {
    await open(page, '/users/12');
    return [await capture(page, 'USR-RESTORED-DESKTOP-01.png')];
}

const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: { width: 1265, height: 710 },
    deviceScaleFactor: 1,
});
const page = await context.newPage();

try {
    await login(page);
    const files = phase === 'deleted'
        ? await captureDeleted(page)
        : phase === 'restored'
            ? await captureRestored(page)
            : await captureActive(page);
    console.log(JSON.stringify({ phase, files }, null, 2));
} finally {
    await context.close();
    await browser.close();
}
