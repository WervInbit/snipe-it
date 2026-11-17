import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { browserLaunchOptions, loadGuideDependency, repoRoot } from './lib/guide-paths.mjs';

const { chromium } = loadGuideDependency('playwright');
const round = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-08-test-guides');
const out = path.join(round, 'evidence');
const password = process.env.GUIDE_CAPTURE_PASSWORD;
assert.ok(password, 'Set the controlled demo capture password at the command boundary.');
fs.mkdirSync(out, { recursive: true });
const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
const captures = [];
const savedOnly = process.argv.includes('--saved-result-only');
async function inspect(page) {
    return page.evaluate(() => ({
        language: document.documentElement.lang,
        title: document.title,
        text: document.body.innerText,
        links: [...document.querySelectorAll('a[href]')].map(a => ({ text: a.innerText.trim(), href: a.getAttribute('href'), title: a.title })),
        inputs: [...document.querySelectorAll('input,select,textarea,button')].map(e => {
            const r = e.getBoundingClientRect();
            return { tag: e.tagName, id: e.id, name: e.name, type: e.type, text: e.tagName === 'BUTTON' ? e.innerText : undefined,
                options: e.tagName === 'SELECT' ? [...e.options].map(o => ({ value: o.value, text: o.text })) : undefined,
                bounds: { x:r.x, y:r.y, w:r.width, h:r.height } };
        }),
        landmarks: [...document.querySelectorAll('.breadcrumb,.nav-tabs,#details,#tests,#history,#components,table tr,.modal.in .modal-content')].map(e => {
            const r=e.getBoundingClientRect();
            return { tag:e.tagName,id:e.id,classes:e.className,text:e.innerText?.slice(0,200),bounds:{x:r.x,y:r.y+scrollY,w:r.width,h:r.height} };
        }),
    }));
}
async function capture(page, id, user) {
    await page.waitForTimeout(450);
    const state = await inspect(page);
    const file = `${id}.png`;
    await page.screenshot({ path: path.join(out,file), fullPage:true });
    fs.writeFileSync(path.join(out,`${id}.json`), JSON.stringify(state,null,2));
    captures.push({ id, file, user, path: new URL(page.url()).pathname, locale:state.language });
    console.log(JSON.stringify({ id, path:new URL(page.url()).pathname, locale:state.language, text:state.text.slice(0,520) }));
    return state;
}
try {
    for (const user of savedOnly ? ['demo_supervisor'] : ['demo_refurbisher','demo_supervisor']) {
        const context = await browser.newContext({ ignoreHTTPSErrors:true, viewport:{width:415,height:915},deviceScaleFactor:2,isMobile:true,hasTouch:true });
        const page = await context.newPage();
        await page.goto('https://dev.inbit/login',{waitUntil:'networkidle'});
        if (user === 'demo_refurbisher') await capture(page,'LOGIN-NL-MOBILE-20261008',user);
        await page.locator('input[name="username"]').fill(user);
        await page.locator('input[name="password"]').fill(password);
        await Promise.all([page.waitForURL(u => !u.pathname.endsWith('/login')),page.locator('button[type="submit"]').click()]);
        if (savedOnly) {
            await page.goto('https://dev.inbit/hardware/17',{waitUntil:'networkidle'});
            assert.equal(await page.locator('#status_select_detail_17').inputValue(),'6');
            if (!fs.existsSync(path.join(out, 'AST-SAVED-NL-MOBILE-20261008.png'))) await capture(page,'AST-SAVED-NL-MOBILE-20261008',user);
            await page.locator('a[href="#history"]').first().click();
            await page.waitForTimeout(1000);
            await capture(page,'AST-HISTORY-NL-MOBILE-20261008',user);
            await page.locator('a[href="#components"]').first().click();
            await capture(page,'AST-COMPONENTS-NL-MOBILE-20261008',user);
            await context.close();
            continue;
        }
        const closeAlert = page.locator('.alert-success button.close');
        if (await closeAlert.isVisible()) await closeAlert.click();
        const dashboard = await capture(page,`DASH-${user}-NL-MOBILE-20261008`,user);
        assert.ok(dashboard.language.startsWith('nl'),'Dutch account capture required.');
        if (user === 'demo_refurbisher') {
            const scanner = dashboard.links.find(a => /scan/i.test(a.href ?? '') && !/\/settings\//.test(a.href));
            assert.ok(scanner,'No scanner destination observed.');
            await page.goto(new URL(scanner.href,'https://dev.inbit').href,{waitUntil:'networkidle'});
            await capture(page,'SCANNER-NL-MOBILE-20261008',user);
            await page.locator('#scan-manual-code').fill('GUIDE-20261008-01');
            await capture(page,'SC-MANUAL-NL-MOBILE-20261008',user);
            await Promise.all([
                page.waitForURL(u => u.pathname === '/hardware/17'),
                page.getByRole('button',{name:'Zoeken',exact:true}).click(),
            ]);
            await capture(page,'SC-IDENTITY-NL-MOBILE-20261008',user);
        }
        await page.goto('https://dev.inbit/hardware/17',{waitUntil:'networkidle'});
        await capture(page,`ASSET-${user}-NL-MOBILE-20261008`,user);
        if (user === 'demo_supervisor') {
            await page.locator('a[href="#tests"]').first().click();
            await capture(page,'AST-WORKFLOWS-NL-MOBILE-20261008',user);
            await page.locator('a[href="#details"]').first().click();
            const select = page.locator('#status_select_detail_17');
            assert.equal(await select.inputValue(),'10','The owned demonstration record must still be on QA Hold.');
            assert.ok((await page.locator('body').innerText()).includes('GUIDE-20261008-01'),'Unexpected asset identity.');
            await page.locator('#status_change_note_detail_17').fill('Demonstratie: beoordeling afgerond; geen fysiek apparaat vrijgegeven.');
            await capture(page,'AST-STATUS-NL-MOBILE-20261008',user);
            await select.selectOption({label:'Ready for Sale'});
            const modal=page.locator('#asset-status-confirmation-17');
            await modal.waitFor({state:'visible'});
            await page.waitForTimeout(400);
            await capture(page,'AST-CONFIRM-NL-MOBILE-20261008',user);
            if (process.argv.includes('--complete-demo-release')) {
                await modal.locator('[data-status-guard-confirm]').click();
                await page.waitForTimeout(1500);
                await page.reload({waitUntil:'networkidle'});
                assert.equal(await page.locator('#status_select_detail_17').inputValue(),'6','Demonstration result did not persist.');
                await capture(page,'AST-SAVED-NL-MOBILE-20261008',user);
            } else {
                await modal.locator('[data-dismiss="modal"]').last().click();
            }
        }
        await context.close();
    }
} finally {
    await browser.close();
    const summaryFile=path.join(round,'capture-summary.json');
    const previous=fs.existsSync(summaryFile)?JSON.parse(fs.readFileSync(summaryFile)).captures:[];
    const combined=[...new Map([...previous,...captures].map(c=>[c.id,c])).values()];
    fs.writeFileSync(summaryFile,JSON.stringify({capturedAt:new Date().toISOString(),environment:'controlled-development',assetId:17,captures:combined},null,2));
}
