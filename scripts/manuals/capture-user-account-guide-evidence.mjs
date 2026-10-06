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
const exampleGroupId = process.env.SNIPEIT_GUIDE_GROUP_ID || '4';

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
    await page.addStyleTag({ content: `
        footer.main-footer { display: none !important; }
    ` });
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

async function expandSettingsNavigation(page) {
    await page.evaluate(() => {
        document.body.classList.remove('sidebar-collapse');
        const tree = [...document.querySelectorAll('.sidebar-menu > li.treeview')]
            .find((item) => item.querySelector(':scope > a')?.textContent.includes('Instellingen'));
        if (!tree) return;
        tree.classList.add('active', 'menu-open');
        const submenu = tree.querySelector(':scope > ul.treeview-menu');
        if (submenu) submenu.style.display = 'block';
    });
    await page.waitForTimeout(250);
}

async function scrollTo(page, selector, offset = -120) {
    await page.locator(selector).first().scrollIntoViewIfNeeded();
    await page.evaluate((scrollOffset) => window.scrollBy(0, scrollOffset), offset);
    await page.waitForTimeout(300);
}

async function captureGroups(page) {
    const files = [];

    await open(page, '/admin/groups');
    await page.locator('#groupsTable').waitFor({ state: 'visible', timeout: 10000 });
    await page.waitForFunction(() => document.querySelectorAll('#groupsTable tbody tr').length > 0);
    await expandSettingsNavigation(page);
    files.push(await capture(page, 'USR-GROUPS-LIST-DESKTOP-01.png'));

    await open(page, '/admin/groups/create');
    await page.locator('#name').waitFor({ state: 'visible' });
    files.push(await capture(page, 'USR-GROUP-CREATE-DESKTOP-01.png'));

    await open(page, `/admin/groups/${exampleGroupId}/edit`);
    await page.locator('#name').waitFor({ state: 'visible' });
    files.push(await capture(page, 'USR-GROUP-PERMISSIONS-TOP-DESKTOP-01.png'));
    await scrollTo(page, 'input[aria-label="permission[models.manage_specification_cleanup]"]', -290);
    files.push(await capture(page, 'USR-GROUP-PERMISSIONS-MODELS-DESKTOP-01.png'));
    await scrollTo(page, '#submit_button', 120);
    files.push(await capture(page, 'USR-GROUP-SAVE-DESKTOP-01.png'));

    await open(page, `/admin/groups/${exampleGroupId}`);
    await page.getByRole('heading', { name: /Groep|Group/i }).first().waitFor();
    await page.waitForTimeout(1000);
    files.push(await capture(page, 'USR-GROUP-DETAIL-DESKTOP-01.png'));

    return files;
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
    const files = phase === 'groups'
        ? await captureGroups(page)
        : phase === 'deleted'
        ? await captureDeleted(page)
        : phase === 'restored'
            ? await captureRestored(page)
            : await captureActive(page);
    console.log(JSON.stringify({ phase, files }, null, 2));
} finally {
    await context.close();
    await browser.close();
}
