import fs from 'node:fs';
import path from 'node:path';
import {
    browserLaunchOptions,
    evidenceRoot,
    loadGuideDependency,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');

const baseUrl = process.env.SNIPEIT_GUIDE_BASE_URL || 'https://dev.inbit';
const username = process.env.SNIPEIT_GUIDE_USER || 'codex';
const password = process.env.SNIPEIT_GUIDE_PASSWORD;
const outDir = process.env.GUIDE_CAPTURE_DIR || evidenceRoot;
const captureMode = process.env.SNIPEIT_GUIDE_CAPTURE_MODE || 'all';
const exampleModelId = process.env.SNIPEIT_CATALOG_MODEL_ID || '2';
const exampleModelNumberId = process.env.SNIPEIT_CATALOG_MODEL_NUMBER_ID || '2';

if (!password) {
    throw new Error('Set SNIPEIT_GUIDE_PASSWORD for the controlled screenshot account.');
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
        .asset-tests-attention { display: none !important; }
    ` });
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.waitForTimeout(450);
}

async function capture(page, fileName) {
    const file = path.join(outDir, fileName);
    await page.screenshot({ path: file, fullPage: false });
    return file;
}

async function chooseAjaxSelect(page, containerId, value) {
    await page.locator(containerId).click();
    const search = page.locator('.select2-container--open .select2-search__field');
    await search.fill(value);
    await page.getByRole('option', { name: value, exact: true }).click();
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

const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: { width: 1365, height: 900 },
    deviceScaleFactor: 1,
});
const page = await context.newPage();

try {
    await login(page);
    const files = [];

    if (captureMode === 'all' || captureMode === 'core') {
        await open(page, '/models');
        await page.locator('#asssetModelsTable').waitFor({ state: 'visible', timeout: 10000 });
        await page.waitForTimeout(1200);
        await expandSettingsNavigation(page);
        const modelSearch = page.getByRole('searchbox').first();
        await modelSearch.fill('HP ProBook 450');
        await page.waitForTimeout(900);
        files.push(await capture(page, 'CAT-MODEL-LIST-DESKTOP-01.png'));

        await open(page, `/models/${exampleModelId}`);
        await page.getByRole('heading', { name: /Model Numbers/i }).waitFor();
        files.push(await capture(page, 'CAT-MODEL-DETAIL-DESKTOP-01.png'));

        await open(page, '/models/create');
        await page.locator('input[name="name"]').fill('HP ProBook 450 G8');
        await chooseAjaxSelect(page, '#select2-category_select_id-container', 'Laptops');
        await chooseAjaxSelect(page, '#select2-manufacturer_select_id-container', 'HP');
        files.push(await capture(page, 'CAT-MODEL-CREATE-DESKTOP-01.png'));

        await open(page, `/models/${exampleModelId}/model-numbers/create`);
        await page.locator('input[name="code"]').fill('2E9F8EA#ABH');
        await page.locator('input[name="label"]').fill('HP ProBook 450 G8 - i5-1135G7 - 8GB - 256GB');
        files.push(await capture(page, 'CAT-MODEL-NUMBER-CREATE-DESKTOP-01.png'));
    }

    if (captureMode === 'all' || captureMode === 'definitions' || captureMode === 'spec') {
        await open(page, `/models/${exampleModelId}/model-numbers/${exampleModelNumberId}/spec`);
        await page.getByRole('heading', { name: 'Expected Components', exact: true }).waitFor();
        files.push(await capture(page, 'CAT-MODEL-SPEC-DESKTOP-01.png'));
        await page.locator('#expected-components').scrollIntoViewIfNeeded();
        await page.evaluate(() => window.scrollBy(0, -110));
        await page.waitForTimeout(250);
        files.push(await capture(page, 'CAT-MODEL-SPEC-COMPONENTS-DESKTOP-01.png'));
    }

    if (captureMode === 'all' || captureMode === 'definitions') {
        await open(page, '/attributes');
        await page.getByRole('heading', { name: /Attributes/i }).first().waitFor();
        files.push(await capture(page, 'CAT-ATTRIBUTE-LIST-DESKTOP-01.png'));

        await open(page, '/admin/settings/component-definitions');
        await page.getByRole('heading', { name: 'Component Definitions', exact: true }).last().waitFor();
        files.push(await capture(page, 'CAT-COMPONENT-DEFINITION-LIST-DESKTOP-01.png'));

        await open(page, '/admin/settings/model-numbers');
        await page.getByRole('heading', { name: /Model Numbers/i }).first().waitFor();
        files.push(await capture(page, 'CAT-MODEL-NUMBER-LIFECYCLE-DESKTOP-01.png'));
    }

    console.log(JSON.stringify({ files, baseUrl, captureMode, exampleModelId, exampleModelNumberId }, null, 2));
} finally {
    await context.close();
    await browser.close();
}
