import {page, text, caption, figure, retained, source, saveBox, end} from './final-guide-scenes-2026-11-04.mjs';
import {GUIDE_TOKENS, guideReference, drawCompletionRow} from './guide-system.mjs';

const C = GUIDE_TOKENS.colors;
function section(d, number, title) {
    d.raw(`<g data-component="task-step" data-number="${number}" data-title="${title}">`);
    d.stepBadge(21, 64, number, C.cat);
    text(d, 32, 65.5, title, {size: 3.8, weight: 900, fill: C.cat});
}
function heading(d, y, title) { text(d, 16, y, title, {size: 3.25, weight: 800, fill: C.cat}); }
function links(d, codes, y = 283) {
    const w = (186 - (codes.length - 1) * 5) / codes.length;
    codes.forEach((code, i) => d.guideChip(12 + i * (w + 5), y, w, guideReference(code)));
}
function next(d, line, codes) {
    text(d, 16, 273, line, {size: 2.65, weight: 700, fill: C.cat});
    links(d, codes);
}
function chapter(code, version, n, total, purpose, need, options = {}) {
    return page(code, version, n, total, purpose, options.role ?? 'Supervisor', need,
        {before: options.before ?? 'CAT-00', context: options.context});
}
const body = (d, y, lines, options = {}) => text(d, 16, y, lines, {size: 2.75, lh: 4.1, ...options});

export async function finalCatalogScenes() {
    for (const key of ['cat-nav', 'model-create', 'model-entry', 'model-info', 'number-create', 'spec-top', 'spec-available', 'spec-detail', 'spec-component',
        'spec-children', 'spec-add', 'spec-save', 'model', 'numbers', 'attrs', 'attr-identity', 'attr-behavior', 'attr-aggregation',
        'attr-distinct', 'attr-constraints', 'attr-options', 'attr-save', 'attr-result', 'definitions', 'def-identity',
        'def-children', 'def-values', 'def-save', 'def-save-action', 'maint-options', 'maint-enum-inuse', 'maint-aggregation',
        'maint-numbers', 'maint-number-edit', 'maint-number-default', 'maint-attr-use', 'maint-attrs', 'maint-definitions', 'maint-inuse',
        'cmp-assumed', 'cmp-result']) await retained(key);
    await source('catalog-base-save', 'CAT-MODEL-FILLED', saveBox('CAT-MODEL-FILLED'));
    await source('catalog-base-fields', 'CAT-MODEL-FILLED', {x:610,y:300,w:1040,h:240});
    await source('catalog-code-save', 'CAT-NUMBER-FILLED', saveBox('CAT-NUMBER-FILLED'));
    await source('catalog-attribute-save', 'ATTR-NUMERIC-CREATE', saveBox('ATTR-NUMERIC-CREATE'));
    await source('catalog-enum-save', 'MAINT-ENUM', saveBox('MAINT-ENUM'));
    await source('catalog-aggregation-save', 'MAINT-INUSE', saveBox('MAINT-INUSE'));
    await source('catalog-inuse-aggregation', 'MAINT-INUSE', {x:610,y:1503,w:1040,h:210});
    await source('catalog-number-actions', 'MAINT-NUMBERS', {x: 1570, y: 310, w: 330, h: 135});
    await source('physical-context', 'AST-LABEL-PLACEMENT-PHOTO-01.jpg', {x: 0, y: 0, w: 1536, h: 1062});
    await source('manufacturer-label', 'AST-LABEL-PLACEMENT-PHOTO-01.jpg', {x: 790, y: 420, w: 260, h: 150});
    await source('hp-product-card', 'CAT-SOURCE-HP-PROBOOK-450-G8-02.jpg', {x: 0, y: 84, w: 239, h: 405});
    const result = {};

    let d = chapter('CAT-00', 14, 3, 6, 'Definities worden eenmaal gemaakt en op meerdere plekken hergebruikt.', 'Vraag: welke bouwsteen?');
    heading(d, 64, 'Deel 3: Herbruikbare bouwstenen');
    d.raw('<g data-component="relationship-diagram" data-title="Definities en attribuutrelaties">');
    d.rect(12, 79, 89, 71, C.catSoft, C.cat, .45, 2); d.rect(109, 79, 89, 71, C.cmpSoft, C.cmp, .45, 2);
    text(d, 17, 89, 'Attribuutdefinitie', {size: 3.3, weight: 800, fill: C.cat});
    text(d, 17, 100, ['Een herbruikbare betekenis met invoerregels.', 'Voorbeeld: Werkgeheugen, geheel getal in GB.',
        'Label: Werkgeheugen', 'Datatype: Int', 'Unit: GB', 'Dit is geen waarde voor een specifiek apparaat.'], {size: 2.65, lh: 6.8});
    text(d, 114, 89, 'Componentdefinitie', {size: 3.3, weight: 800, fill: C.cmp});
    text(d, 114, 100, ['Een herbruikbaar fysiek onderdeeltype.', 'Identiteit, waarden en eventueel vaste kinderen.',
        'Voorbeeld: RAM 8 GB DDR4', 'Werkgeheugen = 8 GB', 'Geheugentype = DDR4', 'Dit is nog geen uniek geplaatst onderdeel.'], {size: 2.65, lh: 6.8});
    heading(d, 164, 'Een componentdefinitie kan meerdere attributen gebruiken');
    d.rect(16, 183, 76, 37, C.cmpSoft, C.cmp, .5, 2);
    text(d, 22, 197, 'RAM 8 GB DDR4', {size: 3.3, weight: 800, fill: C.cmp});
    text(d, 22, 208, 'Componentdefinitie', {size: 2.65});
    for (const [y, label, type] of [[178, 'Werkgeheugen', 'Int + GB'], [197, 'Geheugentype', 'Enum'], [216, 'Geheugensnelheid', 'Int + MHz']]) {
        d.rect(119, y, 74, 16, C.catSoft, C.cat, .45, 2);
        text(d, 125, y + 6, label, {size: 2.75, weight: 800, fill: C.cat});
        text(d, 125, y + 12, type, {size: 2.5});
        d.line(92, 201.5, 111, y + 8, C.cmp, .6); d.line(111, y + 8, 119, y + 8, C.cmp, .6);
    }
    end(d);
    body(d, 246, ['Dezelfde attribuutdefinitie kan terugkomen in andere componentdefinities, modelnummers en workflows.',
        'De lijsten en aanmaakschermen horen bij de taakgidsen hieronder.']);
    next(d, 'Volgende: vergelijk verwachting en werkelijkheid op een asset.', ['CAT-03', 'CAT-04']);
    result['CAT-00'] = {3: d.render()};

    d = chapter('CAT-00', 14, 4, 6, 'Begrijp een verwachte rij en een werkelijk geregistreerd onderdeel.', 'Catalogus + een asset');
    heading(d, 64, 'Deel 4: Verwachting tegenover werkelijkheid');
    body(d, 75, ['Dit zijn verschillende betekenissen; het zijn geen verplichte opeenvolgende stappen.']);
    text(d, 16, 91, 'Assumed: een verwachting', {size: 3.2, weight: 800, fill: C.cat});
    text(d, 16, 103, ['De modelbaseline verwacht dit onderdeel.', 'Deze rij heeft nog geen eigen fysieke', 'registratie. Controleer wat er echt', 'in het apparaat aanwezig is.'], {size: 2.65, lh: 4.2});
    figure(d, 'CAT-00', 'cmp-assumed', 87, 89, 106, '4A', 'Model baseline en Assumed op het asset.', {badgeX: 192});
    text(d, 16, 158, 'Tracked: een fysiek record', {size: 3.2, weight: 800, fill: C.cat});
    text(d, 16, 170, ['Dit onderdeel heeft een eigen tag en S/N.', 'Vergelijk ze met het werkelijke onderdeel.', 'Een Tracked record kan tegelijk', 'een Extra zijn.'], {size: 2.65, lh: 4.2});
    figure(d, 'CAT-00', 'cmp-result', 87, 156, 106, '4B', 'Eigen fysieke identiteit; hier ook Extra.', {badgeX: 192});
    heading(d, 218, 'Een registratie vervangt een verwachting niet vanzelf');
    body(d, 229, ['Een extra Tracked record kan naast de Assumed baseline blijven staan. Controleer het bedoelde effect.',
        'Removed betekent dat de verwachting van die plaats is verwijderd; het is geen gewone statusstap.',
        'Een assetstatus wijzigen maakt van een Assumed-rij geen fysiek geregistreerd component.']);
    next(d, 'Volgende: directe feiten en onderdelen vormen samen de specificatie.', ['CAT-02', 'CMP-02']); result['CAT-00'][4] = d.render();

    d = chapter('CAT-00', 14, 5, 6, 'Begrijp hoe directe feiten en onderdelen samen de specificatie vormen.', 'Modelspecificatie');
    heading(d, 64, 'Deel 5: Hoe een modelspecificatie ontstaat');
    text(d, 16, 85, 'Direct attribuut: feit van de hele variant', {size: 3.2, weight: 800, fill: C.cat});
    text(d, 16, 97, ['Bijvoorbeeld Introductiejaar = 2021', 'of de schermdiagonaal.', 'Geen afzonderlijk verplaatsbaar onderdeel.',
        'De waarde gebruikt een herbruikbare', 'attribuutdefinitie.'], {size: 2.65, lh: 4.2});
    figure(d, 'CAT-00', 'spec-detail', 105, 83, 88, '5A', 'Definitie en directe waarde.', {badgeX: 192});
    heading(d, 147, 'Verwacht component: onderdeel in de fabrieksbaseline');
    body(d, 158, ['Voorbeeld: 1 x RAM 8 GB DDR4. De hoeveelheid en bijdragen bepalen de afgeleide waarde.']);
    figure(d, 'CAT-00', 'spec-component', 16, 170, 177, '5B', 'Het verwachte onderdeeltype, aantal en de afgeleide attributen.', {badgeX: 192});
    text(d, 16, 238, 'Sum: capaciteit of aantal', {size: 3, weight: 800, fill: C.cat});
    text(d, 112, 238, 'Keep distinct values: eigenschap', {size: 3, weight: 800, fill: C.cat});
    text(d, 16, 249, ['2 x 8 GB geeft 16 GB.', 'Het aantal onderdelen telt mee.'], {size: 2.7, lh: 4});
    text(d, 112, 249, ['2 x 3200 MHz blijft 3200 MHz.', '3200 en 2666 MHz blijven beide zichtbaar.'], {size: 2.7, lh: 4});
    body(d, 264, ['Voer hetzelfde feit niet ook als concurrerende directe waarde in. Herhaling maakt een eigenschap niet groter.'], {size: 2.55});
    links(d, ['CAT-02', 'CAT-03']); result['CAT-00'][5] = d.render();

    d = chapter('CAT-01', 10, 3, 5, 'Route C: maak alleen het ontbrekende Basismodel.', 'Product + generatie + bron');
    section(d, 4, 'Vul de herbruikbare productidentiteit in');
    body(d, 81, ['Je komt hier vanaf pagina 2, route C. De + boven Asset modellen opent dit formulier.',
        'Vul Asset model naam, Categorienaam en Fabrikant in. De exacte fabrikantcode volgt op pagina 4.']);
    figure(d, 'CAT-01', 'catalog-base-fields', 16, 98, 177, '4A', 'Het formulier voor een Basismodel; deze drie velden bepalen de productidentiteit.', {badgeX: 192});
    heading(d, 158, 'Wat hoort in ieder veld?');
    body(d, 169, ['Asset model naam: product + generatie, bijvoorbeeld HP ProBook 450 G8.',
        'Categorienaam: het juiste apparaattype, bijvoorbeeld Laptops. Fabrikant: de werkelijke producent, hier HP.',
        'Gebruik geen asset tag, serienummer, exacte SKU-suffix of zelfgemaakte configuratiecode in de naam.',
        'Een algemene afbeelding is optioneel; voeg haar alleen toe als zij herkenning helpt.'],{size:3.1,lh:4.6});
    body(d, 198, ['Juiste categorie of fabrikant ontbreekt? Kies geen bijna-gelijke vervanger.',
        'Noteer je invoer, vraag een Admin om de ontbrekende keuze en hervat daarna deze stap.'],{size:3.1,lh:4.6});
    heading(d, 222, 'Sla het Basismodel op');
    body(d, 233, ['Controleer de drie velden. Kies boven of onder in het formulier Opslaan (4B).',
        'Lees de melding; ga verder vanuit het opgeslagen Basismodel.'], {size: 3.1, lh: 4.6});
    figure(d, 'CAT-01', 'catalog-base-save', 158, 232, 28, '4B', 'Opslaan.', {badgeX: 192});
    end(d);
    next(d, 'Volgende: pagina 4, stap 5. Voeg daar de exacte fabrikantcode toe.', ['CAT-00', 'CAT-06']);
    result['CAT-01'] = {3: d.render()};

    d = chapter('CAT-01', 10, 4, 5, 'Route B of C: voeg de exacte fabrikantvariant toe.', 'Volledige code + bron');
    section(d, 5, 'Open Create Model Number');
    body(d, 81, ['Open het opgeslagen of hergebruikte Basismodel. Kies Create Model Number boven de exacte rijen (5A).']);
    figure(d, 'CAT-01', 'model-entry', 16, 90, 177, '5A', 'De knop staat boven de modelnummerrijen van dit Basismodel.', {badgeX: 192});
    end(d);
    d.raw('<g data-component="task-step" data-number="6">');
    d.stepBadge(21, 129, 6, C.cat);
    text(d, 32, 130.5, 'Vul de volledige code en het herkenningslabel in', {size: 3.8, weight: 900, fill: C.cat});
    body(d, 146, ['Code: neem de exacte fabrikant-/SKU-code inclusief suffix over, bijvoorbeeld 2E9F8EA#ABH.',
        'Bij HP is dit Product ID/P/N op het fysieke label. Houd serienummer en software-Product ID apart.',
        'Label: de standaardconfiguratie van die code, met product, processor, RAM en opslag.']);
    figure(d, 'CAT-01', 'number-create', 16, 163, 116, '6A', 'Code en Label van de exacte fabrikantvariant.');
    text(d, 140, 172, ['Aa behoudt bewust kleine letters;', 'gebruik dit alleen als de code dat vraagt.',
        'Geen Inbit-tag of verzonnen code.', 'Later RAM of opslag vervangen?', 'De fabrikantcode blijft dezelfde.', 'Leg die wijziging vast op het asset.'], {size: 2.5, lh: 3.8});
    figure(d, 'CAT-01', 'catalog-code-save', 155, 207, 28, '6B', 'Opslaan.', {badgeX: 192});
    body(d, 235, ['Controleer Code en Label en klik op Opslaan (6B). Lees de melding.',
        'De eerste exacte code wordt automatisch de standaardrij. Een andere standaard kies je met CAT-05.']);
    end(d);
    next(d, 'Volgende: pagina 5, stap 7. Controleer de opgeslagen identiteit.', ['CAT-05', 'CAT-06']);
    result['CAT-01'][4] = d.render();

    d = chapter('CAT-01', 10, 5, 5, 'Controleer de opgeslagen identiteit voordat je specificaties of assets toevoegt.', 'Productlabel + bron');
    section(d,7,'Controleer Basismodel, exacte code en informatiepaneel');
    body(d,81,['Basismodel: juiste product en generatie. Modelnummer: volledige code en suffix; actief en zonder duplicaat.',
        'Het label noemt de standaard processor, RAM en opslag. Vergelijk die met je gecontroleerde bron.']);
    figure(d,'CAT-01','model',16,99,177,'7A','Basismodel, code, standaardconfiguratie en status van de opgeslagen rij.',{badgeX:192});
    figure(d,'CAT-01','model-info',16,158,84,'7B','Categorie en fabrikant op het Basismodel.');
    text(d,109,166,['Het informatiepaneel staat rechts op het Basismodel.', 'Controleer categorienaam en fabrikant.',
        'De eerste code kan vanzelf de standaardrij zijn;', 'dat is geen extra verplicht invoerveld.'],{size:2.65,lh:4.1}); end(d);
    d.raw('<g data-component="task-step" data-number="8">'); d.stepBadge(21,213,8,C.cat);
    text(d,32,214.5,'Kies het volgende object',{size:3.8,weight:900,fill:C.cat});
    body(d,228,['Specificatie ontbreekt? Gebruik CAT-02. Een fysiek apparaat registreren? Gebruik AST-03.']);
    d.guideChip(16,240,87,guideReference('CAT-02')); d.guideChip(109,240,84,guideReference('AST-03')); end(d);
    body(d,258,['Kopieer model kopieert geen modelnummers, variantbeelden, specificatiewaarden of verwachte componenten.',
        'Controleer ieder onderliggend record handmatig.'],{size:2.6,lh:4});
    drawCompletionRow(d,'Een juist Basismodel bevat de geverifieerde exacte code zonder duplicaat.',{x:12,y:273,width:186,height:9});
    links(d,['CAT-00','CAT-06'],286); result['CAT-01'][5]=d.render();

    const spec = [];
    d = chapter('CAT-02', 5, 1, 6, 'Leg voor een exacte fabrikantvariant de juiste baseline vast.', 'Exacte code + bron', {before: 'CAT-01'});
    section(d, 1, 'Open Edit Spec van de juiste modelnummerrij');
    body(d, 81, ['Open het Basismodel uit CAT-01 of ga naar Instellingen > Asset modellen.',
        'Open het juiste product en vergelijk de volledige code, suffix en het herkenningslabel.',
        'Kies Edit Spec in de rij van precies die fabrikantvariant (1A).']);
    figure(d, 'CAT-02', 'model', 16, 103, 177, '1A', 'Modelnummers bij het Basismodel: Edit Spec hoort bij de gekozen rij.', {badgeX: 192});
    heading(d, 162, 'Herken dezelfde variant in het geopende formulier');
    figure(d, 'CAT-02', 'spec-top', 16, 173, 177, '1B', 'In Edit Spec: kruimelpad en variant moeten bij je bron horen.', {badgeX: 192});
    body(d, 232, ['Verkeerde variant? Keer terug en kies de juiste rij. Pas geen andere specificatie aan als omweg.']);
    end(d); next(d, 'Volgende: kies per feit een directe waarde of een verwacht onderdeel.', ['CAT-00', 'CAT-01']); spec.push(d.render());

    d = chapter('CAT-02', 5, 2, 6, 'Kies per feit de bron die bij de betekenis hoort.', 'Dezelfde variant + bron');
    section(d, 2, 'Direct feit OF verwacht component');
    body(d, 81, ['Gebruik voor hetzelfde feit een passende bron: direct op de variant of via een verwacht onderdeel.']);
    heading(d, 95, 'A - Direct attribuut: feit van de hele variant');
    body(d, 105, ['Geen afzonderlijk vervangbaar of telbaar onderdeel. Voorbeeld: Introductiejaar = 2021.',
        'Dit wordt een standaardwaarde voor assets met deze modelcode. Ga naar pagina 3.']);
    figure(d, 'CAT-02', 'spec-available', 16, 120, 177, '2A', 'Directe waarden: Available Attributes, Selected Attributes en Attribute Details.', {badgeX: 192});
    heading(d, 184, 'B - Verwacht component: onderdeel van de fabrieksbaseline');
    body(d, 194, ['Een afzonderlijk vervangbaar, telbaar of inspecteerbaar onderdeel. Voorbeeld: 1 x RAM 8 GB DDR4.',
        'Expected Components beschrijft wat normaal wordt verwacht. Ga naar pagina 4.']);
    body(d, 215, ['Betekenis ontbreekt? Gebruik CAT-03; een onderdeeltype ontbreekt? Gebruik CAT-04.',
        'Noteer onopgeslagen invoer voordat je weggaat. Open daarna dezelfde Edit Spec en herstel die invoer.',
        'Leg een door componenten geleverd feit niet daarnaast als concurrerende directe waarde vast.']);
    end(d); next(d, 'Volg de passende route; controleer beide soorten samen op pagina 5.', ['CAT-03', 'CAT-04']); spec.push(d.render());

    d = chapter('CAT-02', 5, 3, 6, 'Route A: voeg een directe waarde toe of controleer een bestaande.', 'Dezelfde Edit Spec');
    section(d, 3, 'Zoek de definitie, selecteer de rij en vul de waarde in');
    body(d, 81, ['Staat het feit al bij Selected Attributes? Selecteer die rij.',
        'Anders: zoek bij Available Attributes en klik op de + van de juiste definitie (3A).']);
    figure(d, 'CAT-02', 'spec-available', 16, 98, 177, '3A', 'Zoeken en + links; de toegevoegde rij verschijnt in Selected Attributes.', {badgeX: 192});
    heading(d, 164, 'Vul rechts de bevestigde waarde in');
    body(d, 174, ['Selecteer de bestaande of nieuwe rij. Attribute Details toont de invoer en eenheid (3B).',
        'Gebruik het getoonde datatype; voer alleen een waarde in die de bron voor deze variant onderbouwt.']);
    figure(d, 'CAT-02', 'spec-detail', 16, 190, 108, '3B', 'Het detailpaneel van de geselecteerde definitie.');
    text(d, 132, 196, ['Up en Down veranderen alleen', 'de weergavevolgorde.', 'Een nieuwe onopgeslagen rij', 'haal je met de rode X weg.',
        'Een opgeslagen relatie verwijderen?', 'Gebruik Admin-onderhoud: CAT-05.'], {size: 2.55, lh: 4});
    end(d); next(d, 'Ook een verwacht onderdeel nodig? Pagina 4. Anders: controle op pagina 5.', ['CAT-03', 'CAT-06']); spec.push(d.render());

    d = chapter('CAT-02', 5, 4, 6, 'Route B: voeg een verwacht onderdeeltype met het juiste aantal toe.', 'Dezelfde Edit Spec');
    section(d, 4, 'Voeg een componentdefinitie aan de baseline toe');
    body(d, 81, ['Scroll in Edit Spec naar Expected Components. Gebruik een bestaande componentdefinitie.',
        'Klik onder de bestaande rijen op Add Expected Component (4A).']);
    figure(d, 'CAT-02', 'spec-add', 16, 98, 177, '4A', 'Add Expected Component maakt een nieuwe verwachte rij.', {badgeX: 192});
    heading(d, 164, 'Kies het type en het aantal');
    body(d, 174, ['Kies de Catalog Definition en vul het verwachte aantal bij Stks in (4B).',
        'Voeg een rij toe voor ieder afzonderlijk verwacht onderdeeltype.']);
    figure(d, 'CAT-02', 'spec-component', 16, 190, 177, '4B', 'Definitie, aantal en Derived attributes van een verwacht component.', {badgeX: 192});
    body(d, 251, ['Deze modelrij is standaard verplicht; hier staat geen aparte verplicht/optioneel-keuze.',
        'Dit maakt geen fysiek componentrecord, tag of serienummer aan.']);
    end(d); next(d, 'Volgende: controleer bronnen, bijdragen en aantallen samen.', ['CAT-04', 'CMP-02']); spec.push(d.render());

    d = chapter('CAT-02', 5, 5, 6, 'Controleer de bronnen en berekening voor je de baseline opslaat.', 'Dezelfde variant + bron');
    section(d, 5, 'Controleer bijdragen, kinderen en dubbele invoer');
    body(d, 81, ['Controleer per Expected Component de definitie, Stks, Derived attributes en eventuele kinderen.',
        'De bijdragevlaggen van de definitie bepalen welke eigenschappen zichtbaar worden.']);
    figure(d, 'CAT-02', 'spec-children', 16, 98, 177, '5A', 'Ander onderdeelvoorbeeld: een moederbord met afgeleide waarden en verwachte kinderen.', {badgeX: 192});
    heading(d, 195, 'Rekenregel en bron hebben elk een eigen betekenis');
    body(d, 205, ['Sum telt capaciteit of aantallen maal de hoeveelheid. Keep distinct values behoudt unieke eigenschappen.',
        'Een kindbijdrage gaat voor hetzelfde attribuut op de bovenliggende definitie.',
        'Een feit dat componenten al leveren, staat niet ook als handmatige keuze in de lijst.',
        'Verwijder geen correcte componentbron om die keuze weer zichtbaar te maken.']);
    body(d, 231, ['Corrigeer nieuwe dubbele of verkeerde invoer voor Opslaan. Bij overlap gaat de componentwaarde voor.',
        'Een opgeslagen rij verwijderen vraagt Admin en CAT-05. Een verkeerde definitie corrigeer je via CAT-04.']);
    end(d); next(d, 'Volgende: sla de gecontroleerde baseline op en heropen haar.', ['CAT-03', 'CAT-05']); spec.push(d.render());

    d = chapter('CAT-02', 5, 6, 6, 'Sla de juiste variant op en controleer het bewaarde resultaat.', 'Dezelfde Edit Spec');
    section(d, 6, 'Controleer, sla op en heropen dezelfde variant');
    body(d, 81, ['Controleer eerst de modelcode, directe waarden, componentdefinities en aantallen.',
        'Scroll naar de onderkant van Edit Spec en klik op Opslaan (6A). Lees de melding.']);
    figure(d, 'CAT-02', 'spec-save', 16, 99, 177, '6A', 'Opslaan staat onderaan de modelspecificatie.', {badgeX: 192});
    heading(d, 132, 'Heropen dezelfde Edit Spec');
    body(d, 143, ['Ga terug naar het Basismodel en kies Edit Spec in dezelfde modelnummerrij.',
        'Controleer de code, directe waarden, verwachte componenten, aantallen en afgeleide uitkomsten.']);
    figure(d, 'CAT-02', 'spec-top', 16, 161, 177, '6B', 'Herken dezelfde variant; controleer daarna alle opgeslagen secties verder naar beneden.', {badgeX: 192});
    body(d, 219, ['Foutmelding? Corrigeer de genoemde invoer en sla opnieuw op. Ga pas verder als de controle klopt.',
        'Registreer het fysieke apparaat daarna met AST-03. De baseline zelf maakt geen asset of componentrecord.']);
    end(d);
    drawCompletionRow(d, 'De juiste variant heeft bewaarde directe waarden en een niet-dubbele componentbaseline.', {x: 12, y: 252, width: 186, height: 13});
    links(d, ['CAT-00', 'AST-03']); spec.push(d.render());
    result['CAT-02'] = spec;

    const attr = [];
    d = chapter('CAT-03', 5, 1, 6, 'Maak een herbruikbare betekenis met passende invoerregels.', 'Betekenis + bron');
    section(d, 1, 'Zoek en hergebruik dezelfde betekenis');
    body(d, 81, ['Open Instellingen > Attributes. Zoek op het leesbare Label en de systeemnaam (Key).',
        'Vergelijk datatype, Category, Required, Asset Overrides en het aantal Options.']);
    figure(d, 'CAT-03', 'cat-nav', 16, 98, 58, '1A', 'Instellingen in het zijmenu.', {marks:[{x:9,y:405,w:334.5,h:45,padding:3,target:'Attributes in Instellingen'}]});
    text(d, 86, 109, ['Klap links Instellingen open (1A).', 'Attributes opent de attribuutlijst (1B).', 'Zoek op Label en Key; vergelijk de betekenis.',
        'Controleer datatype, Category, Required,', 'Asset Overrides en het aantal Options.'], {size:2.75,lh:4.4});
    figure(d, 'CAT-03', 'attrs', 16, 177, 177, '1B', 'Vergelijk de bestaande definitie; New Attribute staat boven de lijst.', {badgeX:192});
    body(d, 239, ['Dezelfde betekenis? Hergebruik de definitie; maak geen tweede key voor hetzelfde feit.',
        'Bestaande instelling wijzigen? Controleer het gebruik; gedeeld onderhoud staat in CAT-05.',
        'Betekenis ontbreekt echt? Kies New Attribute en ga naar pagina 2. Zo voorkom je dubbele velden.'], {size:2.65,lh:4});
    end(d); next(d, 'Volgende: naam, systeemnaam, datatype en eenheid.', ['CAT-00', 'CAT-06']); attr.push(d.render());

    d = chapter('CAT-03', 5, 2, 6, 'Leg voor de eerste opslag vast wat dit ene feit betekent.', 'Nieuwe attribuutdefinitie');
    section(d, 2, 'Kies naam, datatype en zo nodig eenheid');
    body(d, 81, ['Label is de leesbare naam. De systeemnaam (Key) ontstaat automatisch.',
        'Laat Manual key override normaal uit. De key houdt steeds dezelfde betekenis en eenheid.']);
    figure(d, 'CAT-03', 'attr-identity', 16, 98, 177, '2A', 'Identiteit en datatype in het nieuwe-attribuutformulier.', {badgeX: 192});
    heading(d, 204, 'Kies het datatype dat de echte invoer beschrijft');
    body(d, 215, ['Bool: ja/nee, bijvoorbeeld touchscreen aanwezig.',
        'Int: een geheel getal, bijvoorbeeld aantal geheugenslots. Decimal: een getal met decimalen, zoals schermdiagonaal.',
        'Enum: een vaste keuzelijst, bijvoorbeeld DDR3 / DDR4 / DDR5.',
        'Text: vrije tekst, alleen als geen passend gestructureerd type bestaat.']);
    body(d, 246, ['Let op: het datatype kan na aanmaken niet meer worden gewijzigd.',
        'Eenheid: kies de vaste eenheid die bij dit feit hoort; gebruik geen verschillende eenheden onder dezelfde key.']);
    end(d); next(d, 'Int/Decimal: pagina 3. Ander type: ga naar Gedrag op pagina 4.', ['CAT-00', 'CAT-04']); attr.push(d.render());

    d = chapter('CAT-03', 5, 3, 6, 'Alleen Int of Decimal: bepaal hoe componentgetallen samenkomen.', 'Betekenis + eenheid');
    section(d, 3, 'Kies de numerieke berekening');
    body(d, 81, ['Scroll naar Numeric Component Aggregation. Dit bepaalt de berekening van componentwaarden.',
        'Component Spec Display bepaalt de weergave; dat is een andere keuze. Ander datatype? Ga naar pagina 4.']);
    figure(d, 'CAT-03', 'attr-aggregation', 16, 98, 177, '3A', 'Kies de rekenregel voordat je deze numerieke definitie voor het eerst opslaat.', {badgeX: 192});
    heading(d, 154, 'Sum values by quantity - capaciteit of aantallen');
    body(d, 165, ['2 x 8 GB geeft samen 16 GB. Het aantal onderdelen telt mee.',
        'Ook Aantal geheugenslots telt aantallen op wanneer componenten die leveren.']);
    heading(d, 190, 'Keep distinct values - een gedeelde eigenschap');
    body(d, 201, ['2 x 3200 MHz blijft 3200 MHz. Bij 3200 en 2666 MHz blijven beide waarden zichtbaar.',
        'Aantallen vermenigvuldigen deze eigenschap niet.']);
    figure(d, 'CAT-03', 'attr-distinct', 16, 217, 177, '3B', 'Ander voorbeeld: Geheugensnelheid gebruikt Keep distinct values.', {badgeX: 192});
    body(d, 261, ['Al in gebruik? Een andere regel verandert bestaande uitkomsten. Gebruik Admin-onderhoud: CAT-05.'], {size: 2.65});
    end(d); next(d, 'Volgende: categorie, verplichting, overrides en weergave.', ['CAT-05', 'CAT-04']); attr.push(d.render());

    d = chapter('CAT-03', 5, 4, 6, 'Bepaal bereik en gedrag vanuit de operationele betekenis.', 'Dezelfde nieuwe definitie');
    section(d, 4, 'Stel categorie en gedrag bewust in');
    body(d, 81, ['Zet een keuze alleen aan wanneer de betekenis van dit feit dat werkelijk vraagt.']);
    figure(d, 'CAT-03', 'attr-behavior', 16, 94, 177, '4A', 'Categorie, verplichting, overrides, Enum-afwijkingen en componentweergave.', {badgeX: 192});
    body(d, 191, ['Category Scope: kies de categorieen die dit feit gebruiken. Leeg betekent alle assetcategorieen.',
        'Required for category: aan betekent dat de modelspecificatie niet zonder deze waarde kan worden opgeslagen.',
        'Allow asset overrides: alleen als een fysiek asset terecht van de modelbaseline kan afwijken.',
        'Custom values (Enum): normaal uit; alleen aan voor een afgesproken uitzondering.',
        'Component Spec Display: Value labels toont waarden; Component labels toont onderdeelregels.']);
    body(d, 231, ['Een override herstelt geen verkeerd modelnummer of verkeerde componentdefinitie.']);
    end(d); next(d, 'Volgende: alleen passende numerieke grenzen of Enum-opties.', ['CAT-02', 'CAT-04']); attr.push(d.render());

    d = chapter('CAT-03', 5, 5, 6, 'Kies de invoerroute die bij het datatype hoort.', 'Dezelfde definitie + type');
    section(d, 5, 'A numeriek OF B Enum; Bool/Text slaat dit over');
    heading(d, 82, 'A - Int of Decimal');
    body(d, 92, ['Minimum en Maximum blokkeren onmogelijke waarden. Step bepaalt toegestane sprongen.',
        'Voorbeeld voor een heel aantal: Minimum 0, Maximum 8, Step 1.']);
    figure(d, 'CAT-03', 'attr-constraints', 16, 104, 177, '5A', 'Numerieke grenzen in hetzelfde attribuutformulier.', {badgeX: 192});
    heading(d, 157, 'OF B - Enum');
    body(d, 167, ['Vul onderaan Value, Label en Sort in. Value is de stabiele sleutel; Label is de leesbare naam.',
        'Klik voor iedere optie op Add to list. Controleer de rij met Value, Label, Sort en Active erboven.']);
    figure(d, 'CAT-03', 'attr-options', 16, 180, 177, '5B', 'Voorbeeld: ddr4 / DDR4, Sort 10. Add to list voegt de ingevoerde optie toe.', {badgeX: 192});
    body(d, 264, ['Regex blijft leeg, tenzij de systeembeheerder een afgesproken patroon aanlevert.'], {size: 2.6});
    end(d); next(d, 'Volgende: controleer de volledige definitie, sla op en heropen.', ['CAT-04', 'CAT-06']); attr.push(d.render());

    d = chapter('CAT-03', 5, 6, 6, 'Controleer de bewaarde definitie en keer terug naar je oorspronkelijke taak.', 'Dezelfde definitie');
    section(d, 6, 'Controleer voor en na Opslaan');
    body(d, 81, ['Controleer Label, key, datatype, eenheid, categorie, gedrag, berekening en invoerregels.',
        'Scroll naar de onderkant van het formulier en kies Opslaan (6A). Lees de melding.']);
    figure(d, 'CAT-03', 'catalog-attribute-save', 16, 101, 34, '6A', 'Opslaan onderaan.');
    heading(d, 129, 'Zoek de definitie terug en open haar opnieuw');
    body(d, 140, ['Ga terug naar Instellingen > Attributes en zoek hetzelfde Label of dezelfde key.',
        'Open Edit en controleer alle bewaarde instellingen. De lijst alleen bewijst niet iedere regel.']);
    figure(d, 'CAT-03', 'attr-result', 16, 159, 177, '6B', 'Opgeslagen numeriek voorbeeld: controleer ook de overige instellingen in het formulier.', {badgeX: 192});
    body(d, 225, ['Een latere wijziging werkt door in modelnummers, componenten, assetoverrides en workflows die dit feit gebruiken.',
        'Lifecycle of opgeslagen opties/relaties verwijderen? Vraag een Admin en gebruik CAT-05.']);
    end(d);
    drawCompletionRow(d, 'De definitie is uniek, vindbaar en heeft de bedoelde betekenis en geldige invoer.', {x: 12, y: 252, width: 186, height: 13});
    links(d, ['CAT-02', 'CAT-04']); attr.push(d.render());
    result['CAT-03'] = attr;

    const def = [];
    d = chapter('CAT-04', 7, 1, 6, 'Maak een herbruikbaar onderdeeltype met bijdragen en verwachte onderdelen.', 'Onderdeelbron + definities');
    section(d, 1, 'Zoek hetzelfde onderdeeltype voordat je iets maakt');
    body(d, 81, ['Open Instellingen > Component Definitions. Zoek naam, part code, modelnummer, categorie of fabrikant.',
        'Vergelijk de gevonden definitie. Kies Create New alleen als geen bestaande definitie past.']);
    figure(d, 'CAT-04', 'cat-nav', 16, 99, 58, '1A', 'Instellingen in het zijmenu.', {marks:[{x:9,y:630,w:334.5,h:45,padding:3,target:'Component Definitions in Instellingen'}]});
    text(d,86,109,['Klap links Instellingen open (1A).', 'Component Definitions opent de lijst (1B).',
        'Instances: fysieke records die het type gebruiken.', 'Templates: modelnummerbaselines die het verwachten.',
        'Het gaat om een herbruikbaar type,', 'geen uniek getagd exemplaar.'], {size:2.65,lh:4.4});
    figure(d, 'CAT-04', 'definitions', 16, 178, 177, '1B', 'Vergelijk de gevonden definitie; kies alleen zo nodig Create New.', {badgeX:192});
    body(d, 227, ['Gaat het alleen om een feit zoals een capaciteit of typeaanduiding? Gebruik een attribuutdefinitie.',
        'De componentdefinitie combineert die feiten voor een herkenbaar fysiek onderdeeltype.']);
    end(d); next(d, 'Volgende: herbruikbare identiteit. Unieke tag en S/N horen later bij CMP-02.', ['CAT-00', 'CAT-03']); def.push(d.render());

    d = chapter('CAT-04', 7, 2, 6, 'Leg alleen geverifieerde kenmerken van het onderdeeltype vast.', 'Nieuwe componentdefinitie');
    section(d, 2, 'Vul de herbruikbare identiteit in');
    body(d, 81, ['Vul alleen bevestigde kenmerken in. Laat een optioneel veld leeg in plaats van te gokken.']);
    figure(d, 'CAT-04', 'def-identity', 16, 95, 177, '2A', 'Het bovenste deel van de componentdefinitie.', {badgeX: 192});
    body(d, 186, ['Name: herkenbaar type fysiek onderdeel. Part Code: fabrikant- of leverancierscode.',
        'Model Number: modelcode van het onderdeel. Category: categorie van dit onderdeel.',
        'Manufacturer: werkelijke fabrikant, of leeg als die niet is bevestigd.',
        'Summary / Label: extra herkenning zonder onnodige herhaling.']);
    body(d, 225, ['Active toont de huidige status. Lifecycle wijzigen is Admin-onderhoud via CAT-05.',
        'Gebruik geen Inbit-tag of uniek serienummer als definitienaam.']);
    end(d); next(d, 'Volgende: alleen vaste verwachte onderdelen. Geen kinderen? Ga naar pagina 4.', ['CAT-03', 'CAT-05']); def.push(d.render());

    d = chapter('CAT-04', 7, 3, 6, 'Alleen als dit type normaal vaste onderdelen bevat.', 'Definitie + vaste structuur');
    section(d, 3, 'Voeg vaste verwachte onderdelen toe');
    body(d, 81, ['Klik op Add Expected Subcomponent. Kies per vaste kindrij de bestaande Component Definition.',
        'RAM zonder vaste kinderen? Sla deze pagina over en ga naar pagina 4.']);
    figure(d, 'CAT-04', 'def-children', 16, 99, 177, '3A', 'Ander voorbeeld: een moederbord met verwachte poorten.', {badgeX: 192});
    body(d, 179, ['Component Definition: het bestaande herbruikbare type van het verwachte onderdeel.',
        'Expected Name: leeg gebruikt de definitienaam; vul alleen duidelijkere herkenning in.',
        'Quantity: het normale aantal. Laat Required in deze werkwijze aangevinkt.',
        'Notes: optionele herkenning of plaatsing; geen vervanging voor een attribuut.']);
    body(d, 215, ['Voeg alleen onderdelen toe die normaal altijd aanwezig horen te zijn.',
        'Een optioneel of per apparaat wisselend onderdeel hoort niet in deze verwachte structuur.']);
    end(d); next(d, 'Volgende: attribuutbijdragen en hun weergavekeuzes.', ['CAT-03', 'CAT-05']); def.push(d.render());

    d = chapter('CAT-04', 7, 4, 6, 'Voeg waarden uit bestaande attribuutdefinities toe.', 'Dezelfde definitie + feiten');
    section(d, 4, 'Laat het onderdeel specificaties bijdragen');
    body(d, 81, ['Klik op Add Attribute Contribution. Kies een bestaand attribuut en vul een geldige waarde in.',
        'Kies daarna bewust welke bijdrage in de assetspecificatie en de componentregel verschijnt.']);
    figure(d, 'CAT-04', 'def-values', 16, 99, 177, '4A', 'Bijdragen met waarde en de twee afzonderlijke displaykeuzes.', {badgeX: 192});
    heading(d, 214, 'Twee vlaggen, twee bedoelingen');
    body(d, 225, ['Show as asset spec: de componentwaarde telt mee in de zichtbare assetspecificatie.',
        'Use in component label: de waarde helpt de leesbare componentregel samenstellen.',
        'Een componentdefinitie kan meerdere attributen gebruiken; dezelfde attribuutdefinitie kan in meerdere typen terugkomen.']);
    body(d, 250, ['Attribuut ontbreekt? Noteer de onopgeslagen invoer en gebruik CAT-03.',
        'Open daarna de bestaande definitie of herstel de nieuwe invoer; voeg de juiste attribuutrij toe.']);
    end(d); next(d, 'Volgende: controleer overlap, aantallen en uitkomst.', ['CAT-03', 'CAT-05']); def.push(d.render());

    d = chapter('CAT-04', 7, 5, 6, 'Controleer bron en uitkomst voordat je de definitie opslaat.', 'Bijdragen + kinderen');
    section(d, 5, 'Controleer overlap, aantallen en uitkomst');
    body(d, 81, ['Een attribuut op een verwacht kind gaat voor hetzelfde attribuut op de bovenliggende definitie.',
        'Controleer beide niveaus; corrigeer onbedoelde overlap. Lees een overlapmelding voor je opslaat.']);
    figure(d, 'CAT-04', 'def-save', 16, 100, 177, '5A', 'Controleer alle bijdragen en de afzonderlijke displayvlaggen.', {badgeX: 192});
    body(d, 175, ['Controleer ook de aantallen van de verwachte kinderen. De attribuutregel bepaalt de numerieke uitkomst:',
        'Sum telt op met de hoeveelheid. Keep distinct values behoudt unieke eigenschappen. CAT-03 beheert die regel.']);
    heading(d, 201, 'Sla de gecontroleerde definitie op');
    body(d, 212, ['Kies Create Definition voor een nieuwe definitie, of Save Changes bij een bestaande.',
        'De getoonde knop (5B) staat onderaan. Lees de melding en controleer het bewaarde resultaat op pagina 6.']);
    figure(d, 'CAT-04', 'def-save-action', 16, 231, 72, '5B', 'In deze bestaande definitie: Save Changes.');
    end(d); next(d, 'Volgende: zoek de definitie, heropen haar en keer terug naar je taak.', ['CAT-03', 'CAT-05']); def.push(d.render());

    d = chapter('CAT-04', 7, 6, 6, 'Controleer de opgeslagen definitie en kies je vervolgtaak.', 'Dezelfde definitie');
    section(d, 6, 'Zoek, heropen en controleer');
    body(d, 81, ['Ga naar Instellingen > Component Definitions. Zoek de definitie en kies Edit.',
        'Controleer identiteit, waarden, aantallen en vlaggen; alleen de lijst bewijst niet alle instellingen.']);
    figure(d, 'CAT-04', 'definitions', 16, 99, 177, '6A', 'Zoek de opgeslagen rij en open Edit voor de volledige controle.', {badgeX: 192});
    heading(d, 171, 'Kies de passende vervolgroute');
    body(d, 182, ['Voor een modelnummerbaseline: gebruik CAT-02, pagina 4, en voeg de definitie als verwacht component toe.',
        'Voor een nieuw fysiek onderdeel: geef de definitie aan de Senior Refurbisher; hervat CMP-02 stap 2A.',
        'Voor lifecycle of opruimen: laat een Admin CAT-05 gebruiken.']);
    body(d, 211, ['Sluit een ongewijzigde controle met Cancel. Maak geen tweede definitie voor een uniek serienummer of Inbit-label.']);
    end(d);
    drawCompletionRow(d, 'De definitie is vindbaar; identiteit, bijdragen en verwachte onderdelen zijn gecontroleerd.', {x: 12, y: 245, width: 186, height: 13});
    links(d, ['CAT-02', 'CMP-02']); def.push(d.render());
    result['CAT-04'] = def;

    const maintenance = {};
    const route = (n, letter, purpose, need) => chapter('CAT-05', 5, n, 9, `Route ${letter}: ${purpose}`, need,
        {role: 'Admin', context: {label: 'Keuze', value: `Alleen route ${letter}; inhoud op p. 1`}});
    const local = (d, n, y, title) => {
        d.raw(`<g data-component="task-step" data-number="${n}">`); d.stepBadge(21, y, n, C.cat);
        text(d, 32, y + 1.5, title, {size: 3.7, weight: 900, fill: C.cat});
    };
    const done = (d, line) => drawCompletionRow(d, line, {x: 12, y: 267, width: 186, height: 11});
    d = route(2, 'A', 'haal een exacte code uit nieuwe keuzes of zet haar terug.', 'Exacte code + statusimpact');
    section(d, 1, 'Zoek de exacte code en controleer huidig gebruik');
    body(d, 81, ['Open links Instellingen > Model Numbers. Vergelijk Basismodel, volledige Code, Status en Assets.',
        'Kies Edit bij de juiste rij (1A). Is die Primary? Kies eerst een andere standaard via pagina 3.']);
    figure(d, 'CAT-05', 'maint-numbers', 16, 99, 177, '1A', 'Status en Assets horen bij deze exacte code, niet bij de hele productfamilie.', {badgeX: 192});
    end(d);
    local(d, 2, 159, 'Wijzig alleen Status en sla de keuze op');
    figure(d, 'CAT-05', 'maint-number-edit', 16, 174, 96, '2A', 'Status en Opslaan in Edit Model Number.');
    text(d, 121, 181, ['Uit nieuwe keuzes halen: Deprecated.', 'Weer beschikbaar maken: Active.', 'Kies Opslaan.',
        'Controleer de lijst en open Edit opnieuw.', 'Staat de bedoelde status er nog?'], {size: 2.65, lh: 4});
    body(d, 225, ['Wijzig hierbij geen Code, Label of afbeeldingen. Het screenshot toont een bestaande Primary als bedieningsvoorbeeld;',
        'het is geen opdracht die rij te deprecaten. Ga daarna terug naar de modelnummerkeuze in CAT-01 of je oorspronkelijke taak.'], {size: 2.6});
    end(d); done(d, 'De bedoelde status is opgeslagen en na heropenen bevestigd.'); links(d, ['CAT-01', 'CAT-02']); maintenance[2] = d.render();

    d = route(3, 'B', 'kies een andere standaard OF verwijder een aantoonbaar ongebruikte code.', 'Exacte code + bekende impact');
    body(d, 67, ['Open Instellingen > Model Numbers. Volg alleen A of B hieronder; het zijn alternatieve taken.']);
    heading(d, 82, 'A - Een andere actieve code als standaard kiezen');
    figure(d, 'CAT-05', 'maint-number-default', 16, 94, 120, 'A1', 'De default-checkbox staat onder Status.');
    body(d, 133, ['Open Edit bij de gewenste code van hetzelfde Basismodel. Nog Deprecated? Zet eerst Active, sla op en heropen.',
        'Vink Make this the default selection for new assets aan en kies Opslaan.',
        'Controleer: de gekozen rij toont Primary; de vorige standaard niet meer.'], {size: 2.65});
    heading(d, 163, 'OF B - Alleen een aantoonbaar ongebruikte rij verwijderen');
    body(d, 174, ['Controleer de exacte Code, Assets = 0 en dat de rij niet Primary is.',
        'Controleer ook of specificatie, afbeeldingen of historische variant nog nodig zijn.',
        'Kies Verwijder op uitsluitend die rij (B1). De bevestiging noemt geen code: annuleer bij twijfel.',
        'Bevestig alleen als de gekozen rij zeker klopt. Zoek de code opnieuw: alleen die rij moet weg zijn.',
        'Geen verwijderactie? Vraag om hulp. Primary of gekoppelde assets blokkeren verwijderen; verander geen koppelingen als omweg.'], {size: 2.6, lh: 3.9});
    figure(d, 'CAT-05', 'catalog-number-actions', 16, 204, 72, 'B1', 'Acties rechts op dezelfde rij.');
    text(d, 99, 210, ['Terug: CAT-01, selectie van de gewenste code.', 'Kopieer model neemt alleen basisvelden en optioneel', 'de modelafbeelding over. Geen modelnummers,',
        'variantbeelden, specificaties of verwachte componenten.', 'Controleer die onderliggende records via CAT-01.'], {size: 2.55, lh: 3.9});
    done(d, 'De nieuwe Primary klopt, of uitsluitend de bedoelde ongebruikte rij is weg.'); links(d, ['CAT-01', 'CAT-02']); maintenance[3] = d.render();

    d = route(5, 'D', 'verberg een volledig attribuut of maak het opnieuw beschikbaar.', 'Attribuut + bekend gebruik');
    section(d, 1, 'Open Edit en controleer het bestaande gebruik');
    body(d, 81, ['Open links Instellingen > Attributes. Zoek de definitie en kies Edit.',
        'Lees de gebruikswaarschuwing. Controleer relevante modelspecificaties, overrides, componenten en testverwachtingen.']);
    figure(d, 'CAT-05', 'maint-enum-inuse', 16, 100, 177, '1A', 'Geheugentype / ram_type: de telling gaat over de definitie, niet over een afzonderlijke Enum-optie.', {badgeX: 192});
    end(d);
    local(d, 2, 163, 'Ga terug naar de lijst en kies Hide OF Unhide');
    body(d, 180, ['Hide werkt meteen: controleer Hidden en de actie Unhide. Herstellen: kies Unhide; controleer Active en Hide.',
        'Er is geen extra Opslaan nodig. Bestaande gegevens blijven staan; controleer ook de bedoelde selecties.']);
    figure(d, 'CAT-05', 'maint-attrs', 16, 196, 177, '2A', 'De rijactie wisselt tussen Hide en Unhide.', {badgeX: 192});
    body(d, 232, ['Er is geen knop om de attribuutdefinitie te verwijderen. Vraag hulp bij noodzakelijke cleanup.',
        'Ga terug naar CAT-03 of de selectie waarmee je begon.']);
    end(d); done(d, 'Rijstatus en beschikbaarheid in de bedoelde keuze kloppen.'); links(d, ['CAT-03', 'CAT-02']); maintenance[5] = d.render();

    d = route(6, 'E', 'onderhoud een bestaande Enum-keuze, niet het hele veld.', 'Exacte optie + bekend gebruik');
    section(d, 1, 'Selecteer de opgeslagen optie op Value en Label');
    body(d, 81, ['Open Instellingen > Attributes. Zoek de definitie, kies Edit en scroll naar Options.',
        'Value is de opgeslagen sleutel, bijvoorbeeld ddr4. Label is de leesbare naam, DDR4.',
        'Controleer gebruik van die exacte waarde; de totale definitietelling bewijst dat niet.']);
    figure(d, 'CAT-05', 'maint-options', 16, 103, 177, '1A', 'Bestaande opties: Active en Remove zijn selectievakjes op iedere eigen rij.', {badgeX: 192});
    end(d);
    local(d, 2, 159, 'Deactiveer OF verwijder, en sla het formulier op');
    body(d, 177, ['Tijdelijk niet aanbieden: vink Active uit. De optie blijft als bestaande optie herkenbaar.',
        'Alleen aantoonbaar ongebruikt en niet meer nodig: vink Remove aan bij die ene rij.',
        'Kies onderaan Opslaan (2A). Alleen Remove aanvinken bewaart nog niets.',
        'Heropen dezelfde definitie: Active staat uit, of uitsluitend de bedoelde optie is weg.',
        'Controleer selecties en bestaande records; vertrouw niet op een automatische gebruiksblokkade.'], {size: 2.65, lh: 4});
    figure(d, 'CAT-05', 'catalog-enum-save', 16, 210, 28, '2A', 'Opslaan onderaan.');
    text(d, 65, 215, ['Gebruik of betekenis onzeker? Laat Value, Active en Remove staan.', 'Vraag de eigenaar voordat je wijzigt.',
        'Terug: CAT-03, controle van de opgeslagen definitie.'], {size: 2.65, lh: 4});
    end(d); done(d, 'De bedoelde optie en alle geraakte selecties kloppen na opslaan.'); links(d, ['CAT-03', 'CAT-06']); maintenance[6] = d.render();

    d = route(7, 'F', 'zet een herbruikbaar onderdeeltype uit of herstel het.', 'Definitie + bekend gebruik');
    section(d, 1, 'Controleer identiteit, Instances en Templates');
    body(d, 81, ['Open links Instellingen > Component Definitions en zoek de bedoelde definitie.',
        'Instances telt fysieke componentrecords; Templates telt modelnummer-verwachtingen, niet alle kindrelaties.',
        'Open Edit en controleer ook bijdragen en verwachte onderdelen voordat je gedeeld gedrag wijzigt.']);
    figure(d, 'CAT-05', 'maint-definitions', 16, 105, 177, '1A', 'De lijst toont gebruik, huidige status en de beschikbare statusactie.', {badgeX: 192});
    end(d);
    local(d, 2, 160, 'Kies Deactivate OF Activate in dezelfde lijst');
    body(d, 178, ['Niet meer voor nieuwe keuzes gebruiken: kies Deactivate bij de bedoelde definitie.',
        'Controleer Inactive en de actie Activate. Opnieuw beschikbaar? Kies Activate en controleer de omgekeerde status.',
        'De lijstactie werkt meteen; er is daarna geen Save Changes nodig.',
        'Controleer de bedoelde selectie en bestaand gebruik. Ontbreekt de actie? Vraag hulp; gebruik geen omweg.']);
    heading(d, 218, 'Wat verandert hier?');
    body(d, 229, ['Deactiveren verwijdert geen Tracked-component. Deze browserroute heeft geen definitie-deleteknop.',
        'Ga terug naar CAT-04 of de oorspronkelijke componentkeuze.']);
    end(d); done(d, 'De bedoelde status en selectie zijn gecontroleerd.'); links(d, ['CAT-04', 'CMP-02']); maintenance[7] = d.render();

    d = route(9, 'H', 'wijzig een numerieke regel alleen met bekende impact.', 'Attribuut + oude regel + impact');
    section(d, 1, 'Bepaal betekenis en alle geraakte bronnen');
    body(d, 81, ['Open Instellingen > Attributes; zoek het attribuut en kies Edit.',
        'Noteer de oude regel en model- en assetvoorbeelden. Controleer bronnen, kinderen, aantallen, vlaggen en gebruik.',
        'Gevolgen onbekend? Stel de wijziging uit totdat die zijn vastgesteld.']);
    figure(d, 'CAT-05', 'maint-inuse', 16, 103, 177, '1A', 'Bestaand gebruik van de gedeelde definitie helpt de impact bepalen.', {badgeX: 192});
    end(d);
    local(d, 2, 163, 'Kies alleen een andere regel als de betekenis dat vraagt');
    body(d, 181, ['Numeric Component Aggregation: Sum telt capaciteit of aantallen maal de hoeveelheid.',
        'Keep distinct values behoudt eigenschappen zoals MHz. Is de bestaande betekenis juist? Laat de regel staan.']);
    figure(d, 'CAT-05', 'catalog-inuse-aggregation', 16, 196, 177, '2A', 'Voorbeeld: Werkgeheugen blijft op Sum; een juiste regel hoef je niet opnieuw op te slaan.', {badgeX: 192});
    text(d, 16, 240, ['Wijziging? Kies Opslaan (2B), heropen en vergelijk dezelfde voorbeelden.',
        'De regel werkt direct door. Onverwacht? Herstel de oude regel,',
        'controleer opnieuw en bespreek de oorzaak met de cataloguseigenaar.'], {size: 2.55, lh: 3.9});
    figure(d, 'CAT-05', 'catalog-aggregation-save', 164, 237, 28, '2B', 'Opslaan.', {badgeX: 192});
    end(d);
    done(d, 'De regel past bij het feit; dezelfde model- en assetvoorbeelden zijn gecontroleerd.'); links(d, ['CAT-02', 'CAT-03']); maintenance[9] = d.render();
    result['CAT-05'] = maintenance;

    d = chapter('CAT-06', 4, 1, 3, 'Controleer een onzekere modelcode of specificatie voordat je invoert.', 'Label + fabrikantbron');
    heading(d, 64, '1 Gebruik deze gids voor een concrete open vraag');
    body(d, 76, ['Kom hier voor CAT-01 of CAT-02 wanneer een modelcode of specificatie nog onzeker is.',
        'Klopt de volledige code? Geldt de bron voor die variant? Onderbouwt zij dit ene feit?',
        'CAT-06 controleert. Invoeren of opslaan gebeurt in de passende taakgids.']);
    heading(d, 101, 'Werkvoorbeeld: vind en lees het fysieke fabrikantlabel');
    figure(d, 'CAT-06', 'physical-context', 16, 112, 98, '1A', 'Onderzijde; het fabrikantlabel zit bij de ventilatie.', {marks: [{x: 790, y: 420, w: 260, h: 150, padding: 14, target: 'Fysiek fabrikantlabel'}]});
    figure(d, 'CAT-06', 'manufacturer-label', 121, 112, 72, '1B', 'Detail van hetzelfde fabrikantlabel.', {badgeX: 192});
    heading(d, 202, 'Lees de verschillende identifiers als verschillende gegevens');
    body(d, 214, ['Fabrikant: HP. Basismodel: HP ProBook 450 G8. Complete fabrikantcode: 2E9F8EA#ABH.',
        'De suffix #ABH hoort bij die code. S/N identificeert een uniek apparaat.',
        'Een Inbit-tag en een software-Product ID zijn andere identifiers; gebruik ze niet als modelcode.']);
    next(d, 'Volgende: controleer op pagina 2 precies een feit tegen de fabrikantbron.', ['CAT-01', 'CAT-02']);
    result['CAT-06'] = {1: d.render()};

    d = chapter('CAT-06', 4, 2, 3, 'Bepaal welk feit de bron voor deze variant werkelijk ondersteunt.', 'Dezelfde labelcode + bron');
    heading(d, 64, '2 Controleer een feit en het bereik van de bron');
    body(d, 76, ['Werkvoorbeeld: geldt de HP-familiepagina ook voor exact 2E9F8EA#ABH?',
        'Controleer product, generatie en complete code; een familiepagina bewijst niet iedere fabrieksvariant.']);
    figure(d, 'CAT-06', 'hp-product-card', 16, 96, 66, '2A', 'Productkaart op de HP-pagina.', {marks: [{x: 8, y: 361, w: 223, h: 46, padding: 3, target: 'Displayregel op de familiepagina'}]});
    text(d, 94, 106, 'Wat bewijst deze bron?', {size: 3.2, weight: 800, fill: C.cat});
    text(d, 94, 117, ['Titel: HP ProBook 450 G8.', 'Familie en generatie komen overeen.', 'De exacte code 2E9F8EA#ABH staat hier niet.',
        'Dit is geen bewijs van de volledige', 'fabrieksconfiguratie van die suffix.'], {size: 2.65, lh: 4});
    text(d, 94, 151, 'Onderbouwd: 15,6 inch', {size: 3.2, weight: 800, fill: C.cat});
    text(d, 94, 162, ['De pagina geeft deze diagonaal voor de familie.', 'Neem FHD, Touch of Sure View niet zomaar over:', 'de bron presenteert die als opties.'], {size: 2.65, lh: 4});
    text(d, 94, 190, 'Niet bevestigd: RAM van deze code', {size: 3.05, weight: 800, fill: C.cat});
    text(d, 94, 201, ['De pagina geeft geen exacte RAM-configuratie.', 'Vul dus niet willekeurig 8 of 32 GB in.', 'Een maximum, keuzelijst of later aangepast asset',
        'bewijst evenmin de fabrieksbaseline.'], {size: 2.65, lh: 4});
    body(d, 237, ['Bron: HP - HP ProBook 450 G8. Open de productpagina:'], {size: 2.65});
    d.raw('<a href="https://www.hp.com/us-en/laptops/business/probook-400/product-card/hp-probook-450-g8.html">');
    text(d, 16, 246, 'hp.com/us-en/laptops/business/probook-400/product-card/hp-probook-450-g8.html', {size: 2.35, fill: C.cat}); d.raw('</a>');
    next(d, 'Volgende: gebruik alleen het bevestigde resultaat; kies de uitkomst op pagina 3.', ['CAT-01', 'CAT-02']);
    result['CAT-06'][2] = d.render();
    return result;
}
