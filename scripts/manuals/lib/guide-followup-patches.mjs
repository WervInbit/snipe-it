import assert from 'node:assert/strict';

export function replaceExact(source, before, after, count = 1) {
  assert.equal(source.split(before).length - 1, count, `Expected ${count} source matches: ${before.slice(0, 100)}`);
  return source.split(before).join(after);
}

export function replaceFunction(source, name, transform) {
  const start = source.indexOf(`function ${name}(`);
  assert.ok(start >= 0, `Missing function ${name}`);
  const rest = source.slice(start + 1);
  const next = rest.search(/\n(?:async )?function |\nconst [A-Za-z]/);
  const end = next < 0 ? source.length : start + 1 + next;
  return source.slice(0, start) + transform(source.slice(start, end)) + source.slice(end);
}

function reviseUserPages(pages, guideReference, colors, preparedCropSpecs) {
  const page = (code, side = 1) => pages.find((p) => p.code === code && (p.pageNumber ?? 1) === side);
  const step = (code, number, side = 1) => page(code, side).steps.find((s) => s.number === String(number));

  page('USR-01').context[0].value = 'Superadmin';
  step('USR-01', 3).note = 'Groep ontbreekt? Laat een Superadmin die eerst maken.';
  step('USR-01', 3).guideReference.prefix = 'Groepsbeheer (gids volgt):';
  step('USR-01', 4).visuals[0].caption = 'Voorbeeld van de detailpagina; controleer hier de zojuist gemaakte gebruiker.';

  page('USR-02').context[0].value = 'Admin / Superadmin';
  step('USR-02', 2).title = 'Groepen: alleen Superadmin';
  step('USR-02', 2).body = ['Open eerst Optionele informatie.', 'Alleen Superadmin wijzigt Groepen.', 'Ctrl+klik voegt toe of deselecteert.', 'Admin: laat een groepswijziging uitvoeren', 'door Superadmin; ga daarna naar stap 3.'];
  step('USR-02', 4).body = ['Kies Opslaan. Controleer de groepen.', 'Heropen Machtigingen en controleer directe afwijkingen.'];
  page('USR-02').help[2].body = 'Vraag Superadmin om een groep; gids volgt:';

  step('USR-03', 1).title = 'Controleer de gebruiker';
  step('USR-03', 1).body = ['Open Personen > Toon Alles.', 'Zoek op naam of gebruikersnaam.', 'Vergelijk beide en open het account.', 'Kies Gebruiker aanpassen.'];
  step('USR-03', 2).body = ['Laat de bestaande loginstand ongewijzigd.', 'Kies eenmaal Genereer.', 'Het tijdelijke wachtwoord verschijnt onder het eerste veld.', 'Kies eenmaal Opslaan.', 'Gebruik geen zelfbedacht vast wachtwoord.'];
  page('USR-03').help[0].body = ['Laat login uit; een reset', 'is geen toegangsbesluit.'];
  preparedCropSpecs.editPasswordGeneratedAction = { source: 'editPasswordGenerated', left: 330, top: 315, width: 650, height: 145 };
  step('USR-03', 2).visuals[0].caption = 'Bedieningsdetail: Genereer toont het tijdelijke wachtwoord onder het veld.';
  step('USR-03', 2).visuals[0].marks = [{ shape: 'rect', x: 397, y: 8, w: 90, h: 38, target: 'Genereer' }];

  step('AC-02', 3).body = ['Kies Opslaan en lees de melding.', 'Gelukt: Uw wachtwoord is bijgewerkt!', 'Foutmelding? Corrigeer het genoemde veld', 'en sla opnieuw op. Ga pas verder na succes.', 'Andere aangemelde apparaten worden uitgelogd.'];
  step('AC-02', 3).warning = ['Deel het nieuwe wachtwoord nooit', 'met een beheerder of collega.'];

  step('USR-04', 1).title = 'Controleer het account';
  step('USR-04', 1).body = ['Open Personen > Toon Alles.', 'Zoek op naam of gebruikersnaam.', 'Vergelijk beide; open het juiste account.'];
  step('USR-04', 2).body = ['Controleer Apparaten, Licenties en beheerrelaties.', 'Apparaten/beheer: wijs de afgesproken eigenaar toe.', 'Licenties/accessoires: gebruik hun check-inroute.'];
  preparedCropSpecs.activatedAction = { source: 'editActivated', left: 270, top: 350, width: 720, height: 160 };
  step('USR-04', 3).visuals[0].caption = 'Bedieningsdetail: haal dit vinkje weg; het account blijft bestaan.';
  step('USR-04', 3).visuals[0].marks = [{ shape: 'rect', x: 195, y: 113, w: 280, h: 30, target: 'Deze gebruiker kan inloggen' }];
  step('USR-04', 4).body = ['Open na Opslaan de Info-tab.', 'Controleer naam en gebruikersnaam.', 'Bij Login ingeschakeld moet Nee staan.'];
  step('USR-04', 4).visuals[0].fit = 'contain';

  const back = page('USR-04', 2);
  back.purpose = 'Kies verwijderen (5-6) OF herstellen (6-8); voer alleen de gekozen route uit';
  back.context[1].value = 'Verwijder- of herstelbesluit';
  back.context[2] = { label: 'Vooraf', value: 'Juiste bestaande gebruiker' };
  back.choiceHeader = true;
  step('USR-04', 5, 2).title = 'Verwijderen: controleer eerst';
  step('USR-04', 5, 2).body = ['Alleen toegang stoppen? Gebruik pagina 1.', 'Controleer besluit en alle toewijzingen.', 'Kies Verwijder; controleer daarna met stap 6.'];
  step('USR-04', 5, 2).visuals[0].marks = [{ shape: 'rect', x: 80, y: 70, w: 260, h: 48, target: 'Verwijder' }];
  step('USR-04', 6, 2).title = 'Zoek het verwijderde account';
  step('USR-04', 6, 2).body = ['Open Verwijderde Gebruikers en zoek de identiteit.', 'Verwijderroute: staat de juiste gebruiker hier? Klaar.', 'Herstelroute: ga alleen met herstelbesluit naar stap 7.', 'Maak geen duplicaat met dezelfde identiteit.'];
  step('USR-04', 7, 2).title = 'OF: herstel het account';
  step('USR-04', 7, 2).body = ['Controleer eerst besluit en oude loginstand.', 'Open de verwijderde gebruiker en kies Herstel.'];
  step('USR-04', 7, 2).warning = ['Herstel bewaart de vorige loginstand.', 'Toegang onduidelijk? Herstel nog niet.'];
  step('USR-04', 8, 2).body = ['Controleer gebruikersnaam, groep en directe rechten.', 'Controleer Login ingeschakeld volgens het besluit.'];
  step('USR-04', 8, 2).warning = ['Toegang wijkt af? Laat dit corrigeren', 'voordat de gebruiker verdergaat.'];
  step('USR-04', 8, 2).visuals[0].fit = 'contain';
  step('USR-04', 8, 2).visuals[0].caption = 'Dit voorbeeld heeft login uit; controleer altijd de werkelijke opgeslagen stand.';
  back.complete = 'Verwijderen: de juiste gebruiker staat bij verwijderd. OF herstellen: identiteit en toegang kloppen.';
}

export function patchUser(source) {
  source = replaceExact(source,
    "    doc.text(18, 29, page.purpose, { size: 3, fill: colors.muted });",
    "    if (page.choiceHeader) doc.rect(12, 24, 186, 10, colors.orangeSoft, '#FDBA74', 0.4, 1.8);\n    doc.text(18, 29, page.purpose, { size: page.choiceHeader ? 2.65 : 3, fill: colors.muted });");
  return replaceExact(source, 'async function main() {', `(${reviseUserPages.toString()})(pages, guideReference, colors, preparedCropSpecs);\n\nasync function main() {`);
}

export function patchCatalog(source) {
  source = patchCatalogOther(source);
  for (const id of ['CAT-ATTRIBUTE-CREATE-IDENTITY-DESKTOP', 'CAT-ATTRIBUTE-CONSTRAINTS-NUMERIC-DESKTOP', 'CAT-ATTRIBUTE-OPTIONS-ENUM-DESKTOP', 'CAT-ATTRIBUTE-SAVE-DESKTOP', 'CAT-COMPONENT-DEFINITION-IDENTITY-DESKTOP', 'CAT-COMPONENT-DEFINITION-CHILDREN-DESKTOP', 'CAT-COMPONENT-DEFINITION-CONTRIBUTIONS-DESKTOP', 'CAT-COMPONENT-DEFINITION-SAVE-DESKTOP']) {
    source = replaceExact(source, `${id}-01`, `${id}-02`);
  }
  source = replaceExact(source, "componentDefinitionOverlap: 'CAT-COMPONENT-DEFINITION-OVERLAP-DESKTOP-01'", "componentDefinitionOverlap: 'CAT-COMPONENT-DEFINITION-CONTRIBUTIONS-DESKTOP-02'");
  source = replaceFunction(source, 'cat04V2Page3', (part) => {
    part = replaceExact(part, 'Gebruik bestaande onderdeeldefinities voor onderdelen die normaal binnen deze definitie verwacht worden.', 'Wel vaste onderdelen? Klik eerst Add Expected Subcomponent. Geen vaste onderdelen? Ga naar pagina 4.');
    part = replaceExact(part, 'Elke rij bevat definitie, herkenningsnaam, aantal, Required en optionele Notes.', 'Na Add Expected Subcomponent: kies definitie, naam, aantal, Required en eventueel Notes.');
    return part;
  });
  source = replaceFunction(source, 'cat04Page4', (part) => {
    part = replaceExact(part, 'Kies bestaande attribuutdefinities en vul waarden in die aan hun datatype en regels voldoen.', 'Klik eerst Add Attribute Contribution. Kies daarna een bestaand attribuut en vul een geldige waarde in.');
    part = replaceExact(part,
      "doc.text(23, 237, 'Betekenis ontbreekt? Gebruik eerst CAT-03; maak hier geen bijna-gelijk vrij tekstveld.', { size: 1.85, weight: 900, fill: colors.orange });",
      "doc.text(23, 233, ['Attribuut ontbreekt? Noteer de invoer voordat je dit onopgeslagen formulier verlaat. Gebruik CAT-03.', 'Keer terug naar de bestaande definitie, of vul de nieuwe opnieuw in. Voeg dan de attribuutrij toe.'], { size: 1.7, weight: 800, fill: colors.orange, lh: 2.4 });");
    return part;
  });
  source = replaceFunction(source, 'cat04Page5', (part) => {
    part = replaceExact(part, 'De waarschuwing noemt welk attribuut op beide definitieniveaus staat.', 'Voorbeeld van een numerieke bijdrage; vergelijk ook de bijdragen van het verwachte onderdeel.');
    part = replaceExact(part, '{ x: 50, y: 455, w: 1300, h: 315 }', '{ x: 75, y: 444, w: 1265, h: 223 }');
    part = replaceExact(part,
      "['Bepaal welk niveau het feit bezit. De gekoppelde waarde van het verwachte onderdeel overschrijft', 'de waarde van de bovenliggende definitie. Corrigeer een onbedoeld duplicaat voor hergebruik.']",
      "['Hierarchy overlap warning vergelijkt getalattributen die op beide niveaus aan de assetspecificatie bijdragen.', 'Bepaal welk niveau de waarde levert; het verwachte onderdeel kan de bovenliggende waarde vervangen.', 'Corrigeer onbedoelde overlap. Geen waarschuwing betekent niet dat alle overige feiten kloppen.']");
    part = replaceExact(part, 'De waarschuwing is een correctieroute, geen STOP. Verwijderen van opgeslagen relaties hoort bij CAT-05.', 'Opgeslagen relatie verwijderen? Laat een Admin dit doen voordat je verdergaat; CAT-05 volgt nog.');
    return part;
  });
  source = replaceFunction(source, 'cat04Page6', (part) => {
    part = replaceExact(part, 'De definitie is een bouwsteen; voeg haar nu toe waar de oorspronkelijke taak daarom vroeg.', 'Zoek de opgeslagen definitie. Open Edit en controleer identiteit, waarden, aantallen en vinkjes.');
    part = replaceExact(part, 'Controleer naam, categorie, fabrikant, Instances, Templates en Active.', 'Controleer de rij en open Edit: de lijst alleen bewijst de opgeslagen waarden en aantallen niet.');
    part = replaceExact(part, 'Deactiveer of ruim opgeslagen onderdeel- en bijdrage-relaties gecontroleerd op.', 'Vraag een Admin om lifecycle of opruimen. CAT-05 volgt nog.');
    part = replaceExact(part, 'Maak en plaats later één fysiek componentrecord vanuit deze definitie.', 'Geef de definitie aan de Senior Refurbisher; ga terug naar CMP-02 stap 2A.');
    part = replaceExact(part, 'Maak geen tweede definitie voor één uniek serienummer of Inbit-label.', 'Sluit de controle met Cancel. Maak geen tweede definitie voor een uniek serienummer of Inbit-label.');
    return part;
  });
  return source;
}

function patchCatalogOther(source) {
  source = replaceFunction(source, 'cat00V8Page6', (s) => {
    s = replaceExact(s, "if (route[0] !== 'CAT-01')", "if (['CAT-05', 'CAT-06'].includes(route[0]))");
    s = replaceExact(s, 'Adminroute voor lifecycle en bestaande rijen.', 'Gids volgt; vraag een Admin om lifecycle of opruimen.');
    return replaceExact(s, 'Controleer code en feiten zonder iets te verzinnen.', 'Gids volgt; laat een Supervisor bron en code controleren.');
  });
  source = replaceFunction(source, 'cat00V8Page5', (s) => replaceExact(s, 'Verwachte componenten leveren de fysieke baseline en afgeleide waarden.', 'Ander schermvoorbeeld: verwachte componenten leveren baseline en afgeleide waarden.'));
  source = replaceFunction(source, 'cat01V5Page2', (s) => {
    s = replaceExact(s, 'Wat al bestaat bepaalt waar je verdergaat; niet iedere variant vraagt een nieuw Basismodel.', 'Kies A, B OF C. Volg alleen die route; de andere pagina kan worden overgeslagen.');
    s = replaceExact(s, 'Ga verder bij stap 7.', 'Ga naar pagina 5, stap 7.');
    s = replaceExact(s, "['Controleer fabrikant, product en generatie.', 'Kies daarna Create Model Number en ga naar stap 5.']", "['Controleer fabrikant, product', 'en generatie. Ga naar pagina 4,', 'stap 5: Create Model Number.']");
    s = replaceExact(s, "['Alleen als fabrikant, product en generatie werkelijk ontbreken.', 'Gebruik de + en ga naar stap 4: Basismodel invullen.']", "['Alleen als product en generatie ontbreken.', 'Gebruik de + en ga naar pagina 3,', 'stap 4: Basismodel invullen.']");
    return replaceExact(s, 'maak alleen een ontbrekend Basismodel.', 'volg A naar p. 5, B naar p. 4 OF C naar p. 3.');
  });
  source = replaceFunction(source, 'cat01V5Page4', (s) => {
    s = replaceExact(s, 'De code is de identiteit; het label beschrijft de standaardconfiguratie van die variant.', 'Neem de fabrikantcode over (bij HP: Product ID/P/N); houd serienummer en software-ID apart.');
    s = replaceExact(s, 'Vul code en standaardlabel in', 'Vul code en label in; kies Opslaan');
    s = replaceExact(s, 'Code is exact; Label beschrijft de standaardconfiguratie.', 'Controleer Code en Label. Kies daarna Opslaan.');
    return replaceExact(s, "['Geen serienummer, Product ID, Inbit-tag', 'of zelfgemaakte fabrikantcode.']", "['Geen serienummer, software-Product ID,', 'Inbit-tag of zelfgemaakte fabrikantcode.']");
  });
  source = replaceFunction(source, 'cat02Page2', (s) => {
    s = replaceExact(s, 'Betekenis ontbreekt? Maak eerst de attribuutdefinitie.', 'Betekenis ontbreekt? CAT-03, dan terug naar pagina 3.');
    s = replaceExact(s, 'Onderdeeltype ontbreekt? Maak eerst de componentdefinitie.', 'Type ontbreekt? CAT-04, dan terug naar pagina 4.');
    return replaceExact(s, "doc.text(23, 238, 'Leg RAM niet óók direct vast wanneer het verwachte RAM-component die waarde al levert.', { size: 1.9, weight: 900, fill: colors.orange });", "doc.text(23, 235, ['Noteer onopgeslagen invoer voordat je het formulier verlaat. Maak de ontbrekende definitie met CAT-03/04.', 'Open daarna dezelfde Edit Spec opnieuw en herstel de invoer. Leg een componentwaarde niet ook direct vast.'], { size: 1.65, weight: 800, fill: colors.orange, lh: 2.35 });");
  });
  source = replaceFunction(source, 'cat02Page5', (s) => {
    s = replaceExact(s, 'Een conflict noemt het directe feit en de afwijkende componentwaarde.', 'Apart conflictvoorbeeld: het directe feit wijkt af van de componentwaarde.');
    return replaceExact(s, 'Bestaande opgeslagen rij laten verwijderen: Admin-route.', 'Bestaande rij verwijderen? Vraag een Admin; CAT-05 volgt.');
  });
  source = replaceFunction(source, 'cat03Page3', (s) => {
    for (const [number, letter] of [['1','C'],['2','R'],['3','O'],['4','E'],['5','L']]) s = replaceExact(s, `icon: '${number}'`, `icon: '${letter}'`);
    return s;
  });
  source = replaceFunction(source, 'cat03Page4', (s) => {
    s = replaceExact(s, 'Numerieke grenzen en Enum-opties zijn alternatieven; gebruik alleen wat bij het datatype hoort.', 'Kies A voor Int/Decimal OF B voor Enum. Bij Bool/Text: sla A en B over en ga naar pagina 5.');
    s = replaceExact(s, "'B Enum'", "'OF B Enum'");
    s = replaceExact(s, "['Value blijft stabiel voor het systeem.', 'Label is de leesbare keuze.', 'Sort bepaalt de volgorde.', 'Voorbeeld: ddr4 / DDR4.']", "['Vul onderaan Value, Label en Sort in.', 'Value blijft stabiel; Label is leesbaar.', 'Klik Add to list voor elke optie.', 'Controleer de rij in de lijst erboven.', 'Voorbeeld: ddr4 / DDR4, Sort 10.']");
    s = replaceExact(s, 'Voeg elke afgesproken Value en Label toe in de gewenste volgorde.', 'Add to list voegt de ingevulde optie toe; controleer daarna Value, Label, Sort en Active.');
    return replaceExact(s, '{ x: 300, y: 400, w: 830, h: 500 }', '{ x: 500, y: 600, w: 625, h: 300 }');
  });
  return replaceFunction(source, 'cat03Page5', (s) => replaceExact(s, 'Lifecycle of bestaande opties/relaties verwijderen: gebruik CAT-05.', 'Lifecycle of bestaande opties/relaties verwijderen? Vraag een Admin; CAT-05 volgt nog.'));
}

const names = {
  'AC-01': 'Login', 'SC-01': 'Asset vinden en openen', 'AST-02': 'Refurbishment-route',
  'AST-03': 'Asset registreren en labelen', 'AST-04': 'Werk afronden en overdragen',
  'AST-05': 'Asset beoordelen en vrijgeven', 'WF-01': 'Workflow starten', 'WF-02': 'Workflow uitvoeren en afronden',
  'CMP-01': 'Bestaand component plaatsen', 'CMP-02': 'Nieuw component registreren en plaatsen',
  'CMP-04': 'Component verwijderen naar tray', 'HELP-01': 'Problemen en hulp',
};

function fullChips(source, fn = 'chip') {
  return replaceExact(source, `function ${fn}(family, code, label) {`, `function ${fn}(family, code, label) {\n  label = (${JSON.stringify(names)})[code] ?? label;`);
}

function honestQr(source) {
  const start = source.indexOf('  qrPlaceholder(x, y, size) {');
  const end = source.indexOf('\n  render() {', start);
  assert.ok(start >= 0 && end > start);
  return source.slice(0, start) + `  qrPlaceholder(x, y, size) {
    this.rect(x, y, size, size, colors.white, colors.ink, 0.6, 0.8);
    this.text(x + size / 2, y + size / 2 + 1, 'QR volgt', { size: 2.5, weight: 800, anchor: 'middle' });
  }
` + source.slice(end);
}

function patchAc01(source) {
  source = honestQr(source);
  return replaceExact(source, "['Vraag je supervisor', 'om het te resetten.']", "['Vraag je supervisor;', 'een Admin reset.']");
}

function patchSc01(source) {
  source = honestQr(source);
  return replaceExact(source, 'Wacht tot de asset opent.', 'Asset open? Ga naar stap 4.');
}

function reviseAssetGuides(guides, names) {
  for (const guide of guides) for (const page of guide.pages) {
    page.date = '2026-09-08';
    // References keep their place; complete labels are allowed to wrap in their existing chips.
    for (const ref of [page.prerequisite, ...(page.related ?? []), ...(page.route ?? []).map((r) => r.ref), page.helpBranch?.ref]) {
      if (ref && names[ref.code]) ref.label = names[ref.code];
    }
    if (page.code === 'AST-02') {
      page.route[2].text = 'Juiste onafgeronde run? Open die. OF start eenmaal een nieuwe run.';
      page.helpBranch.body = 'Laat de Supervisor registreren; keer daarna terug naar SC-01.';
    }
    const step = (number) => page.steps?.find((s) => s.number === String(number));
    if (page.code === 'AST-03' && step(1)) {
      step(1).body = ['Begin bij 1A: dashboard > Apparaten.', 'Kies daarna 1B OF 1C; gebruik maar een knop.'];
      step(1).visuals[0].caption = 'Eerst 1A: tik op Apparaten.';
      step(1).visuals[1].caption = 'Kies 1B: Nieuwe aanmaken.';
      step(1).visuals[2].caption = 'OF kies 1C: tik op +.';
      step(3).warning = 'Type ontbreekt? Laat catalogusbeheer CAT-01 uitvoeren; keer terug naar stap 3.';
    }
    if (page.code === 'AST-04') {
      step(1).title = 'Controleer de verplichte workflows';
      step(1).body = ['Controleer naam en asset tag; open Test uitvoeren.', 'Controleer alle verplichte profielen voor dit model: geen open kaart of Mislukt.'];
      step(1).warning = 'Set of bewijs onduidelijk? Vraag supervisor. Verberg geen mislukking.';
      step(1).visuals[1].caption = 'Een afgeronde run; controleer ook alle andere verplichte profielen.';
      step(2).warning = 'Identiteit wijkt af? Wijzig niets. Vergelijk via SC-01 en vraag supervisor.';
    }
    if (page.code === 'AST-05') {
      step(2).body = ['Controleer alle vereiste profielen voor het huidige model en de verkoopstatus.', 'Elke verplichte controle: afgerond, 0 Mislukt en passend bewijs.'];
      step(2).warning = 'Profielset, notitie of waarschuwing onduidelijk? Geef nog niet vrij; laat corrigeren.';
      step(2).visuals[0].caption = 'Een geslaagde run is niet het bewijs voor alle vereiste profielen.';
      step(4).title = 'Kies vrijgeven OF terugsturen';
      step(4).body[1] = 'OF niet akkoord: kies Being Processed; stuur terug met de relevante gids hieronder.';
    }
  }
}

function patchAssets(source) {
  return replaceExact(source, '  for (const guide of selectedGuides) {', `  (${reviseAssetGuides.toString()})(selectedGuides, ${JSON.stringify(names)});\n  for (const guide of selectedGuides) {`);
}

function patchComponents(source) {
  source = fullChips(source);
  source = replaceExact(source, 'CMP-NEW-DEFINITION-MOBILE-03', 'CMP-NEW-DEFINITION-MOBILE-04');
  source = replaceExact(source, 'CMP-NEW-CUSTOM-MOBILE-03', 'CMP-NEW-CUSTOM-MOBILE-04');
  source = replaceExact(source, 'Kies een registratieroute', 'Kies 2A OF 2B');
  source = replaceExact(source, 'Laat de herbruikbare definitie eerst beheren:', 'Vraag een Supervisor de definitie te beheren. Keer daarna terug naar stap 2A:');
  return replaceExact(source, 'Alleen een supervisor kan het wachtwoord laten resetten.', 'Vraag je supervisor om hulp; een Admin voert de wachtwoordreset uit.');
}

function patchWorkflow(source) {
  source = fullChips(source);
  source = replaceExact(source, 'Voer de fysieke of softwarecontrole uit. Kies daarna <b>Geslaagd</b> of <b>Mislukt</b> naar waarheid.', 'Voer de controle uit. Kies <b>Geslaagd</b> of <b>Mislukt</b> naar waarheid. Bij een taakkaart heten die knoppen <b>Gedaan</b> en <b>Niet gedaan</b>.');
  source = replaceExact(source, 'Voorbeeld: fout, meetwaarde en wat je al hebt gecontroleerd.', 'Voorbeeld: fout, meetwaarde en uitgevoerde controle. Wacht op de melding dat de notitie is opgeslagen.');
  source = replaceExact(source, 'Herhaal stap 2 tot en met 5 voor alle vereiste kaarten. Controleer daarna de aantallen en open de run opnieuw bij twijfel.', 'Herhaal stap 2-5 voor alle vereiste kaarten. Resultaten worden automatisch opgeslagen. Open daarna dezelfde asset > Test uitvoeren en controleer de runregel.');
  return replaceExact(source, 'STOP bij een ontbrekend, onduidelijk of tegenstrijdig resultaat.', 'Resultaat ontbreekt of klopt niet? Open de run opnieuw en corrigeer of vraag hulp.');
}

export const guideFollowups = [
  { code: 'AC-01', old: 8, version: 9, file: 'AC-01-login-v9-draft.pdf', script: 'generate-ac01-snipe-proof.mjs', patch: patchAc01, html: 'AC-01-login-snipe-v8.html', pages: 1, env: { SNIPEIT_AC01_VERSION: '8' } },
  { code: 'SC-01', old: 10, version: 11, file: 'SC-01-asset-vinden-en-openen-v11-draft.pdf', script: 'generate-sc01-snipe-proof.mjs', patch: patchSc01, html: 'SC-01-find-open-asset-snipe-v10.html', pages: 1 },
  ...[
    ['AST-02',6,7,'refurbishment-route',1], ['AST-03',14,15,'register-label',2],
    ['AST-04',5,6,'complete-handoff',1], ['AST-05',5,6,'review-release',1],
  ].map(([code, old, version, stem, pages]) => ({ code, old, version, pages,
    file: `${code}-${code === 'AST-03' ? 'asset-registreren-en-labelen' : stem}-v${version}-draft.pdf`,
    script: 'generate-revised-guide-set.mjs', patch: patchAssets,
    html: `${code}-${stem}-v${old}-draft.html`, env: { SNIPEIT_AST02_VERSION: '6' },
  })),
  { code: 'WF-02', old: 11, version: 12, file: 'WF-02-complete-workflow-v12-draft.pdf', script: 'generate-workflow-guide-review.mjs', patch: patchWorkflow, html: 'WF-02-complete-workflow-v11-draft.html', pages: 2, env: { SNIPEIT_WF02_VERSION: '11' } },
  { code: 'CMP-01', old: 5, version: 6, file: 'CMP-01-install-existing-v6-draft.pdf', script: 'generate-component-guide-review.mjs', patch: (s) => fullChips(s, 'guideChip'), html: 'CMP-01-install-existing-v5-draft.html', pages: 1, env: { SNIPEIT_CMP01_VERSION: '5' } },
  { code: 'CMP-02', old: 4, version: 5, file: 'CMP-02-register-install-v5-draft.pdf', script: 'generate-component-followup-guides.mjs', patch: patchComponents, html: 'CMP-02-register-install-v4-draft.html', pages: 1 },
  { code: 'HELP-01', old: 6, version: 7, file: 'HELP-01-problems-v7-draft.pdf', script: 'generate-component-followup-guides.mjs', patch: patchComponents, html: 'HELP-01-problems-v6-draft.html', pages: 1 },
  ...[
    ['CAT-00',9,10,'catalogus-begrijpen',6], ['CAT-01',5,6,'model-en-modelnummer-aanmaken',5],
    ['CAT-02',1,2,'modelspecificatie-opbouwen',6], ['CAT-03',1,2,'attributen-beheren',5],
  ].map(([code, old, version, stem, pages]) => ({ code, old, version, pages,
    file: `${code}-${stem}-v${version}-draft.pdf`, script: 'generate-catalog-guide-review.mjs',
    patch: patchCatalog, html: `${code.toLowerCase()}-v${old}.html`,
  })),
  { code: 'USR-04', old: 3, version: 5, file: 'USR-04-gebruiker-uitschakelen-of-herstellen-v5-draft.pdf', script: 'generate-user-account-guide-review.mjs', patch: patchUser, html: 'usr-04-gebruiker-uitschakelen-v3-draft.html', pages: 2 },
  { code: 'CAT-04', old: 2, version: 4, file: 'CAT-04-componentdefinities-beheren-v4-draft.pdf', script: 'generate-catalog-guide-review.mjs', patch: patchCatalog, html: 'cat-04-v2.html', pages: 6 },
  { code: 'USR-01', old: 11, version: 12, file: 'usr-01-gebruiker-toevoegen-v12-draft.pdf', script: 'generate-user-account-guide-review.mjs', patch: patchUser, html: 'usr-01-gebruiker-toevoegen-v11-draft.html', pages: 1 },
  { code: 'USR-02', old: 9, version: 10, file: 'usr-02-rol-en-rechten-wijzigen-v10-draft.pdf', script: 'generate-user-account-guide-review.mjs', patch: patchUser, html: 'usr-02-rol-en-rechten-wijzigen-v9-draft.html', pages: 1 },
  { code: 'USR-03', old: 3, version: 4, file: 'usr-03-wachtwoord-resetten-v4-draft.pdf', script: 'generate-user-account-guide-review.mjs', patch: patchUser, html: 'usr-03-wachtwoord-resetten-v3-draft.html', pages: 1 },
  { code: 'AC-02', old: 3, version: 4, file: 'ac-02-eigen-wachtwoord-wijzigen-v4-draft.pdf', script: 'generate-user-account-guide-review.mjs', patch: patchUser, html: 'ac-02-eigen-wachtwoord-wijzigen-v3-draft.html', pages: 1 },
];
