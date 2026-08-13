export const GUIDE_STATUSES = Object.freeze({
    WORKING_DRAFT: 'Working draft',
    INTERNAL_REVIEW: 'Internal review candidate',
    THIRD_PARTY_APPROVED: 'Third-party approved',
    SUPERSEDED: 'Superseded',
});

export const GUIDE_TOKENS = Object.freeze({
    page: Object.freeze({ width: 210, height: 297, margin: 12 }),
    colors: Object.freeze({
        ink: '#102033',
        muted: '#53657A',
        line: '#C8D5E2',
        faint: '#F8FAFC',
        white: '#FFFFFF',
        ac: '#2563EB',
        acSoft: '#EFF6FF',
        sc: '#0E8A75',
        scSoft: '#ECFDF8',
        ast: '#138A43',
        astSoft: '#ECFDF3',
        wf: '#C66A00',
        wfSoft: '#FFF7ED',
        cmp: '#B66A00',
        cmpSoft: '#FFF8E6',
        usr: '#4F46E5',
        usrSoft: '#EEF2FF',
        help: '#E83448',
        helpSoft: '#FFF1F3',
        orange: '#C66A00',
        orangeSoft: '#FFF7ED',
        green: '#138A43',
        greenSoft: '#ECFDF3',
        teal: '#0E8A75',
        tealSoft: '#ECFDF8',
    }),
    type: Object.freeze({
        fontFamily: 'Arial, Helvetica, sans-serif',
        title: 8.3,
        stepTitle: 3.85,
        body: 2.35,
        caption: 2.05,
        footer: 1.75,
    }),
    components: Object.freeze({
        stepBadgeRadius: 7.2,
        stepBadgeStroke: 1.9,
        imageBadgeRadius: 4.05,
        imageBadgeStroke: 0.75,
        familyBadgeRadius: 2.35,
        familyBadgeStroke: 0.5,
        guideChipHeight: 7,
        guideChipRadius: 2.3,
        focusStroke: 1.15,
        qrSize: 22,
        maxRelatedGuides: 5,
    }),
});

const colors = GUIDE_TOKENS.colors;

export const GUIDE_FAMILIES = Object.freeze({
    AC: Object.freeze({ color: colors.ac, fill: colors.acSoft }),
    SC: Object.freeze({ color: colors.sc, fill: colors.scSoft }),
    AST: Object.freeze({ color: colors.ast, fill: colors.astSoft }),
    WF: Object.freeze({ color: colors.wf, fill: colors.wfSoft }),
    CMP: Object.freeze({ color: colors.cmp, fill: colors.cmpSoft }),
    USR: Object.freeze({ color: colors.usr, fill: colors.usrSoft }),
    HELP: Object.freeze({ color: colors.help, fill: colors.helpSoft }),
});

export const GUIDE_REGISTRY = Object.freeze({
    'AC-01': Object.freeze({ family: 'AC', title: 'Login', status: GUIDE_STATUSES.INTERNAL_REVIEW }),
    'AC-02': Object.freeze({ family: 'AC', title: 'Eigen wachtwoord wijzigen', status: GUIDE_STATUSES.WORKING_DRAFT }),
    'SC-01': Object.freeze({ family: 'SC', title: 'Asset vinden en openen', status: GUIDE_STATUSES.INTERNAL_REVIEW }),
    'AST-02': Object.freeze({ family: 'AST', title: 'Refurbishment route', status: GUIDE_STATUSES.INTERNAL_REVIEW }),
    'AST-03': Object.freeze({ family: 'AST', title: 'Asset registreren en labelen', status: GUIDE_STATUSES.WORKING_DRAFT }),
    'AST-04': Object.freeze({ family: 'AST', title: 'Werk afronden en overdragen', status: GUIDE_STATUSES.WORKING_DRAFT }),
    'AST-05': Object.freeze({ family: 'AST', title: 'Asset beoordelen en vrijgeven', status: GUIDE_STATUSES.WORKING_DRAFT }),
    'WF-01': Object.freeze({ family: 'WF', title: 'Workflow starten', status: GUIDE_STATUSES.INTERNAL_REVIEW }),
    'WF-02': Object.freeze({ family: 'WF', title: 'Workflow uitvoeren en afronden', status: GUIDE_STATUSES.INTERNAL_REVIEW }),
    'CMP-01': Object.freeze({ family: 'CMP', title: 'Bestaand component plaatsen', status: GUIDE_STATUSES.INTERNAL_REVIEW }),
    'CMP-02': Object.freeze({ family: 'CMP', title: 'Nieuw component registreren en plaatsen', status: GUIDE_STATUSES.WORKING_DRAFT }),
    'CMP-04': Object.freeze({ family: 'CMP', title: 'Component naar tray verplaatsen', status: GUIDE_STATUSES.WORKING_DRAFT }),
    'HELP-01': Object.freeze({ family: 'HELP', title: 'Problemen en hulp', status: GUIDE_STATUSES.WORKING_DRAFT }),
    'USR-01': Object.freeze({ family: 'USR', title: 'Gebruiker toevoegen', status: GUIDE_STATUSES.INTERNAL_REVIEW }),
    'USR-02': Object.freeze({ family: 'USR', title: 'Rol en rechten wijzigen', status: GUIDE_STATUSES.INTERNAL_REVIEW }),
    'USR-03': Object.freeze({ family: 'USR', title: 'Wachtwoord resetten', status: GUIDE_STATUSES.WORKING_DRAFT }),
    'USR-04': Object.freeze({ family: 'USR', title: 'Gebruiker uitschakelen of herstellen', status: GUIDE_STATUSES.WORKING_DRAFT }),
    'USR-05': Object.freeze({ family: 'USR', title: 'Groepen beheren', status: GUIDE_STATUSES.WORKING_DRAFT }),
});

export function xml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function dataAttrs(values = {}) {
    return Object.entries(values)
        .filter(([, value]) => value !== undefined && value !== null)
        .map(([key, value]) => ` data-${key}="${xml(value)}"`)
        .join('');
}

export function guideReference(code, options = {}) {
    const entry = GUIDE_REGISTRY[code];
    if (!entry) throw new Error(`Unknown guide reference: ${code}`);
    return {
        code,
        family: entry.family,
        title: entry.title,
        label: `${code} ${entry.title}`,
        status: entry.status,
        ...options,
    };
}

export function normalizeFocusBounds(mark) {
    const padding = mark.padding ?? 0;
    return {
        ...mark,
        x: mark.x - padding,
        y: mark.y - padding,
        w: mark.w + padding * 2,
        h: mark.h + padding * 2,
    };
}

export function layoutRelatedGuides(references, options = {}) {
    const {
        firstRowX = 38,
        secondRowX = 12,
        firstRowY = 268,
        secondRowY = 277,
        gap = 3,
        rightEdge = 174,
    } = options;
    if (references.length > GUIDE_TOKENS.components.maxRelatedGuides) {
        throw new Error(`Related-guide capacity is ${GUIDE_TOKENS.components.maxRelatedGuides}; received ${references.length}.`);
    }
    const defaultSplit = references.length >= 4 ? 2 : references.length;
    const cursors = {
        1: { x: firstRowX, y: firstRowY },
        2: { x: secondRowX, y: secondRowY },
    };
    return references.map((reference, index) => {
        const rowNumber = reference.row ?? (index < defaultSplit ? 1 : 2);
        const cursor = cursors[rowNumber];
        if (!cursor) throw new Error(`Unsupported related-guide row: ${rowNumber}`);
        const width = reference.width ?? Math.max(34, 13 + reference.label.length * 1.08);
        const placement = { ...reference, row: rowNumber, x: cursor.x, y: cursor.y, width };
        cursor.x += width + gap;
        if (placement.x + width > rightEdge) {
            throw new Error(`${reference.label} exceeds the related-guide area on row ${rowNumber}.`);
        }
        return placement;
    });
}

export class SvgGuideDocument {
    constructor(images = {}, idPrefix = 'page') {
        this.images = images;
        this.idPrefix = idPrefix;
        this.parts = [];
        this.defs = [];
        this.uid = 0;
    }

    nextId(prefix) {
        this.uid += 1;
        return `${this.idPrefix}-${prefix}-${this.uid}`;
    }

    raw(markup) {
        this.parts.push(markup);
    }

    rect(x, y, w, h, fill = 'none', stroke = 'none', sw = 0, r = 0, opacity = 1, data = {}) {
        this.parts.push(`<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${r}" fill="${fill}" stroke="${stroke}" stroke-width="${sw}" opacity="${opacity}"${dataAttrs(data)}/>`);
    }

    line(x1, y1, x2, y2, stroke = colors.line, sw = 0.4) {
        this.parts.push(`<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="${stroke}" stroke-width="${sw}"/>`);
    }

    circle(cx, cy, r, stroke, sw = 1, fill = 'none', opacity = 1, data = {}) {
        this.parts.push(`<circle cx="${cx}" cy="${cy}" r="${r}" fill="${fill}" stroke="${stroke}" stroke-width="${sw}" opacity="${opacity}"${dataAttrs(data)}/>`);
    }

    ellipse(cx, cy, rx, ry, stroke, sw = 1, fill = 'none', opacity = 1, data = {}) {
        this.parts.push(`<ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="${fill}" stroke="${stroke}" stroke-width="${sw}" opacity="${opacity}"${dataAttrs(data)}/>`);
    }

    text(x, y, value, opts = {}) {
        const {
            size = 3.2,
            weight = 400,
            fill = colors.ink,
            anchor = 'start',
            lh = size * 1.35,
            family = GUIDE_TOKENS.type.fontFamily,
            data = {},
        } = opts;
        const lines = Array.isArray(value) ? value : [value];
        const tspans = lines.map((line, index) => `<tspan x="${x}" dy="${index === 0 ? 0 : lh}">${xml(line)}</tspan>`).join('');
        this.parts.push(`<text x="${x}" y="${y}" font-family="${family}" font-size="${size}" font-weight="${weight}" fill="${fill}" text-anchor="${anchor}"${dataAttrs(data)}>${tspans}</text>`);
    }

    centeredText(x, y, value, opts = {}) {
        const {
            size = 3.2,
            weight = 400,
            fill = colors.ink,
            anchor = 'middle',
            family = GUIDE_TOKENS.type.fontFamily,
            data = {},
        } = opts;
        this.parts.push(`<text x="${x}" y="${y}" font-family="${family}" font-size="${size}" font-weight="${weight}" fill="${fill}" text-anchor="${anchor}" dominant-baseline="central"${dataAttrs({ component: 'centered-text', ...data })}>${xml(value)}</text>`);
    }

    image(key, frame, crop, opts = {}) {
        const img = this.images[key];
        if (!img) throw new Error(`Missing image ${key}`);
        const area = crop ?? { x: 0, y: 0, w: img.width, h: img.height };
        const contain = opts.fit === 'contain';
        const scale = contain
            ? Math.min(frame.w / area.w, frame.h / area.h)
            : Math.max(frame.w / area.w, frame.h / area.h);
        const visibleW = contain ? area.w * scale : frame.w;
        const visibleH = contain ? area.h * scale : frame.h;
        const visibleX = contain ? frame.x + (frame.w - visibleW) / 2 : frame.x;
        const visibleY = contain ? frame.y + (frame.h - visibleH) / 2 : frame.y;
        const x = visibleX - area.x * scale;
        const y = visibleY - area.y * scale;
        const clipId = this.nextId('clip');
        this.defs.push(`<clipPath id="${clipId}"><rect x="${visibleX}" y="${visibleY}" width="${visibleW}" height="${visibleH}" rx="${opts.r ?? 1.2}"/></clipPath>`);
        this.rect(frame.x, frame.y, frame.w, frame.h, colors.white, colors.line, 0.4, opts.r ?? 1.2);
        this.parts.push(`<image href="${img.href}" x="${x}" y="${y}" width="${img.width * scale}" height="${img.height * scale}" clip-path="url(#${clipId})"/>`);
        this.rect(frame.x, frame.y, frame.w, frame.h, 'none', colors.line, 0.4, opts.r ?? 1.2);
        return { x, y, scale, clipId };
    }

    familyBadge(cx, cy, family, opts = {}) {
        const palette = GUIDE_FAMILIES[family];
        if (!palette) throw new Error(`Unknown guide family: ${family}`);
        const radius = opts.radius ?? GUIDE_TOKENS.components.familyBadgeRadius;
        const fontSize = opts.fontSize ?? (family.length > 3 ? 1.2 : 1.55);
        const fill = opts.fill ?? colors.white;
        const opacity = opts.opacity ?? 1;
        this.parts.push(`<g data-component="family-badge" data-family="${family}">
            <circle cx="${cx}" cy="${cy}" r="${radius}" fill="${fill}" stroke="${palette.color}" stroke-width="${opts.strokeWidth ?? GUIDE_TOKENS.components.familyBadgeStroke}" opacity="${opacity}"/>
            <text x="${cx}" y="${cy}" font-family="${GUIDE_TOKENS.type.fontFamily}" font-size="${fontSize}" font-weight="900" fill="${palette.color}" text-anchor="middle" dominant-baseline="central" data-role="family-label">${xml(family)}</text>
        </g>`);
    }

    stepBadge(cx, cy, label, color) {
        this.parts.push(`<g data-component="step-badge">
            <circle cx="${cx}" cy="${cy}" r="${GUIDE_TOKENS.components.stepBadgeRadius}" fill="${colors.white}" fill-opacity="0.92" stroke="${color}" stroke-width="${GUIDE_TOKENS.components.stepBadgeStroke}"/>
            <text x="${cx}" y="${cy}" font-family="${GUIDE_TOKENS.type.fontFamily}" font-size="4.8" font-weight="900" fill="${color}" text-anchor="middle" dominant-baseline="central" data-role="badge-label">${xml(label)}</text>
        </g>`);
    }

    imageBadge(cx, cy, label, color) {
        this.parts.push(`<g data-component="image-badge">
            <circle cx="${cx}" cy="${cy}" r="${GUIDE_TOKENS.components.imageBadgeRadius}" fill="${colors.white}" fill-opacity="0.58" stroke="${color}" stroke-width="${GUIDE_TOKENS.components.imageBadgeStroke}"/>
            <text x="${cx}" y="${cy}" font-family="${GUIDE_TOKENS.type.fontFamily}" font-size="1.95" font-weight="800" fill="${color}" text-anchor="middle" dominant-baseline="central" data-role="badge-label">${xml(label)}</text>
        </g>`);
    }

    guideChip(x, y, width, reference) {
        const ref = typeof reference === 'string' ? guideReference(reference) : reference;
        const palette = GUIDE_FAMILIES[ref.family];
        const height = GUIDE_TOKENS.components.guideChipHeight;
        const cy = y + height / 2;
        const iconX = x + 5;
        this.parts.push(`<g data-component="guide-chip" data-guide-code="${ref.code}">
            <rect x="${x}" y="${y}" width="${width}" height="${height}" rx="${GUIDE_TOKENS.components.guideChipRadius}" fill="${palette.fill}" stroke="${palette.color}" stroke-width="0.55"/>
            <circle cx="${iconX}" cy="${cy}" r="${GUIDE_TOKENS.components.familyBadgeRadius}" fill="${colors.white}" stroke="${palette.color}" stroke-width="${GUIDE_TOKENS.components.familyBadgeStroke}"/>
            <text x="${iconX}" y="${cy}" font-family="${GUIDE_TOKENS.type.fontFamily}" font-size="${ref.family.length > 3 ? 1.2 : 1.55}" font-weight="900" fill="${palette.color}" text-anchor="middle" dominant-baseline="central" data-role="family-label">${xml(ref.family)}</text>
            <text x="${x + 9.5}" y="${cy}" font-family="${GUIDE_TOKENS.type.fontFamily}" font-size="2.25" font-weight="800" fill="${palette.color}" dominant-baseline="central" data-role="guide-label">${xml(ref.label)}</text>
        </g>`);
    }

    chip(x, y, width, label) {
        const code = String(label).match(/^[A-Z]+-\d+/)?.[0];
        const reference = code && GUIDE_REGISTRY[code]
            ? { ...guideReference(code), label }
            : { code: code ?? label, family: String(label).split('-')[0], label };
        this.guideChip(x, y, width, reference);
    }

    focusMark(placement, rawMark, opts = {}) {
        const mark = normalizeFocusBounds(rawMark);
        const x = placement.x + mark.x * placement.scale;
        const y = placement.y + mark.y * placement.scale;
        const w = mark.w * placement.scale;
        const h = mark.h * placement.scale;
        const clip = placement.clipId ? ` clip-path="url(#${placement.clipId})"` : '';
        const common = `fill="none" stroke="${opts.color ?? colors.help}" stroke-width="${mark.sw ?? opts.strokeWidth ?? GUIDE_TOKENS.components.focusStroke}" opacity="${opts.opacity ?? 0.94}" vector-effect="non-scaling-stroke"${clip} data-component="focus-mark" data-target="${xml(mark.target ?? '')}"`;
        if (mark.shape === 'ellipse' || mark.shape === 'circle') {
            this.parts.push(`<ellipse cx="${x + w / 2}" cy="${y + h / 2}" rx="${w / 2}" ry="${h / 2}" ${common}/>`);
        } else {
            this.parts.push(`<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="${Math.min(2, w / 5)}" ${common}/>`);
        }
        return { x, y, w, h, target: mark.target ?? '' };
    }

    qrPlaceholder(x, y, size = GUIDE_TOKENS.components.qrSize) {
        this.rect(x, y, size, size, colors.white, colors.ink, 0.6, 0.8);
        this.line(x + 3, y + 3, x + 8, y + 3, colors.ink, 0.8);
        this.line(x + 3, y + 3, x + 3, y + 8, colors.ink, 0.8);
        this.line(x + size - 3, y + 3, x + size - 8, y + 3, colors.ink, 0.8);
        this.line(x + size - 3, y + 3, x + size - 3, y + 8, colors.ink, 0.8);
        this.line(x + 3, y + size - 3, x + 8, y + size - 3, colors.ink, 0.8);
        this.line(x + 3, y + size - 3, x + 3, y + size - 8, colors.ink, 0.8);
        this.line(x + size - 3, y + size - 3, x + size - 8, y + size - 3, colors.ink, 0.8);
        this.line(x + size - 3, y + size - 3, x + size - 3, y + size - 8, colors.ink, 0.8);
        this.centeredText(x + size / 2, y + size / 2, 'QR volgt', { size: 2.1, weight: 800, fill: colors.muted });
    }

    render() {
        return `<?xml version="1.0" encoding="UTF-8"?>\n<svg xmlns="http://www.w3.org/2000/svg" width="210mm" height="297mm" viewBox="0 0 210 297"><defs>${this.defs.join('')}</defs><rect width="210" height="297" fill="${colors.white}"/>${this.parts.join('')}</svg>`;
    }
}

export function drawContextStrip(doc, context, options = {}) {
    const { x = 12, y = 38, width = 186, height = 18 } = options;
    doc.rect(x, y, width, height, colors.faint, colors.line, 0.45, 1.6, 1, { component: 'context-strip' });
    const colW = (width - 10) / context.length;
    context.forEach((item, index) => {
        const itemX = x + 5 + index * colW;
        doc.text(itemX, y + 8, item.label, { size: 2.2, weight: 800, fill: colors.muted });
        const valueY = y + 13.15;
        const ref = item.guide
            ?? (item.guideCode ? guideReference(item.guideCode) : null)
            ?? (item.guideFamily ? { family: item.guideFamily } : null);
        if (ref) {
            doc.familyBadge(itemX + 1.85, valueY, ref.family, { radius: 1.85, fontSize: 1.15 });
            doc.centeredText(itemX + 5, valueY, item.value, { size: 2.75, weight: 800, fill: item.color ?? GUIDE_FAMILIES[ref.family].color, anchor: 'start' });
        } else {
            doc.centeredText(itemX, valueY, item.value, { size: 2.75, weight: 800, fill: item.color ?? colors.ink, anchor: 'start' });
        }
    });
}

export function drawCompletionRow(doc, text, options = {}) {
    const { x = 12, y = 253, width = 150, height = 12 } = options;
    const cy = y + height / 2;
    doc.rect(x, y, width, height, colors.greenSoft, '#86EFAC', 0.45, 1.8, 1, { component: 'completion-row' });
    doc.centeredText(x + 5, cy, 'Klaar als', { size: 2.8, weight: 900, fill: colors.green, anchor: 'start' });
    doc.centeredText(x + 26, cy, text, { size: 2.25, fill: colors.green, anchor: 'start' });
}

export function drawRelatedGuideRows(doc, references, options = {}) {
    const placements = layoutRelatedGuides(references, options);
    doc.centeredText(12, 271.5, options.label ?? 'Relevante gidsen', { size: 2.1, weight: 800, fill: colors.muted, anchor: 'start' });
    placements.forEach((placement) => doc.guideChip(placement.x, placement.y, placement.width, placement));
    return placements;
}

export function validateGuideDefinition(page) {
    const errors = [];
    if (!page.code || !GUIDE_REGISTRY[page.code]) errors.push(`Unknown or missing guide code: ${page.code ?? '(missing)'}`);
    if (!page.family || !GUIDE_FAMILIES[page.family]) errors.push(`Unknown or missing family: ${page.family ?? '(missing)'}`);
    if (!page.title) errors.push('Guide title is required.');
    if (!page.version) errors.push('Guide version is required.');
    const serialized = JSON.stringify(page);
    if (serialized.includes('dev.inbit')) errors.push('Operator-facing guide definitions may not contain dev.inbit.');
    const labels = new Set();
    (page.steps ?? []).forEach((step) => {
        if (!step.number || !step.title) errors.push('Every step requires a number and title.');
        (step.visuals ?? []).forEach((visual) => {
            if (!visual.label || !visual.caption) errors.push(`Every visual in step ${step.number} requires a label and caption.`);
            if (labels.has(visual.label)) errors.push(`Duplicate visual label: ${visual.label}`);
            labels.add(visual.label);
        });
    });
    if ((page.related ?? []).length > GUIDE_TOKENS.components.maxRelatedGuides) {
        errors.push(`A guide may show at most ${GUIDE_TOKENS.components.maxRelatedGuides} related guides.`);
    }
    (page.related ?? []).forEach((reference) => {
        if (!reference.code || !GUIDE_REGISTRY[reference.code]) errors.push(`Unknown related guide: ${reference.code ?? reference.label ?? '(missing)'}`);
        if (reference.code && reference.label !== `${reference.code} ${GUIDE_REGISTRY[reference.code]?.title}`) {
            errors.push(`Related guide ${reference.code} must use its full registered name.`);
        }
    });
    if (errors.length) throw new Error(`${page.code ?? 'Guide'} validation failed:\n- ${errors.join('\n- ')}`);
    return true;
}

export async function inspectRenderedGuideComponents(page) {
    const result = await page.evaluate(() => {
        const errors = [];
        const chipMetrics = [];
        const badges = [...document.querySelectorAll('[data-component="family-badge"], [data-component="step-badge"], [data-component="image-badge"]')];
        badges.forEach((group) => {
            const circle = group.querySelector('circle');
            const text = group.querySelector('text');
            if (!circle || !text) return;
            const cy = Number(circle.getAttribute('cy'));
            const textY = Number(text.getAttribute('y'));
            if (Math.abs(cy - textY) > 0.001 || text.getAttribute('dominant-baseline') !== 'central') {
                errors.push(`${group.dataset.component} ${group.dataset.family || ''} does not use shared vertical centering.`.trim());
            }
        });
        const chips = [...document.querySelectorAll('[data-component="guide-chip"]')];
        chips.forEach((group) => {
            const frame = group.querySelector('rect').getBBox();
            const label = group.querySelector('[data-role="guide-label"]').getBBox();
            chipMetrics.push({
                code: group.dataset.guideCode,
                frameWidth: Number(frame.width.toFixed(2)),
                requiredWidth: Number((label.x + label.width - frame.x + 1).toFixed(2)),
            });
            if (label.x + label.width > frame.x + frame.width - 1) {
                const overflow = label.x + label.width - (frame.x + frame.width - 1);
                errors.push(`${group.dataset.guideCode} label overflows its chip by ${overflow.toFixed(2)} SVG units.`);
            }
            const circle = group.querySelector('circle');
            const family = group.querySelector('[data-role="family-label"]');
            if (Number(circle.getAttribute('cy')) !== Number(family.getAttribute('y'))) {
                errors.push(`${group.dataset.guideCode} family label is not vertically centered.`);
            }
        });
        return { errors, badgeCount: badges.length, chipCount: chips.length, chipMetrics };
    });
    if (result.errors.length) throw new Error(`Rendered component QA failed:\n- ${result.errors.join('\n- ')}\n${JSON.stringify(result.chipMetrics)}`);
    return result;
}
