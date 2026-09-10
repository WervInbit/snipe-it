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
const exampleComponentDefinitionId = process.env.SNIPEIT_CATALOG_COMPONENT_DEFINITION_ID || '1';

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

async function scrollTo(page, selector, offset = -120) {
    await page.locator(selector).first().scrollIntoViewIfNeeded();
    await page.evaluate((scrollOffset) => window.scrollBy(0, scrollOffset), offset);
    await page.waitForTimeout(300);
}

async function useSupervisorSpecificationView(page) {
    await page.evaluate(() => {
        document.querySelectorAll('.js-remove-assigned, .js-component-template-remove')
            .forEach((control) => control.remove());
    });
}

async function addDirectAttributeExample(page) {
    const search = page.locator('.attribute-column--available .js-attribute-search');
    await search.fill('5G-ondersteuning');
    const row = page.locator('#available-attributes-list .available-attribute')
        .filter({ hasText: '5G-ondersteuning' });
    await row.locator('.js-add-attribute').click();
    await page.locator('#attribute_15').selectOption('1');
    await page.waitForTimeout(250);
}

async function clearExpectedComponentRows(page) {
    await page.evaluate(() => {
        document.querySelectorAll('[data-component-template-row]').forEach((row) => row.remove());
        document.querySelector('[data-component-template-empty]')?.classList.remove('hidden');
    });
}

async function addExpectedComponentEntry(page) {
    await page.locator('[data-add-component-template]').click();
    const row = page.locator('[data-component-template-row]').last();
    const select = row.locator('.js-component-template-definition-select');
    await select.selectOption({ label: 'RAM 8GB DDR4' });
    await select.dispatchEvent('change');
    await row.locator('input[type="number"]').fill('1');
    await page.waitForTimeout(250);
}

async function injectModelSpecificationConflict(page) {
    await page.evaluate(() => {
        const existing = document.querySelector('[data-testid="model-spec-component-conflict-warning"]');
        if (existing) existing.remove();
        const warning = document.createElement('div');
        warning.className = 'col-md-12';
        warning.innerHTML = `
            <div class="alert alert-warning" data-testid="model-spec-component-conflict-warning">
                <strong>Component specification conflict</strong>
                <ul class="mb-0">
                    <li><strong>Werkgeheugen</strong>: Manual model value 16 GB differs from component value 8 GB. Component value is being used.</li>
                </ul>
            </div>`;
        const selector = document.querySelector('#model_spec_model_number_id');
        selector?.parentElement?.insertBefore(warning, selector);
    });
    await page.waitForTimeout(200);
}

async function injectModelSpecificationSuccess(page) {
    await page.evaluate(() => {
        const content = document.querySelector('section.content');
        if (!content) throw new Error('Specification content section not found.');
        const success = document.createElement('div');
        success.className = 'alert alert-success';
        success.textContent = 'Model specification updated.';
        content.insertBefore(success, content.firstChild);
    });
    await page.waitForTimeout(200);
}

async function prepareAttributeExample(page, { datatype = 'int' } = {}) {
    await page.locator('#label').fill('Aantal geheugenslots');
    await page.locator('#datatype').selectOption(datatype);
    await page.locator('#category_ids').selectOption({ label: 'Laptops' });
    await page.locator('#category_ids').dispatchEvent('change');
    await page.waitForTimeout(250);
}

async function addEnumOption(page, value, label, sort) {
    await page.locator('#new_option_value').fill(value);
    await page.locator('#new_option_label').fill(label);
    await page.locator('#new_option_sort').fill(String(sort));
    await page.locator('[data-option-add]').click();
}

async function injectAttributeResultExample(page) {
    await page.evaluate(() => {
        const body = document.querySelector('table tbody');
        if (!body) throw new Error('Attribute result table body not found.');
        body.innerHTML = `
            <tr data-capture-example-row>
                <td>Aantal geheugenslots</td>
                <td><code>aantal_geheugenslots</code></td>
                <td>Int</td>
                <td><span class="label label-success">Active</span></td>
                <td>Laptops</td>
                <td><span class="text-muted">--</span></td>
                <td><span class="text-muted">--</span></td>
                <td>0</td>
                <td><button type="button" class="btn btn-default btn-xs">Edit</button></td>
            </tr>`;
    });
    await page.waitForTimeout(200);
}

async function injectHierarchyOverlapExample(page) {
    await page.evaluate(() => {
        const section = document.querySelector('#expected-subcomponents');
        const rows = section?.querySelector('[data-subcomponent-template-rows]');
        if (!section || !rows) throw new Error('Expected-subcomponent section not found.');
        const existing = section.querySelector('[data-testid="component-definition-hierarchy-overlap-warning"]');
        if (existing) existing.remove();
        const warning = document.createElement('div');
        warning.className = 'alert alert-warning';
        warning.dataset.testid = 'component-definition-hierarchy-overlap-warning';
        warning.innerHTML = `
            <strong>Hierarchy overlap warning</strong>
            <ul style="margin-bottom:0; padding-left:18px;">
                <li>Motherboard and RAM 8GB DDR4 both contribute Geheugentype.
                    <span class="text-muted">Attached child values override parent values for calculated asset specs.</span>
                </li>
            </ul>`;
        section.insertBefore(warning, rows);
    });
    await page.waitForTimeout(200);
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

    if (captureMode === 'all' || captureMode === 'core' || captureMode === 'number-search') {
        await open(page, '/admin/settings/model-numbers?search=2E9F8EA%23ABH');
        await page.getByRole('heading', { name: /Model Numbers/i }).first().waitFor();
        files.push(await capture(page, 'CAT-MODEL-NUMBER-SEARCH-DESKTOP-01.png'));
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

    if (captureMode === 'all' || captureMode === 'model-spec') {
        const specRoute = `/models/${exampleModelId}/model-numbers/${exampleModelNumberId}/spec`;

        await open(page, specRoute);
        await page.getByRole('heading', { name: 'Expected Components', exact: true }).waitFor();
        await useSupervisorSpecificationView(page);
        await addDirectAttributeExample(page);
        await scrollTo(page, '[data-testid="model-attributes-builder"]', -125);
        files.push(await capture(page, 'CAT-MODEL-SPEC-ATTRIBUTE-ADD-DESKTOP-01.png'));

        await open(page, specRoute);
        await page.getByRole('heading', { name: 'Expected Components', exact: true }).waitFor();
        await useSupervisorSpecificationView(page);
        await clearExpectedComponentRows(page);
        await scrollTo(page, '[data-add-component-template]', 0);
        files.push(await capture(page, 'CAT-MODEL-SPEC-EXPECTED-START-DESKTOP-01.png'));
        await addExpectedComponentEntry(page);
        await scrollTo(page, '#expected-components', -115);
        files.push(await capture(page, 'CAT-MODEL-SPEC-EXPECTED-ADD-DESKTOP-01.png'));

        await open(page, specRoute);
        await page.getByRole('heading', { name: 'Expected Components', exact: true }).waitFor();
        await useSupervisorSpecificationView(page);
        await injectModelSpecificationConflict(page);
        await page.evaluate(() => window.scrollTo(0, 0));
        files.push(await capture(page, 'CAT-MODEL-SPEC-CONFLICT-DESKTOP-01.png'));

        await open(page, specRoute);
        await page.getByRole('heading', { name: 'Expected Components', exact: true }).waitFor();
        await useSupervisorSpecificationView(page);
        await page.evaluate(() => {
            const rows = [...document.querySelectorAll('[data-component-template-row]')];
            rows.forEach((row) => {
                const selected = row.querySelector('select')?.selectedOptions[0]?.textContent ?? '';
                if (!selected.includes('RAM 8GB DDR4')) row.remove();
            });
        });
        await page.getByRole('button', { name: /Opslaan|Save/i }).last().evaluate((button) => {
            button.scrollIntoView({ block: 'center' });
        });
        await page.waitForTimeout(300);
        files.push(await capture(page, 'CAT-MODEL-SPEC-SAVE-DESKTOP-01.png'));

        await open(page, specRoute);
        await page.getByRole('heading', { name: 'Expected Components', exact: true }).waitFor();
        await useSupervisorSpecificationView(page);
        await page.locator('#expected-components').scrollIntoViewIfNeeded();
        await page.evaluate(() => window.scrollBy(0, -110));
        await page.waitForTimeout(250);
        files.push(await capture(page, 'CAT-MODEL-SPEC-ROSTER-DESKTOP-01.png'));

        await open(page, specRoute);
        await page.getByRole('heading', { name: 'Expected Components', exact: true }).waitFor();
        await useSupervisorSpecificationView(page);
        await injectModelSpecificationSuccess(page);
        await page.evaluate(() => window.scrollTo(0, 0));
        files.push(await capture(page, 'CAT-MODEL-SPEC-SAVED-DESKTOP-01.png'));
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

    if (captureMode === 'all' || captureMode === 'catalog-admin' || captureMode === 'attribute-definitions') {
        await open(page, '/attributes?search=geheugen');
        await page.getByRole('heading', { name: /Attributes/i }).first().waitFor();
        await expandSettingsNavigation(page);
        files.push(await capture(page, 'CAT-ATTRIBUTE-ENTRY-DESKTOP-01.png'));

        await open(page, '/attributes/create');
        await page.getByRole('heading', { name: /Create Attribute/i }).first().waitFor();
        await prepareAttributeExample(page);
        files.push(await capture(page, 'CAT-ATTRIBUTE-CREATE-IDENTITY-DESKTOP-01.png'));

        await page.locator('#constraints_min').fill('0');
        await page.locator('#constraints_max').fill('8');
        await page.locator('#constraints_step').fill('1');
        await scrollTo(page, '#constraints_min', -170);
        files.push(await capture(page, 'CAT-ATTRIBUTE-CONSTRAINTS-NUMERIC-DESKTOP-01.png'));

        await scrollTo(page, '#submit_button', 100);
        files.push(await capture(page, 'CAT-ATTRIBUTE-SAVE-DESKTOP-01.png'));

        await open(page, '/attributes/create');
        await page.getByRole('heading', { name: /Create Attribute/i }).first().waitFor();
        await prepareAttributeExample(page, { datatype: 'enum' });
        await page.locator('#label').fill('Geheugentype');
        await addEnumOption(page, 'ddr4', 'DDR4', 10);
        await addEnumOption(page, 'ddr5', 'DDR5', 20);
        await scrollTo(page, '[data-attribute-options-wrapper]', -115);
        files.push(await capture(page, 'CAT-ATTRIBUTE-OPTIONS-ENUM-DESKTOP-01.png'));

        await open(page, '/attributes?search=aantal_geheugenslots');
        await page.getByRole('heading', { name: /Attributes/i }).first().waitFor();
        await injectAttributeResultExample(page);
        files.push(await capture(page, 'CAT-ATTRIBUTE-RESULT-DESKTOP-01.png'));
    }

    if (captureMode === 'all' || captureMode === 'catalog-admin' || captureMode === 'component-definitions') {
        const componentSearch = encodeURIComponent('Motherboard - HP ProBook 450 G8');
        await open(page, `/admin/settings/component-definitions?search=${componentSearch}`);
        await page.getByRole('heading', { name: 'Component Definitions', exact: true }).last().waitFor();
        await page.locator('form button.btn-warning, form button.btn-success').evaluateAll((buttons) => buttons.forEach((button) => button.closest('form')?.remove()));
        await expandSettingsNavigation(page);
        files.push(await capture(page, 'CAT-COMPONENT-DEFINITION-ENTRY-DESKTOP-01.png'));

        await open(page, `/admin/settings/component-definitions/${exampleComponentDefinitionId}/edit`);
        await page.getByRole('heading', { name: /Edit Component Definition/i }).first().waitFor();
        files.push(await capture(page, 'CAT-COMPONENT-DEFINITION-IDENTITY-DESKTOP-01.png'));

        await scrollTo(page, '#expected-subcomponents', -105);
        files.push(await capture(page, 'CAT-COMPONENT-DEFINITION-CHILDREN-DESKTOP-01.png'));

        await scrollTo(page, '[data-contribution-rows]', -105);
        files.push(await capture(page, 'CAT-COMPONENT-DEFINITION-CONTRIBUTIONS-DESKTOP-01.png'));

        await injectHierarchyOverlapExample(page);
        await scrollTo(page, '[data-testid="component-definition-hierarchy-overlap-warning"]', -105);
        files.push(await capture(page, 'CAT-COMPONENT-DEFINITION-OVERLAP-DESKTOP-01.png'));

        await scrollTo(page, '.box-footer', 100);
        files.push(await capture(page, 'CAT-COMPONENT-DEFINITION-SAVE-DESKTOP-01.png'));
    }

    console.log(JSON.stringify({
        files,
        baseUrl,
        captureMode,
        exampleModelId,
        exampleModelNumberId,
        exampleComponentDefinitionId,
    }, null, 2));
} finally {
    await context.close();
    await browser.close();
}
