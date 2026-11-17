import fs from 'node:fs';
import path from 'node:path';
import {SvgGuideDocument, GUIDE_TOKENS, GUIDE_FAMILIES, guideReference,
    drawContextStrip, drawCompletionRow} from './guide-system.mjs';
import {repoRoot, loadGuideDependency} from './guide-paths.mjs';

const base = path.join(repoRoot, 'resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides');
const manifest = JSON.parse(fs.readFileSync(path.join(base, 'authoring-manifest.json'), 'utf8'));
const sharp = loadGuideDependency('sharp'), images = {}, crops = {}, provenance = {};
const C = GUIDE_TOKENS.colors;

export async function source(key, sourceName, crop) {
    const file = path.join(base, 'evidence', /\.(png|jpe?g)$/.test(sourceName) ? sourceName : sourceName + '.png');
    const bytes = fs.readFileSync(file), meta = await sharp(bytes).metadata();
    images[key] = {href: `data:image/${meta.format === 'jpeg' ? 'jpeg' : 'png'};base64,` + bytes.toString('base64'), width: meta.width, height: meta.height};
    crops[key] = crop;
    provenance[key] = {source: path.relative(repoRoot, file).replaceAll('\\', '/'), cropPixels: crop};
}
export async function retained(key, original = key) {
    const s = manifest.sources.find(s => s.key === original);
    const file = path.join(repoRoot, s.source), bytes = fs.readFileSync(file), meta = await sharp(bytes).metadata();
    images[key] = {href: `data:image/${meta.format === 'jpeg' ? 'jpeg' : 'png'};base64,` + bytes.toString('base64'), width: meta.width, height: meta.height};
    crops[key] = s.cropPixels ?? {x: 0, y: 0, w: meta.width, h: meta.height};
    provenance[key] = {...s};
}
export const text = (d, x, y, lines, o = {}) => d.text(x, y, lines, {size: 2.65, lh: 3.8, ...o});
export const caption = (d, x, y, line) => text(d, x, y, line, {size: 2.25, fill: C.muted});
export function page(code, version, n, total, purpose, role, need, options = {}) {
    const d = new SvgGuideDocument(images, `final-${code}-p${n}`), pal = GUIDE_FAMILIES[code.split('-')[0]];
    d.rect(12, 13, 2, 16, pal.color);
    text(d, 18, 22, code + ' ' + guideReference(code).title, {size: code === 'USR-01' ? 6.0 : 5.9, weight: 900});
    text(d, 18, 29.5, purpose, {size: 2.8, fill: C.muted});
    d.rect(164, 10, 34, 13, pal.fill, pal.color, .45, 1.4);
    d.centeredText(181, 14.6, 'Versie ' + version, {size: 2.5, weight: 800, fill: pal.color});
    d.centeredText(181, 19.5, `04-11-2026 | ${n}/${total}`, {size: 2.15, fill: pal.color});
    drawContextStrip(d, [{label: 'Rol', value: role}, {label: 'Nodig', value: need},
        options.context ?? (n === 1 ? {label: 'Vooraf', guideCode: options.before ?? 'AC-01', size: options.before === 'CAT-01' ? 2.15 : 2.75} : {label: 'Vervolg', value: 'Dezelfde taak; vorige zijde'})], {y: 34, height: 17});
    text(d, 12, 296, `Inbit Snipe-IT | ${code} v${version} | 04-11-2026`, {size: 1.65, fill: C.muted});
    text(d, 198, 296, `${n}/${total} | https://snipe.inbit/`, {size: 1.65, anchor: 'end', fill: C.muted});
    return d;
}
export function step(d, code, n, y, h, title) {
    d.raw(`<g id="step-${n}" data-component="task-step" data-number="${n}">`);
    d.rect(12, y, 186, h, 'white', C.line, .45, 2, 1, {component: 'step-frame'});
    d.stepBadge(21, y + 9, n, GUIDE_FAMILIES[code.split('-')[0]].color);
    text(d, 32, y + 10.5, title, {size: 3.5, weight: 900, fill: GUIDE_FAMILIES[code.split('-')[0]].color});
}
export const end = d => d.raw('</g>');
export function figure(d, code, key, x, y, w, label, captionLine, options = {}) {
    const crop = crops[key], h = w * crop.h / crop.w;
    d.raw(`<g data-component="figure" data-source-key="${key}" data-label="${label}">`);
    const placed = d.image(key, {x, y, w, h}, crop, {fit: 'contain'});
    for (const m of options.marks ?? []) d.focusMark(placed, m);
    d.imageBadge(options.badgeX ?? x + 2, options.badgeY ?? y + 1, label, GUIDE_FAMILIES[code.split('-')[0]].color);
    if (captionLine) caption(d, x, y + h + 3.7, captionLine);
    d.raw('</g>');
    return {x, y, w, h};
}
export function saveBox(name) {
    return controlBox(name, /Opslaan|Save Changes/);
}
export function controlBox(name, pattern) {
    const s = JSON.parse(fs.readFileSync(path.join(base, 'evidence', name + '.json'), 'utf8'));
    const b = s.elements.find(e => e.tag === 'BUTTON' && pattern.test(e.text)).bounds;
    return {x: Math.floor((b.x - 6) * s.viewport.dpr), y: Math.floor((b.y - 5) * s.viewport.dpr),
        w: Math.ceil((b.w + 12) * s.viewport.dpr), h: Math.ceil((b.h + 10) * s.viewport.dpr)};
}

export async function finalScenes() {
    for (const key of ['usr-nav', 'usr-credentials', 'usr-detail', 'usr-group', 'usr-saved', 'usr-reset', 'usr-disable', 'usr-disabled',
        'usr-permissions-head', 'usr-assets', 'status-user', 'cmp-entry', 'cmp-existing', 'cmp-to-tray', 'cmp-result', 'cmp-create',
        'group-gear', 'group-nav', 'group-list', 'group-top', 'group-models', 'group-save', 'group-detail',
        'cmp-tray-confirm', 'cmp-tray-submit', 'cmp-tray-status', 'cmp-tray-asset']) await retained(key);
    await source('user-list-tools', 'USR-NAV', {x: 1222, y: 262, w: 675, h: 165});
    await source('user-name-fields', 'USR-NEW-IDENTITY', {x: 382, y: 312, w: 915, h: 225});
    await source('user-email-field', 'USR-NEW-IDENTITY', {x: 382, y: 751, w: 915, h: 86});
    await source('user-create-save', 'USR-NEW-IDENTITY', saveBox('USR-NEW-IDENTITY'));
    await source('user-edit-save', 'USR-GROUP-CHOSEN', saveBox('USR-GROUP-CHOSEN'));
    await source('user-reset-save', 'USR-RESET-CONTROL', saveBox('USR-RESET-CONTROL'));
    await source('user-disable-save', 'USR-DISABLE-FORM', saveBox('USR-DISABLE-FORM'));
    await source('user-rights-save', 'USR-PERMISSIONS', saveBox('USR-PERMISSIONS'));
    await source('status-user-save', 'STATUS-USER-TARGET', saveBox('STATUS-USER-TARGET'));
    await source('tray-status-readable', 'CMP-TRAY-DETAIL', {x: 40, y: 884, w: 260, h: 80});
    await source('tray-asset-readable', 'CMP-TRAY-DETAIL', {x: 40, y: 1510, w: 260, h: 74});
    await source('group-edit-button', 'GROUP-DETAIL', {x: 1615, y: 172, w: 191, h: 52});
    await source('existing-install-button', 'CMP-EXISTING-SELECTED', controlBox('CMP-EXISTING-SELECTED', /^\s*Install\s*$/));
    await source('tray-dialog-actions', 'CMP-TRAY-CONFIRM', {x:260,y:930,w:535,h:105});
    const result = {};

    let d = page('USR-01', 16, 1, 2, 'Maak het lokale account eenmaal; stel daarna de juiste toegang in.',
        'Admin / Superadmin', 'Naam + gewenste taak');
    step(d, 'USR-01', 1, 56, 87, 'Zoek de persoon; open alleen zo nodig een nieuw account');
    text(d, 16, 79, ['Open Personen > Toon Alles (1A).', 'Zoek naam, gebruikersnaam en e-mail (1B).',
        'Bestaat het account? Controleer de bestaande toegang.', 'Eerder gebruikt? Laat een Admin ook verwijderde',
        'gebruikers controleren voordat je iets aanmaakt.', 'Account ontbreekt? Klik op + boven de lijst (1B).'], {size: 2.65, lh: 4});
    figure(d, 'USR-01', 'usr-nav', 120, 73, 70, '1A', 'Personen > Toon Alles.', {badgeX: 192});
    figure(d, 'USR-01', 'user-list-tools', 16, 112, 94, '1B', 'Zoeken onder de werkbalk; + maakt een account.', {marks: [{x: 965.65625 * 1.5, y: 187.1875 * 1.5, w: 38.25 * 1.5, h: 34 * 1.5, padding: 4.5, shape: 'ellipse', target: 'Nieuw account via plus'}]});
    end(d);
    step(d, 'USR-01', 2, 148, 125, 'Vul naam, gebruikersnaam en e-mail in');
    text(d, 16, 171, ['Vul Voornaam en Achternaam in (2A); gebruik een echte, gecontroleerde E-mail (2B).',
        'Gebruikersnaam: Voornaam + kleine eerste letters van alle achternaamdelen.',
        'Geen punten of spaties: Jan de Vries wordt Jandv. Bestaat die naam? Vraag een Admin om de uitzondering.',
        'Geen bevestigd e-mailadres? Verzin niets; laat een Admin de lokale werkwijze bepalen.'], {size: 2.65, lh: 3.9});
    figure(d, 'USR-01', 'user-name-fields', 16, 191, 177, '2A', 'Naam en gebruikersnaam in het nieuwe-accountformulier.', {badgeX: 192});
    figure(d, 'USR-01', 'user-email-field', 16, 244, 177, '2B', 'E-mail staat lager in hetzelfde formulier.', {badgeX: 192});
    end(d);
    text(d, 16, 283, 'Vervolg op bladzijde 2: tijdelijk wachtwoord, opslaan, groep en overdracht.', {size: 2.75, weight: 800, fill: C.usr});
    const first = d.render();
    d = page('USR-01', 16, 2, 2, 'Vervolg: sla op, wijs toegang toe en draag het account veilig over.',
        'Admin / Superadmin', 'Dezelfde persoon en invoer');
    step(d, 'USR-01', 3, 56, 79, 'Genereer een tijdelijk wachtwoord en sla op');
    text(d, 16, 78, ['Kies Genereer (3A); beide wachtwoordvelden worden gevuld. Bewaar dit wachtwoord tijdelijk prive.',
        'Controleer Deze gebruiker kan inloggen: zet dit aan als deze persoon mag aanmelden.',
        'Kies onderaan Opslaan (3B). Opslag mislukt? Lees de melding; maak geen tweede account.'], {size: 2.55, lh: 3.8});
    figure(d, 'USR-01', 'usr-credentials', 16, 96, 132, '3A', 'Genereer, bevestiging en de loginstand.');
    figure(d, 'USR-01', 'user-create-save', 160, 111, 28, '3B', 'Opslaan.', {badgeX: 192, badgeY: 112});
    end(d);
    step(d, 'USR-01', 4, 140, 76, 'Laat de juiste groep toewijzen en opslaan');
    text(d, 16, 161, ['Een groep is een rechtenpakket voor een taak. Admin: laat Superadmin de groep toewijzen na deze opslag.',
        'Superadmin: kies rechts op de accountpagina Gebruiker aanpassen (4A).',
        'Open Informatie > Optionele informatie > Groepen (4B).',
        'Kies de taakgroep, hier Refurbisher, en klik onderaan op Opslaan (4C).'], {size: 2.55, lh: 3.7});
    figure(d, 'USR-01', 'usr-detail', 16, 180, 55, '4A', 'Rechts op de accountpagina.');
    figure(d, 'USR-01', 'user-edit-save', 16, 197, 28, '4C', 'Groepskeuze opslaan.', {badgeX: 14, badgeY: 205});
    figure(d, 'USR-01', 'usr-group', 78, 181, 115, '4B', 'Groepen na het uitklappen van Optionele informatie.', {badgeX: 192});
    end(d);
    step(d, 'USR-01', 5, 221, 42, 'Controleer en draag persoonlijk over');
    text(d, 16, 242, ['Controleer op Info naam, gebruikersnaam,', 'groep en login (5A). Extra rechten? Gebruik USR-02.',
        'Geef het tijdelijke wachtwoord persoonlijk door.', 'De gebruiker meldt zich aan en kiest direct',
        'een eigen wachtwoord met de gids hieronder.'], {size: 2.5, lh: 3.4});
    figure(d, 'USR-01', 'usr-saved', 109, 228, 84, '5A', 'Het opgeslagen account.', {badgeX: 192});
    d.guideChip(109, 254, 84, guideReference('AC-02'));
    end(d);
    drawCompletionRow(d, 'Identiteit en toegang kloppen; de gebruiker kiest een eigen wachtwoord.', {x: 12, y: 268, width: 186, height: 11});
    d.guideChip(12, 283, 90, guideReference('USR-02'));
    d.guideChip(108, 283, 90, guideReference('USR-05'));
    result['USR-01'] = [first, d.render()];

    d = page('USR-02', 14, 1, 2, 'Kies de toegang voor de taak van deze gebruiker; sla op en test.', 'Admin / Superadmin', 'Juiste gebruiker + taak');
    step(d, 'USR-02', 1, 56, 61, 'Open en controleer het juiste account');
    text(d, 16, 78, ['Open Personen > Toon Alles; zoek naam of gebruikersnaam.', 'Vergelijk beide en open dezelfde persoon (1A).',
        'Kies rechts Gebruiker aanpassen (1B).', 'Identiteit onzeker? Vraag eerst de aanvrager.'], {size: 2.6, lh: 3.9});
    figure(d, 'USR-02', 'usr-saved', 119, 71, 74, '1A', 'Controleer de persoon.', {badgeX: 192});
    figure(d, 'USR-02', 'usr-detail', 119, 99, 74, '1B', 'Editor op de accountpagina.', {badgeX: 192});
    end(d);
    step(d, 'USR-02', 2, 122, 81, 'Groepen wijzigen: laat dit aan Superadmin');
    text(d, 16, 144, ['Open Informatie > Optionele informatie > Groepen (2A). Een groep is een rechtenpakket voor een taak.',
        'Alle toegewezen groepen tellen mee. Verwijder oude toegang die niet meer nodig is.',
        'Superadmin: Ctrl+klik voegt een groep toe of deselecteert die.',
        'Admin: laat de groepswijziging door Superadmin uitvoeren; wijzig zelf alleen toegestane uitzonderingen.',
        'Sla nog niet op voordat ook de bedoelde directe keuzes zijn gecontroleerd.'], {size: 2.55, lh: 3.7});
    figure(d, 'USR-02', 'usr-group', 16, 169, 100, '2A', 'Groepen na het uitklappen van Optionele informatie.');
    end(d);
    step(d, 'USR-02', 3, 208, 69, 'Kies directe uitzonderingen bewust');
    text(d, 16, 230, ['Open de tab Machtigingen (3A). Overnemen gebruikt het gecombineerde groepsresultaat.',
        'Toestaan geeft het recht direct. Weigeren blokkeert ook Toestaan uit een groep.',
        'Directe keuzes gaan voor groepen. Alleen Superadmin wijzigt Global: Super User.',
        'Onduidelijk? Laat Overnemen staan. Een herbruikbaar taakpakket beheer je met USR-05.'], {size: 2.55, lh: 3.7});
    figure(d, 'USR-02', 'usr-permissions-head', 16, 249, 177, '3A', 'Machtigingen naast Informatie; drie kolommen per recht.', {badgeX: 192});
    end(d);
    d.guideChip(12, 283, 90, guideReference('USR-05')); d.guideChip(108, 283, 90, guideReference('USR-04'));
    const rightsFirst = d.render();
    d = page('USR-02', 14, 2, 2, 'Vervolg: controleer de taakrechten, bewaar de keuze en test als gewone gebruiker.', 'Admin / Superadmin', 'Dezelfde gebruiker');
    step(d, 'USR-02', 4, 56, 110, 'Controleer de taakrechten en sla de gebruiker op');
    text(d, 16, 78, ['Zoek de rechten voor de taak in Machtigingen; klap de sectie zo nodig open.',
        'Assets Create registreert een asset; Edit Quality Grade wijzigt kwaliteit. Alleen lezen is daarvoor onvoldoende.',
        'Testing: Execute Tests, Execute Senior Workflows en Execute Supervisor Workflows regelen de uitvoering.',
        'Start Additional Workflow Runs, Edit Workflow Runs en Override Workflow Guards zijn aparte rechten.',
        'Geef alleen de benodigde toegang. Kies onderaan het gebruikersformulier Opslaan (4B).'], {size: 2.55, lh: 3.8});
    figure(d, 'USR-02', 'usr-assets', 16, 110, 138, '4A', 'Voorbeeld View en Create onder Assets: dezelfde drie kolommen.');
    figure(d, 'USR-02', 'user-rights-save', 164, 126, 28, '4B', 'Gebruiker opslaan.', {badgeX: 192});
    text(d, 16, 159, 'Heropen dezelfde gebruiker en controleer groepen en directe keuzes voordat je een ander formulier opent.', {size: 2.55});
    end(d);
    step(d, 'USR-02', 5, 171, 70, 'Alleen bij een beperking door de assetstatus');
    text(d, 16, 193, ['Ontbreekt het asset of de statuskeuze? Open links Instellingen > Statuslabels; bewerk de juiste status.',
        'Bij Gebruikersuitzonderingen toevoegen of vervangen kies je dezelfde gebruiker (5A).',
        'Status kiezen nodig? Zet Zien en Kiezen / gebruiken op Toestaan. Overnemen volgt de groepen.',
        'De gebruikersregel gaat voor; actierechten en statusbeveiliging blijven gelden. Wijziging? Kies Opslaan (5B).'], {size: 2.55, lh: 3.8});
    figure(d, 'USR-02', 'status-user', 16, 214, 174, '5A', 'Gebruiker, Zien en Kiezen / gebruiken in het statusformulier.');
    figure(d, 'USR-02', 'status-user-save', 164, 186, 28, '5B', 'Status opslaan.', {badgeX: 192});
    end(d);
    step(d, 'USR-02', 6, 246, 31, 'Test de effectieve toegang met de gewone gebruiker');
    text(d, 16, 266, ['Laat opnieuw aanmelden; test een toegestane en een uitgesloten taak zonder echte gegevens te wijzigen.',
        'Admin/Superadmin bewijst die grens niet. Te ruime toegang? Controleer alle andere groepen,',
        'directe Toestaan-keuzes en statusregels; corrigeer en herhaal de gewone-gebruikerstest.'], {size: 2.5, lh: 3.5});
    end(d);
    drawCompletionRow(d, 'Opgeslagen groepen, directe keuzes en statusregels geven de geteste taakgrens.', {x: 12, y: 282, width: 186, height: 8});
    result['USR-02'] = [rightsFirst, d.render()];

    d = page('USR-03', 7, 1, 1, 'Reset een lokaal wachtwoord en laat de gebruiker een eigen kiezen.', 'Admin', 'Gebruiker + priveoverdracht');
    step(d, 'USR-03', 1, 56, 62, 'Zoek de gebruiker en open de editor');
    text(d, 16, 78, ['Open Personen > Toon Alles; zoek naam of gebruikersnaam.', 'Vergelijk beide en open de juiste persoon.',
        'Kies rechts Gebruiker aanpassen (1B).', 'Identiteit onzeker? Wijzig niets; vraag om verduidelijking.'], {size: 2.65, lh: 3.9});
    figure(d, 'USR-03', 'usr-saved', 119, 71, 74, '1A', 'Vergelijk de accountidentiteit.', {badgeX: 192});
    figure(d, 'USR-03', 'usr-detail', 119, 100, 74, '1B', 'Editor rechts op de accountpagina.', {badgeX: 192});
    end(d);
    step(d, 'USR-03', 2, 123, 88, 'Genereer en sla het tijdelijke wachtwoord op');
    text(d, 16, 145, ['Kies Genereer (2A). Het tijdelijke wachtwoord verschijnt onder het eerste veld;',
        'beide wachtwoordvelden worden gevuld. Bewaar het alleen voor persoonlijke overdracht.',
        'Laat de bestaande loginstand en rechten ongewijzigd. Klik onderaan Opslaan (2B).',
        'Lees de succesmelding. Foutmelding? Corrigeer de genoemde invoer en sla opnieuw op.'], {size: 2.65, lh: 3.8});
    figure(d, 'USR-03', 'usr-reset', 16, 165, 140, '2A', 'Genereer vult beide velden; laat de loginstand staan.');
    figure(d, 'USR-03', 'user-reset-save', 164, 179, 28, '2B', 'Opslaan.', {badgeX: 192});
    end(d);
    step(d, 'USR-03', 3, 216, 46, 'Draag persoonlijk over; de gebruiker kiest zelf');
    text(d, 16, 238, ['Geef het tijdelijke wachtwoord persoonlijk aan de gebruiker.',
        'Zet het niet in chat, e-mail, notities, tickets of screenshots.',
        'De gebruiker logt in en kiest direct zelf een eigen wachtwoord.',
        'Vraag nooit dat eigen wachtwoord als bewijs. Regel eerst een veilig overdrachtsmoment.'], {size: 2.65, lh: 3.8});
    end(d);
    drawCompletionRow(d, 'De reset is opgeslagen en prive overgedragen; de gebruiker volgt AC-02.', {x: 12, y: 267, width: 186, height: 11});
    d.guideChip(12, 283, 90, guideReference('AC-02'));
    d.guideChip(108, 283, 90, guideReference('USR-01'));
    result['USR-03'] = [d.render()];

    d = page('USR-04', 8, 1, 1, 'Schakel aanmelden uit voor het gecontroleerde bestaande account.', 'Admin', 'Besluit + juiste gebruiker');
    step(d, 'USR-04', 1, 56, 70, 'Zoek en controleer de gebruiker');
    text(d, 16, 78, ['Open Personen > Toon Alles; zoek naam of gebruikersnaam.', 'Open de persoon en vergelijk beide op Info (1A).',
        'Kies rechts Gebruiker aanpassen (1B).', 'Identiteit onzeker? Wijzig niets; laat die eerst bevestigen.'], {lh: 4});
    figure(d, 'USR-04', 'usr-saved', 119, 71, 74, '1A', 'De identiteit van het bestaande account.', {badgeX: 192});
    figure(d, 'USR-04', 'usr-detail', 119, 104, 74, '1B', 'Editor rechts op de accountpagina.', {badgeX: 192});
    end(d);
    step(d, 'USR-04', 2, 131, 76, 'Zet de login uit en sla op');
    text(d, 16, 153, ['Open Informatie en zoek Deze gebruiker kan inloggen (2A).', 'Vink dit uit; laat wachtwoord, groepen en overige rechten staan.',
        'Klik onderaan Opslaan (2B). Foutmelding? Lees de genoemde oorzaak;', 'controleer de invoer en sla opnieuw op voordat je verdergaat.'], {lh: 3.9});
    figure(d, 'USR-04', 'usr-disable', 16, 174, 140, '2A', 'Het complete loginvakje; hier nog aangevinkt.');
    figure(d, 'USR-04', 'user-disable-save', 164, 183, 28, '2B', 'Opslaan.', {badgeX: 192});
    end(d);
    step(d, 'USR-04', 3, 212, 48, 'Controleer de opgeslagen stand');
    text(d, 16, 234, ['Open opnieuw Info van dezelfde gebruiker.', 'Controleer Login ingeschakeld: Nee (3A).',
        'Het account en de geschiedenis blijven bestaan.', 'Dit bevestigt de blokkade van nieuwe logins.'], {size: 2.55, lh: 3.8});
    figure(d, 'USR-04', 'usr-disabled', 115, 233, 78, '3A', 'De opgeslagen loginstand.', {badgeX: 192});
    end(d);
    drawCompletionRow(d, 'Hetzelfde bestaande account toont na opslaan Login ingeschakeld: Nee.', {x: 12, y: 267, width: 186, height: 11});
    d.guideChip(12, 283, 90, guideReference('USR-02'));
    d.guideChip(108, 283, 90, guideReference('HELP-01'));
    result['USR-04'] = [d.render()];

    d = page('USR-05', 5, 1, 3, 'Hergebruik of wijzig een taakgroep; controleer daarna de toegang van een gebruiker.', 'Superadmin', 'Taak + testgebruiker');
    step(d, 'USR-05', 1, 56, 75, 'Open Groepen en bepaal de taakgrens');
    text(d, 16, 78, ['Bepaal welke taken de groep wel en niet mag uitvoeren.', 'Open rechtsboven het tandwiel (1A) en kies Groepen (1B).'], {size:2.65,lh:3.9});
    figure(d, 'USR-05', 'group-gear', 16, 97, 112, '1A', 'Beheerder Instellingen naast het accountmenu.', {marks:[{x:1873.125,y:103.5,w:24.375,h:19.5,shape:'ellipse',padding:4.5,target:'Beheerder Instellingen'}]});
    figure(d, 'USR-05', 'group-nav', 154, 83, 30, '1B', 'Groepen.', {badgeX:192}); end(d);
    step(d, 'USR-05', 2, 136, 137, 'Vergelijk de groep en kies precies een route');
    text(d, 16, 158, 'Zoek de naam in de lijst (2A); open de kandidaatgroep en vergelijk haar taak met de gewenste toegang.', {size:2.65});
    figure(d, 'USR-05', 'group-list', 16, 167, 177, '2A', 'Groepenlijst met groepsnamen en Nieuwe aanmaken.', {badgeX:192});
    text(d, 16, 242, ['A - Pakket klopt: hergebruik via USR-01 stap 4 of USR-02 stap 2. Opslaan is niet nodig.',
        'B - Taak voor alle leden wijzigen: open de groepsnaam;', '     kies rechts Groep bewerken (2B), daarna pagina 2.',
        'C - Pakket ontbreekt: kies Nieuwe aanmaken (2A), daarna pagina 2.',
        'Een uitzondering voor een lid regel je bij die gebruiker met USR-02.'], {size:2.55,lh:3.9});
    figure(d, 'USR-05', 'group-edit-button', 164, 244, 28, '2B', 'Groep bewerken.', {badgeX:192});
    text(d, 16, 269, 'Alleen bij aanmaken of bewerken: ga verder op pagina 2.', {size:2.65,weight:700,fill:C.usr}); end(d);
    d.guideChip(12,283,90,guideReference('USR-01')); d.guideChip(108,283,90,guideReference('USR-02'));
    const groupsFirst = d.render();
    d = page('USR-05', 5, 2, 3, 'Vervolg van aanmaken of bewerken; stel het pakket voor alle leden in.', 'Superadmin', 'Dezelfde groep + taak');
    step(d, 'USR-05', 3, 56, 57, 'Geef de groep een herkenbare naam');
    text(d, 16, 78, ['Voorbeeld: Catalogus lezen. Noteer taak en grenzen; kies voor een afwijkend pakket een eigen groepsnaam.',
        'Standaardgroepen kunnen vereiste rechten terugkrijgen.'], {size:2.65,lh:3.9});
    figure(d, 'USR-05', 'group-top', 16, 96, 177, '3A', 'Naam van de nieuwe of bewerkte groep.', {badgeX:192}); end(d);
    step(d, 'USR-05', 4, 118, 120, 'Geef alleen rechten die bij deze taak horen');
    text(d, 16, 140, ['Voorbeeld: Models View toestaan; Create en Edit weigeren (4A).',
        'Laat andere rechten uit, ook Admin en Super User. De sectieradio kiest alle rijen; de titel klapt ze open of dicht.',
        'Groepen tellen op: Toestaan uit een groep volstaat. Weigeren in een andere groep blokkeert dat niet.',
        'Een directe gebruikerskeuze gaat voor; direct Weigeren blokkeert ook Toestaan uit een groep.'], {size:2.65,lh:4});
    figure(d, 'USR-05', 'group-models', 16, 177, 177, '4A', 'Links Toestaan, rechts Weigeren; controleer de afzonderlijke rijen.', {badgeX:192}); end(d);
    d.rect(12,244,186,32,C.faint,C.line,.45,2);
    text(d,16,251,'Alleen als de groep met assets of statussen werkt',{size:3,weight:800,fill:C.usr});
    text(d,16,259,['Actierechten en statustoegang zijn aparte grenzen. Controleer Instellingen > Statuslabels: Zien en Kiezen / gebruiken.',
        'Een gebruikersregel gaat voor groepsregels; kiezen vraagt beide toegangen.',
        'Test dit straks met een gewone gebruiker. Admin/Superadmin kunnen de grens omzeilen.'],{size:2.55,lh:3.9});
    d.guideChip(12,283,90,guideReference('USR-02')); d.guideChip(108,283,90,guideReference('USR-01'));
    const groupsSecond = d.render();
    d = page('USR-05', 5, 3, 3, 'Vervolg: sla op, heropen en test toegestane en uitgesloten taken.', 'Superadmin', 'Dezelfde groep + testgebruiker');
    step(d, 'USR-05', 5, 56, 114, 'Sla de groep op en controleer het bewaarde pakket');
    text(d,16,78,['Klik onderaan het formulier op Opslaan (5A). Lees de melding.',
        'Open zo nodig opnieuw via Beheerder Instellingen > Groepen en open dezelfde groepsnaam.'],{size:2.65,lh:4});
    figure(d,'USR-05','group-save',16,96,90,'5A','Opslaan onderaan het groepsformulier.');
    figure(d,'USR-05','group-detail',124,92,69,'5B','De opgeslagen groepsrechten.',{badgeX:192});
    text(d,16,142,['Controleer naam en alle gewijzigde rechten. In dit voorbeeld: View ja, Create en Edit nee.',
        'Een groepsdetail bewijst de instelling, nog niet de effectieve toegang van ieder lid.',
        'Opslag mislukt? Herstel de gemelde fout en sla opnieuw op.'],{size:2.65,lh:4});
    d.guideChip(16,158,90,guideReference('HELP-01')); end(d);
    step(d,'USR-05',6,175,78,'Test met een gecontroleerde gewone gebruiker');
    text(d,16,197,['Wijs de groep via USR-02 stap 2 toe aan een afgesproken testgebruiker.',
        'Controleer alle andere groepen en directe rechten. Voor dit voorbeeld: geen andere toegang,',
        'geen directe uitzonderingen en geen Admin of Super User.',
        'Meld die gebruiker opnieuw aan; open Instellingen > Asset modellen. Bekijken moet lukken.',
        'Aanmaken en bewerken mogen niet toegankelijk zijn. Test zonder echte gegevens te wijzigen.',
        'Onverwachte toegang? Stop uitrol; controleer groepen en uitzonderingen met USR-02.',
        'Je eigen Superadmin-sessie is geen geldige toegangstest.'],{size:2.65,lh:4.1});
    text(d,16,241,'Test geslaagd? Terug naar USR-01 stap 4 of USR-02 stap 2 voor de bedoelde gebruiker.',{size:2.65}); end(d);
    drawCompletionRow(d,'De groep klopt; toegestane en uitgesloten taken zijn als gewone gebruiker getest.',{x:12,y:260,width:186,height:12});
    d.guideChip(12,283,90,guideReference('USR-01')); d.guideChip(108,283,90,guideReference('USR-02'));
    result['USR-05']=[groupsFirst,groupsSecond,d.render()];

    d = page('CMP-01',8,1,1,'Plaats een bestaand fysiek onderdeel en koppel dezelfde registratie aan het juiste asset.','Senior Refurbisher','Open asset + onderdeel',{before:'SC-01'});
    step(d,'CMP-01',1,56,49,'Open Components');
    text(d,16,78,['Tik op de Components-tab (1A).','Kies Add / Install Component.'],{size:2.65,lh:4});
    figure(d,'CMP-01','cmp-entry',117,63,71,'1A','Components op het gecontroleerde asset.',{badgeX:192,marks:[{x:152,y:738.4,w:124,h:105.1,shape:'ellipse',target:'Components-tab'}]}); end(d);
    step(d,'CMP-01',2,110,61,'Kies de bestaande identiteit');
    text(d,16,133,['Zoek de bestaande registratie in je tray of opslag.', 'Vergelijk bron, naam, tag en S/N met het onderdeel.',
        'Vervanging? Ontkoppel het oude onderdeel eerst (CMP-04).', 'Geen passende registratie of geen actie? Vraag Supervisor.',
        'Andere identiteit? Wijzig niets; laat die eerst oplossen.', 'Maak geen tweede record om een melding te omzeilen.'],{size:2.5,lh:3.6});
    figure(d,'CMP-01','cmp-existing',112,118,80,'2A','Selecteer hetzelfde bestaande component.',{badgeX:192}); end(d);
    step(d,'CMP-01',3,176,39,'Plaats fysiek en kies Install eenmaal');
    text(d,16,198,['Plaats dat onderdeel in het apparaat. Klik op Install onder de selectie (3A).',
        'Melding over conditie? Laat een Supervisor die eerst beoordelen.'],{size:2.55,lh:4});
    figure(d,'CMP-01','existing-install-button',169,185,22,'3A','Install.',{badgeX:192}); end(d);
    step(d,'CMP-01',4,220,41,'Controleer koppeling en specificatie');
    text(d,16,242,['Open Components op hetzelfde asset; controleer Tracked,', 'dezelfde tag en hetzelfde S/N (4A). Vergelijk fysiek.',
        'Extra is bovenop de modelverwachting.', 'Controleer ook Specificatie op Info.',
        'Afwijking? Vraag een Supervisor voordat je verdergaat.'],{size:2.5,lh:3.5});
    figure(d,'CMP-01','cmp-result',115,231,78,'4A',null,{badgeX:192}); end(d);
    drawCompletionRow(d,'Hetzelfde onderdeel is geplaatst; identiteit, koppeling en specificatie kloppen.',{x:12,y:266,width:186,height:11});
    d.guideChip(12,283,90,guideReference('CMP-04'));d.guideChip(108,283,90,guideReference('WF-02'));
    result['CMP-01']=[d.render()];

    d = page('CMP-02', 9, 2, 2, 'Vervolg: plaats, registreer eenmaal en controleer het opgeslagen resultaat.', 'Senior Refurbisher', 'Dezelfde invoer; stap 1 en 2', {before: 'SC-01'});
    step(d, 'CMP-02', 3, 56, 94, 'Plaats fysiek en kies Create and Install eenmaal');
    text(d, 16, 78, ['Vergelijk de gekozen definitie of naam, het echte S/N en de conditie met het onderdeel.',
        'Plaats dat onderdeel in het gecontroleerde apparaat. Klik onderaan Create and Install (3A).',
        'Deze knop maakt het componentrecord aan en koppelt het direct aan dit asset; de tag ontstaat automatisch.',
        'Lees de melding. Dubbelmelding? Open en vergelijk de bestaande registratie; maak geen tweede record.',
        'Identiteit onduidelijk of conditie geblokkeerd? Laat een Supervisor dit oplossen voordat je doorgaat.'], {size: 2.65, lh: 3.9});
    figure(d, 'CMP-02', 'cmp-create', 16, 113, 177, '3A', 'Create and Install onderaan het ingevulde formulier.', {badgeX: 192});
    end(d);
    step(d, 'CMP-02', 4, 155, 100, 'Controleer identiteit, koppeling en specificatie');
    text(d, 16, 177, ['Open Components op hetzelfde asset. Controleer Tracked, de nieuwe tag en het ingevoerde S/N (4A).',
        'Vergelijk met het fysieke onderdeel. Controleer daarna op Info de Specificatie van het asset.'], {size: 2.65, lh: 3.9});
    figure(d, 'CMP-02', 'cmp-result', 16, 194, 95, '4A', 'Volledige identificatie van het nieuwe record.');
    text(d, 121, 201, ['Tracked is een eigen fysieke registratie.', 'Extra kan naast Assumed blijven staan.',
        'Is dit de bedoelde toevoeging', 'of de bedoelde vervanging?', 'Een registratie vervangt de oude', 'modelverwachting niet vanzelf.'], {size: 2.55, lh: 4});
    text(d, 16, 244, ['Extra terwijl je een vervanging bedoelde? Laat een Supervisor registratie en afgeleide specificatie controleren.',
        'Maak geen tweede component om dit te herstellen.'], {size: 2.55, lh: 3.8});
    end(d);
    drawCompletionRow(d, 'Het onderdeel staat eenmaal op het juiste asset; identiteit en specificatie kloppen.', {x: 12, y: 262, width: 186, height: 13});
    d.guideChip(12, 283, 90, guideReference('CMP-04')); d.guideChip(108, 283, 90, guideReference('AST-02'));
    result['CMP-02'] = {2: d.render()};

    d = page('CMP-04', 9, 1, 1, 'Verplaats hetzelfde fysieke component van het asset naar je eigen tray.', 'Senior Refurbisher', 'Open asset + eigen tray', {before: 'SC-01'});
    step(d, 'CMP-04', 1, 56, 97, 'Open het component en kies Naar tray');
    text(d, 16, 78, ['Open op het gecontroleerde asset de tab Components (1A). Vergelijk naam, tag en S/N met het onderdeel.',
        'Zoek dezelfde Tracked-rij; Naar tray staat onder die identiteit (1B en 1C).',
        'Een Assumed-rij is een verwachting, geen fysiek componentrecord. Vraag bij twijfel een Supervisor.'], {lh: 3.8});
    figure(d, 'CMP-04', 'cmp-entry', 16, 96, 83, '1A', 'Components op het asset.', {marks: [{x:152,y:738.4,w:124,h:105.1,shape:'ellipse',target:'Components-tab'}]});
    figure(d, 'CMP-04', 'cmp-result', 111, 94, 77, '1B', 'Identiteit van de Tracked-rij.', {badgeX: 192});
    figure(d, 'CMP-04', 'cmp-to-tray', 111, 135, 65, '1C', null, {badgeX: 192, badgeY: 139});
    text(d, 16, 145, ['Geen actie of S/N ontbreekt? Vraag een Supervisor.', 'Maak geen andere registratie om dit te omzeilen.'], {size: 2.4, lh: 3.5});
    end(d);
    step(d, 'CMP-04', 2, 158, 53, 'Controleer de bevestiging');
    text(d, 16, 180, ['Vergelijk naam, tag en het vergrendelde', 'serienummer opnieuw met het onderdeel.',
        'Een vergrendeld serienummer blijft behouden.', 'Verkeerd of onzeker? Kies Annuleren;', 'laat een Supervisor de identiteit oplossen.'], {size: 2.55, lh: 3.8});
    figure(d, 'CMP-04', 'cmp-tray-confirm', 124, 164, 60, '2A', null, {badgeX: 192});
    end(d);
    step(d, 'CMP-04', 3, 216, 29, 'Verplaats fysiek en bevestig eenmaal');
    text(d, 16, 236, ['Haal dat onderdeel uit het asset en leg het in je eigen tray.', 'Kies daarna Naar tray bevestigen (3A).'], {size: 2.55, lh: 3.6});
    figure(d, 'CMP-04', 'tray-dialog-actions', 135, 224, 57, '3A', null, {badgeX: 192});
    end(d);
    step(d, 'CMP-04', 4, 250, 41, 'Controleer het resultaat');
    text(d, 16, 271, ['Open hetzelfde component.', 'Vergelijk de ongewijzigde tag en S/N.', 'In Tray en Asset: N.v.t.? Klaar.'], {size: 2.45, lh: 3.5});
    figure(d, 'CMP-04', 'tray-status-readable', 94, 266, 43, '4A', null, {badgeX: 137, badgeY: 266});
    figure(d, 'CMP-04', 'tray-asset-readable', 145, 266, 43, '4B', null, {badgeX: 188, badgeY: 266});
    d.guideChip(16, 283, 87, guideReference('CMP-01'));
    d.guideChip(109, 283, 84, guideReference('WF-02'));
    end(d);
    result['CMP-04'] = [d.render()];

    return {pages: result, provenance};
}

export function provenanceIndex() { return provenance; }
