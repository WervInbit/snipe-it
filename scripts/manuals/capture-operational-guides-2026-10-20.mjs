import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { browserLaunchOptions, loadGuideDependency, repoRoot } from './lib/guide-paths.mjs';

const round = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-20-operational-guides');
assert.ok(!fs.existsSync(path.join(round, 'manifest.sha256')), 'Retained evidence must not be overwritten.');
const fixture = JSON.parse(fs.readFileSync(path.join(round, 'fixture.json'), 'utf8'));
assert.equal(fixture.asset_tag, 'GUIDE-20261020-01');
const out = path.join(round, 'evidence');
fs.mkdirSync(out, { recursive: true });
const { chromium } = loadGuideDependency('playwright');
const sharp = loadGuideDependency('sharp');
const browser = await chromium.launch(browserLaunchOptions({ headless: true }));
const password = process.env.GUIDE_CAPTURE_PASSWORD;
assert.ok(password, 'Pass the controlled demo password at the command boundary.');
const captures = [];
const mode = process.argv[2] ?? 'start';
async function capture(page, id) {
    await page.waitForTimeout(350);
    const state = await page.evaluate(() => {
        const box = e => { const r = e.getBoundingClientRect(); return { x:r.x + scrollX, y:r.y + scrollY, w:r.width, h:r.height }; };
        return { language:document.documentElement.lang, url:location.pathname + location.hash, text:document.body.innerText,
            elements:[...document.querySelectorAll('.breadcrumb,.nav-tabs,.testing-card,[data-testid="workflow-step"],[data-testid="workflow-done-area"],.testing-card [data-action],.testing-card textarea,.testing-card [data-note-saved],.testing-card [data-photo-gallery],.testing-card .testing-drawer,#details .row,#details dl,#details tr,#details label,#details select,#details textarea,#details .workflow-sale-readiness,section.workflow-progression')].map(e => ({ tag:e.tagName,id:e.id,action:e.getAttribute('data-action'),testid:e.getAttribute('data-testid'),profile:e.getAttribute('data-workflow-profile-id'),classes:e.className,text:e.innerText?.slice(0,600),bounds:box(e) })),
            links:[...document.querySelectorAll('a[href]')].map(e => ({text:e.innerText.trim(),href:e.getAttribute('href'),bounds:box(e)})) };
    });
    assert.equal(state.language, 'nl-NL', 'Dutch evidence required');
    await page.screenshot({path:path.join(out, `${id}.png`),fullPage:true});
    fs.writeFileSync(path.join(out, `${id}.json`), JSON.stringify(state,null,2));
    captures.push({id,locale:state.language,path:state.url,user:'demo_refurbisher',assetId:fixture.asset_id});
    console.log(JSON.stringify({id,path:state.url,text:state.text.slice(0,220)}));
    return state;
}
try {
    const context = await browser.newContext({ignoreHTTPSErrors:true,viewport:{width:415,height:915},deviceScaleFactor:2,isMobile:true,hasTouch:true});
    const page = await context.newPage();
    await page.goto('https://dev.inbit/login',{waitUntil:'networkidle'});
    await page.locator('input[name="username"]').fill('demo_refurbisher');
    await page.locator('input[name="password"]').fill(password);
    await Promise.all([page.waitForURL(u => !u.pathname.endsWith('/login')),page.locator('button[type="submit"]').click()]);
    await page.goto(`https://dev.inbit/hardware/${fixture.asset_id}`,{waitUntil:'networkidle'});
    assert.ok((await page.locator('body').innerText()).includes(fixture.asset_tag));
    if (mode === 'note-proof') {
        const note=page.locator(`#status_change_note_detail_${fixture.asset_id}`);
        await note.fill('Handleidingdemonstratie: werk en controle genoteerd; gereed voor beoordeling. Geen fysiek apparaat overgedragen.');
        const box=await note.boundingBox();
        await page.mouse.move(box.x+box.width-3,box.y+box.height-3);
        await page.mouse.down();
        await page.mouse.move(box.x+box.width-3,box.y+box.height+15,{steps:8});
        await page.mouse.up();
        await note.evaluate(e => { e.scrollTop=0; });
        await capture(page,'HANDOFF-NOTE-NL-20261020');
    } else if (mode === 'geometry') {
        const landmarkGeometry = await page.evaluate(() => {
            const names = new Set(['Assettag','Serienummer','Status','Statusnotitie','Required workflows']);
            const b = e => { const r=e.getBoundingClientRect();return {x:r.x,y:r.y+scrollY,w:r.width,h:r.height}; };
            return [...document.querySelectorAll('*')].filter(e => e.children.length===0 && names.has(e.textContent.trim())).map(e => ({text:e.textContent.trim(),bounds:b(e),parent:b(e.parentElement),parentText:e.parentElement.innerText,grand:b(e.parentElement.parentElement),grandText:e.parentElement.parentElement.innerText?.slice(0,500)}));
        });
        fs.writeFileSync(path.join(out,'layout-geometry.json'),JSON.stringify(landmarkGeometry,null,2));
        console.log(JSON.stringify(landmarkGeometry));
    } else if (mode === 'saved') {
        assert.equal(await page.locator(`#status_select_detail_${fixture.asset_id}`).inputValue(),'10');
        await capture(page, 'HANDOFF-SAVED-NL-20261020');
        await page.locator('a[href="#history"]').first().click();
        await page.waitForTimeout(800);
        await capture(page, 'HANDOFF-HISTORY-NL-20261020');
        assert.ok((await page.locator('body').innerText()).includes('Handleidingdemonstratie: werk en controle genoteerd; gereed voor beoordeling.'), 'Saved handoff note not visible in history');
    } else if (mode === 'start') {
        assert.equal(fixture.runs.length, 0, 'Start stage requires an untouched owned fixture.');
        await capture(page, 'WF-ASSET-NL-20261020');
        await page.locator('a[href="#tests"]').first().click();
        await capture(page, 'WF-ENTRY-NL-20261020');
        const row = page.locator('[data-testid="workflow-step"][data-workflow-profile-id="1"]');
        await Promise.all([page.waitForURL(u => /active/.test(u.pathname)),row.locator('button[name="workflow_profile_id"][value="1"]').click()]);
        await capture(page, 'WF-CARDS-NL-20261020');
        const card = page.locator('.testing-card').first();
        const help = card.locator('[data-action="toggle-help"]');
        if (await help.count()) {
            await help.click();
            await capture(page, 'WF-INSTRUCTIONS-NL-20261020');
            await help.click();
        }
        await card.locator('[data-action="toggle-note"]').click();
        await card.locator('textarea').fill('Demonstratie voor de handleiding; geen fysieke controle.');
        await page.waitForTimeout(1700);
        assert.ok((await card.locator('[data-note-saved]').innerText()).trim(), 'Note has no save response');
        await capture(page, 'WF-NOTE-NL-20261020');
        await card.locator('[data-action="toggle-note"]').click();
        await card.locator('[data-action="toggle-photos"]').click();
        const demoPhoto = path.join(out, 'handleiding-demonstratie-upload.png');
        await sharp(Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="640" height="420"><rect width="640" height="420" fill="#e5edf4"/><text x="30" y="160" font-family="sans-serif" font-size="30">Handleiding: demonstratie-upload</text><text x="30" y="220" font-family="sans-serif" font-size="24">Geen fysieke apparaatcontrole.</text></svg>')).png().toFile(demoPhoto);
        await card.locator('input[type="file"]').setInputFiles(demoPhoto);
        await card.locator('[data-photo-node]').first().waitFor();
        await capture(page, 'WF-PHOTO-NL-20261020');
        await card.locator('[data-action="toggle-photos"]').click();
        await card.locator('[data-action="set-pass"]').click();
        await page.waitForTimeout(900);
        await capture(page, 'WF-ANSWER-NL-20261020');
        await page.goto(`https://dev.inbit/hardware/${fixture.asset_id}#tests`,{waitUntil:'networkidle'});
        await capture(page, 'WF-CONTINUE-NL-20261020');
    } else if (mode === 'finish') {
        assert.ok(fixture.prepared_demonstration_results, 'Prepare only owned demonstration results first.');
        await page.locator('a[href="#tests"]').first().click();
        await page.locator('[data-testid="workflow-step"][data-workflow-profile-id="1"] a.btn-primary').click();
        await page.waitForLoadState('networkidle');
        await capture(page, 'WF-DONE-NL-20261020');
        await page.locator('#tests-complete-btn').click();
        await page.waitForURL(u => u.pathname === `/hardware/${fixture.asset_id}`);
        await capture(page, 'WF-RETURN-NL-20261020');
        await page.locator('a[href="#details"]').first().click();
        await capture(page, 'HANDOFF-READY-NL-20261020');
        await page.locator('a[href="#components"]').first().click();
        await capture(page, 'HANDOFF-COMPONENTS-NL-20261020');
        await page.locator('a[href="#tests"]').first().click();
        await page.locator('[data-testid="workflow-step"][data-workflow-profile-id="3"] a.btn-primary').click();
        await page.waitForLoadState('networkidle');
        await capture(page, 'WF-TASK-NL-20261020');
        await page.goto(`https://dev.inbit/hardware/${fixture.asset_id}`,{waitUntil:'networkidle'});
        await page.locator('a[href="#details"]').first().click();
        const note = page.locator(`#status_change_note_detail_${fixture.asset_id}`);
        await note.fill('Handleidingdemonstratie: werk en controle genoteerd; gereed voor beoordeling. Geen fysiek apparaat overgedragen.');
        await capture(page, 'HANDOFF-NOTE-NL-20261020');
        await page.locator(`#status_select_detail_${fixture.asset_id}`).selectOption({label:'QA Hold'});
        await page.waitForTimeout(1500);
        await page.reload({waitUntil:'networkidle'});
        assert.equal(await page.locator(`#status_select_detail_${fixture.asset_id}`).inputValue(),'10');
        await capture(page, 'HANDOFF-SAVED-NL-20261020');
        await page.locator('a[href="#history"]').first().click();
        await page.waitForTimeout(800);
        await capture(page, 'HANDOFF-HISTORY-NL-20261020');
    } else throw new Error('Unknown capture stage');
    await context.close();
} finally {
    await browser.close();
    const file = path.join(round,'capture-summary.json');
    const previous = fs.existsSync(file) ? JSON.parse(fs.readFileSync(file)).captures : [];
    fs.writeFileSync(file,JSON.stringify({capturedAt:new Date().toISOString(),assetId:fixture.asset_id,captures:[...new Map([...previous,...captures].map(c=>[c.id,c])).values()]},null,2));
}
