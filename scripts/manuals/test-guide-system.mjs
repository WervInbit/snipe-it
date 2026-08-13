import assert from 'node:assert/strict';
import {
    GUIDE_FAMILIES,
    GUIDE_REGISTRY,
    SvgGuideDocument,
    guideReference,
    layoutRelatedGuides,
    normalizeFocusBounds,
    validateGuideDefinition,
} from './lib/guide-system.mjs';

for (const [code, guide] of Object.entries(GUIDE_REGISTRY)) {
    assert.equal(code.split('-')[0], guide.family, `${code} has the wrong family.`);
    assert.ok(GUIDE_FAMILIES[guide.family], `${code} uses an unknown family.`);
}

const doc = new SvgGuideDocument();
doc.familyBadge(10, 10, 'USR');
doc.stepBadge(20, 20, '1', GUIDE_FAMILIES.USR.color);
doc.imageBadge(30, 30, '1A', GUIDE_FAMILIES.USR.color);
doc.guideChip(40, 40, 55, guideReference('USR-02'));
const markup = doc.render();
assert.match(markup, /dominant-baseline="central"/);
assert.doesNotMatch(markup, /y="10\.[1-9]/, 'Family labels must not use guessed baseline offsets.');

const related = [
    guideReference('AC-02', { width: 57 }),
    guideReference('USR-02', { width: 59 }),
    guideReference('USR-03', { width: 47, row: 2 }),
    guideReference('USR-04', { width: 66, row: 2 }),
    guideReference('USR-05', { width: 42, row: 2 }),
];
const placements = layoutRelatedGuides(related);
assert.deepEqual(placements.map((item) => item.row), [1, 1, 2, 2, 2]);
assert.ok(placements.every((item) => item.x + item.width <= 174));

assert.deepEqual(
    normalizeFocusBounds({ x: 10, y: 20, w: 30, h: 40, padding: 3 }),
    { x: 7, y: 17, w: 36, h: 46, padding: 3 },
);

validateGuideDefinition({
    code: 'USR-01',
    family: 'USR',
    title: 'Gebruiker toevoegen',
    version: 'Draft v7',
    steps: [{ number: '1', title: 'Test', visuals: [{ label: '1A', caption: 'Testbeeld.' }] }],
    related,
});

console.log(JSON.stringify({ registry: Object.keys(GUIDE_REGISTRY).length, related: placements.length, status: 'ok' }));
