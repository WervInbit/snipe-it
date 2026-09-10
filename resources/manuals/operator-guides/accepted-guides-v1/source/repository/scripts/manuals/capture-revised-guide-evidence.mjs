import fs from 'node:fs';
import path from 'node:path';
import {
  browserLaunchOptions,
  captureOutputDir,
  loadGuideDependency,
} from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');

const baseUrl = (process.env.GUIDE_CAPTURE_URL || 'https://dev.inbit/').replace(/\/$/, '');
const username = process.env.GUIDE_CAPTURE_USER;
const password = process.env.GUIDE_CAPTURE_PASSWORD;
const assetTag = process.env.GUIDE_CAPTURE_ASSET_TAG || 'INBIT-HG0001';
const outDir = process.env.GUIDE_CAPTURE_DIR
  || captureOutputDir('revised-guide-set');

if (!username || !password) {
  throw new Error('Set GUIDE_CAPTURE_USER and GUIDE_CAPTURE_PASSWORD before running this script.');
}

fs.mkdirSync(outDir, { recursive: true });

const manifest = {
  capturedAt: new Date().toISOString(),
  environment: 'controlled-development',
  operatorFacingUrl: 'https://snipe.inbit/',
  assetTag,
  captures: [],
  unavailable: [],
};

function safeName(name) {
  return name.replace(/[^A-Za-z0-9._-]+/g, '-');
}

async function login(page) {
  await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.locator('#username').fill(username);
  await page.locator('#password').fill(password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => null),
    page.locator('#submit, button[type="submit"], input[type="submit"]').first().click(),
  ]);
  await page.waitForTimeout(500);
  if (new URL(page.url()).pathname.includes('/login')) {
    const failureFile = path.join(outDir, `capture-login-failure-${Date.now()}.png`);
    await page.screenshot({ path: failureFile, fullPage: true });
    const message = await page.locator('.alert, .alert-msg, .help-block').allInnerTexts().catch(() => []);
    throw new Error(`Controlled capture login did not leave the login page. ${message.join(' ').trim()}`.trim());
  }
}

async function settle(page) {
  await page.waitForLoadState('domcontentloaded');
  await page.waitForTimeout(900);
}

async function capturePage(page, name, url, options = {}) {
  try {
    const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await settle(page);
    if (response && response.status() >= 400) {
      throw new Error(`HTTP ${response.status()}`);
    }
    const file = path.join(outDir, `${safeName(name)}.png`);
    await page.screenshot({ path: file, fullPage: options.fullPage ?? true });
    manifest.captures.push({ name, file, finalPath: new URL(page.url()).pathname, kind: 'page' });
    return true;
  } catch (error) {
    manifest.unavailable.push({ name, reason: error.message });
    return false;
  }
}

async function captureLocator(page, name, locator, options = {}) {
  try {
    const target = locator.first();
    await target.waitFor({ state: 'visible', timeout: options.timeout ?? 5000 });
    const file = path.join(outDir, `${safeName(name)}.png`);
    await target.screenshot({ path: file });
    manifest.captures.push({ name, file, finalPath: new URL(page.url()).pathname, kind: 'locator' });
    return true;
  } catch (error) {
    manifest.unavailable.push({ name, reason: error.message });
    return false;
  }
}

async function captureForViewport(browser, profile) {
  const context = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: profile.viewport,
    deviceScaleFactor: profile.scale,
    isMobile: profile.mobile,
    hasTouch: profile.mobile,
  });
  const page = await context.newPage();
  await login(page);

  await capturePage(page, `BASE-dashboard-${profile.name}`, `${baseUrl}/`, { fullPage: false });
  await capturePage(page, `AST-03-new-asset-${profile.name}`, `${baseUrl}/hardware/create`);
  await captureLocator(page, `AST-03-new-asset-form-${profile.name}`, page.locator('form.form-horizontal, form[method="POST"]'));

  const assetFound = await capturePage(
    page,
    `BASE-asset-detail-${profile.name}`,
    `${baseUrl}/hardware/bytag/${encodeURIComponent(assetTag)}`,
    { fullPage: false },
  );

  if (assetFound) {
    const assetPath = new URL(page.url()).pathname;
    const idMatch = assetPath.match(/^\/hardware\/(\d+)/);
    const assetId = idMatch?.[1];

    await captureLocator(page, `BASE-asset-title-${profile.name}`, page.locator('.content-header, h1').first());
    await captureLocator(
      page,
      `AST-03-label-widget-${profile.name}`,
      page.locator('.qr-label-panel, .qr-label-widget, [data-testid*="qr"], .qr-label-preview').first(),
    );

    if (assetId) {
      await capturePage(page, `AST-04-05-asset-edit-${profile.name}`, `${baseUrl}/hardware/${assetId}/edit`);
      await captureLocator(page, `AST-04-05-status-fields-${profile.name}`, page.locator('#status_id').locator('xpath=ancestor::*[contains(@class,"form-group")][1]'));

      await capturePage(page, `CMP-components-tab-${profile.name}`, `${baseUrl}/hardware/${assetId}#components`, { fullPage: false });
      const componentsTab = page.locator('a[href="#components"]');
      if (await componentsTab.count()) {
        await componentsTab.first().click();
        await page.waitForTimeout(500);
      }
      await captureLocator(page, `CMP-components-panel-${profile.name}`, page.locator('#components'));

      const addAccessible = await capturePage(page, `CMP-add-component-${profile.name}`, `${baseUrl}/hardware/${assetId}/components/add`);
      if (addAccessible) {
        await captureLocator(page, `CMP-existing-component-panel-${profile.name}`, page.locator('form').first());
        const toggle = page.locator('[data-toggle-new-component], button:has-text("Nieuw component"), button:has-text("New component")');
        if (await toggle.count()) {
          await toggle.first().click();
          await page.waitForTimeout(350);
        }
        await captureLocator(page, `CMP-new-component-panel-${profile.name}`, page.locator('form').last());
      }

      await capturePage(page, `WF-tests-${profile.name}`, `${baseUrl}/hardware/${assetId}/tests`);
      await capturePage(page, `WF-active-${profile.name}`, `${baseUrl}/hardware/${assetId}/tests/active`);

      await capturePage(page, `AST-03-label-page-${profile.name}`, `${baseUrl}/hardware/${assetId}/label`);
    } else {
      manifest.unavailable.push({ name: `asset-id-${profile.name}`, reason: `Unexpected asset path: ${assetPath}` });
    }
  }

  await context.close();
}

const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
try {
  await captureForViewport(browser, {
    name: 'wide',
    viewport: { width: 1365, height: 900 },
    scale: 1,
    mobile: false,
  });
  await captureForViewport(browser, {
    name: 'mobile',
    viewport: { width: 390, height: 844 },
    scale: 2,
    mobile: true,
  });
} finally {
  await browser.close();
}

const manifestPath = path.join(outDir, 'capture-manifest.json');
fs.writeFileSync(manifestPath, `${JSON.stringify(manifest, null, 2)}\n`, 'utf8');
console.log(JSON.stringify({ outDir, manifestPath, captured: manifest.captures.length, unavailable: manifest.unavailable.length }));
