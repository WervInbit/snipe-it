import fs from 'node:fs';
import path from 'node:path';
import {
    browserLaunchOptions,
    evidenceRoot,
    loadGuideDependency,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');

const baseUrl = process.env.SNIPEIT_GUIDE_BASE_URL || 'https://dev.inbit';
const username = process.env.SNIPEIT_GUIDE_USER || 'admin';
const password = process.env.SNIPEIT_GUIDE_PASSWORD;
const outDir = process.env.GUIDE_CAPTURE_DIR || evidenceRoot;
const captureMode = process.env.SNIPEIT_GUIDE_CAPTURE_MODE || 'all';
const guideAssetTag = 'INBIT-HG0421';
const guideSerial = '5CD1234ABC';
const guideModel = 'HP ProBook 450 G8';

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
    await page.addStyleTag({ content: '.asset-tests-attention { display: none !important; }' });
    await page.waitForTimeout(350);
}

async function capture(page, fileName, anchor = null) {
    if (anchor) {
        await anchor.scrollIntoViewIfNeeded();
    } else {
        await page.evaluate(() => window.scrollTo(0, 0));
    }
    await page.waitForTimeout(250);

    const file = path.join(outDir, fileName);
    await page.screenshot({ path: file, fullPage: false });
    return file;
}

async function captureElement(page, fileName, locator) {
    await locator.scrollIntoViewIfNeeded();
    await page.waitForTimeout(250);

    const file = path.join(outDir, fileName);
    await locator.screenshot({ path: file });
    return file;
}

async function substituteGuideIdentity(page, replacements) {
    await page.evaluate((pairs) => {
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        const nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        for (const node of nodes) {
            let value = node.nodeValue;
            for (const [from, to] of pairs) value = value.replaceAll(from, to);
            node.nodeValue = value;
        }
    }, Object.entries(replacements));
}

async function selectCreateExample(page) {
    const serial = page.locator('.js-serial-input').first();
    if (await serial.count() === 0) {
        throw new Error(`Asset create form unavailable at ${page.url()}: ${(await page.locator('body').innerText()).slice(0, 800)}`);
    }
    await serial.fill(guideSerial);
    await page.locator('.js-asset-tag-input').first().evaluate((element, value) => {
        element.value = value;
        element.setAttribute('value', value);
    }, guideAssetTag);

    await page.locator('#select2-category_select_id-container').click();
    await page.getByRole('option', { name: 'Laptops', exact: true }).click();

    await page.locator('#select2-model_select_id-container').click();
    await page.getByRole('option', { name: /HP ProBook 450 G8/ }).click();
    await page.waitForTimeout(500);

    await page.locator('select[name="status_id"]').selectOption('9');
    await page.locator('#use_custom_location').check();
    await page.locator('#location_note').fill('Refurbish werkbank');
}

async function captureRegistration(page, files) {
    await open(page, '/hardware');
    files.push(await capture(page, 'AST-REGISTER-ENTRY-MOBILE-01.png'));

    await open(page, '/hardware/create');
    await selectCreateExample(page);
    files.push(await capture(
        page,
        'AST-REGISTER-IDENTITY-MOBILE-01.png',
        page.locator('.js-asset-tag-input').first(),
    ));
    files.push(await capture(
        page,
        'AST-REGISTER-STATUS-MOBILE-01.png',
        page.locator('select[name="status_id"]'),
    ));
    files.push(await capture(
        page,
        'AST-REGISTER-LOCATION-MOBILE-01.png',
        page.locator('#use_custom_location'),
    ));

    await open(page, '/hardware/4');
    await substituteGuideIdentity(page, {
        'DEMO-004': guideAssetTag,
        'Pixel 8 Pro': guideModel,
        'E5BB04CF-E93F-30F1-9ACC-C95DE9F7C1F8': guideSerial,
    });
    files.push(await capture(page, 'AST-ASSET-SAVED-MOBILE-01.png'));
    files.push(await capture(
        page,
        'AST-LABEL-CONTROL-MOBILE-01.png',
        page.locator('.qr-label-panel'),
    ));
}

async function captureRegistrationSavedCheck(page, files) {
    await page.setViewportSize({ width: 900, height: 900 });
    await open(page, '/hardware/2');
    await substituteGuideIdentity(page, {
        'DEMO-002': guideAssetTag,
        'HP ProBook 430 G7': guideModel,
        '294A0DD2-5C75-37D2-B710-348A518F278B': guideSerial,
    });
    await page.addStyleTag({ content: `
        #details .info-stack-container > .col-md-3.info-stack,
        .qr-label-panel,
        #asset-quality-row { display: none !important; }
        #details .info-stack-container > .col-md-9 { width: 100% !important; }
        #details .row-new-striped > .row { min-height: 0 !important; padding: 6px 12px !important; }
        #asset-status-row .asset-detail-current-value,
        #asset-status-row .asset-detail-control { margin: 0 0 4px !important; padding: 0 !important; }
        #asset-status-row .form-control { height: 34px !important; padding: 4px 10px !important; }
    ` });
    await page.evaluate(() => {
        const root = document.querySelector('#details .row-new-striped');
        if (!root) return;

        const rows = [...root.children].filter((element) => element.classList.contains('row'));
        const keep = new Set([
            document.querySelector('.js-copy-assettag')?.closest('.row'),
            document.querySelector('#asset-status-row'),
            rows.find((row) => row.querySelector('strong')?.textContent.trim() === 'Asset naam'),
            rows.find((row) => row.querySelector('strong')?.textContent.trim() === 'Serienummer'),
        ].filter(Boolean));

        for (const row of rows) {
            if (!keep.has(row)) row.style.display = 'none';
        }
    });
    files.push(await captureElement(
        page,
        'AST-REGISTER-SAVED-CHECK-01.png',
        page.locator('#details .row-new-striped'),
    ));
}

async function captureHandoff(page, files) {
    await open(page, '/hardware/4');
    await substituteGuideIdentity(page, {
        'DEMO-004': guideAssetTag,
        'Pixel 8 Pro': guideModel,
        'E5BB04CF-E93F-30F1-9ACC-C95DE9F7C1F8': guideSerial,
    });
    await page.locator('a[data-toggle="tab"][href="#tests"]').first().click();
    files.push(await capture(
        page,
        'AST-WORKFLOW-PASS-MOBILE-01.png',
        page.getByRole('button', { name: /Standard Diagnostics #4/ }),
    ));

    await open(page, '/hardware/5');
    await substituteGuideIdentity(page, {
        'DEMO-005': guideAssetTag,
        'HP ProBook 450 G7': guideModel,
        '5F070FB7-4D39-3065-8C55-1022003C510E': guideSerial,
    });
    await page.locator('a[data-toggle="tab"][href="#components"]').click();
    files.push(await capture(
        page,
        'AST-COMPONENT-REVIEW-MOBILE-01.png',
        page.locator('#components'),
    ));
    await open(page, '/hardware/5');
    await substituteGuideIdentity(page, {
        'DEMO-005': guideAssetTag,
        'HP ProBook 450 G7': guideModel,
        '5F070FB7-4D39-3065-8C55-1022003C510E': guideSerial,
    });
    files.push(await capture(
        page,
        'AST-QA-HANDOFF-MOBILE-01.png',
        page.locator('#asset-status-row'),
    ));
}

async function captureRelease(page, files) {
    await open(page, '/hardware/5');
    await substituteGuideIdentity(page, {
        'DEMO-005': guideAssetTag,
        'HP ProBook 450 G7': guideModel,
        '5F070FB7-4D39-3065-8C55-1022003C510E': guideSerial,
    });
    await page.locator('a[data-toggle="tab"][href="#tests"]').first().click();
    files.push(await capture(
        page,
        'AST-WORKFLOW-ISSUE-MOBILE-01.png',
        page.getByRole('button', { name: /Standard Diagnostics #5/ }),
    ));

    await open(page, '/hardware/4');
    await substituteGuideIdentity(page, {
        'DEMO-004': guideAssetTag,
        'Pixel 8 Pro': guideModel,
        'E5BB04CF-E93F-30F1-9ACC-C95DE9F7C1F8': guideSerial,
    });
    files.push(await capture(
        page,
        'AST-READY-STATUS-MOBILE-01.png',
        page.locator('#asset-status-row'),
    ));
}

const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 2,
});
const page = await context.newPage();

try {
    await login(page);
    const files = [];
    if (captureMode === 'identity-only') {
        await open(page, '/hardware/4');
        await substituteGuideIdentity(page, {
            'DEMO-004': guideAssetTag,
            'Pixel 8 Pro': guideModel,
            'E5BB04CF-E93F-30F1-9ACC-C95DE9F7C1F8': guideSerial,
        });
        files.push(await capture(page, 'AST-ASSET-SAVED-MOBILE-01.png'));
        files.push(await capture(page, 'AST-LABEL-CONTROL-MOBILE-01.png', page.locator('.qr-label-panel')));

        await open(page, '/hardware/5');
        await substituteGuideIdentity(page, {
            'DEMO-005': guideAssetTag,
            'HP ProBook 450 G7': guideModel,
            '5F070FB7-4D39-3065-8C55-1022003C510E': guideSerial,
        });
        files.push(await capture(page, 'AST-QA-HANDOFF-MOBILE-01.png', page.locator('#asset-status-row')));

        await open(page, '/hardware/4');
        await substituteGuideIdentity(page, {
            'DEMO-004': guideAssetTag,
            'Pixel 8 Pro': guideModel,
            'E5BB04CF-E93F-30F1-9ACC-C95DE9F7C1F8': guideSerial,
        });
        files.push(await capture(page, 'AST-READY-STATUS-MOBILE-01.png', page.locator('#asset-status-row')));
    } else if (captureMode === 'ast03-only') {
        await captureRegistration(page, files);
    } else if (captureMode === 'ast03-saved-check') {
        await captureRegistrationSavedCheck(page, files);
    } else {
        await captureRegistration(page, files);
        await captureHandoff(page, files);
        await captureRelease(page, files);
    }
    console.log(JSON.stringify({ files }, null, 2));
} finally {
    await context.close();
    await browser.close();
}
