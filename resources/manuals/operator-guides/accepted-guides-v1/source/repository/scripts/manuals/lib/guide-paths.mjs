import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const libDir = path.dirname(fileURLToPath(import.meta.url));

export const repoRoot = path.resolve(libDir, '..', '..', '..');
export const guideResourceRoot = path.resolve(
    process.env.SNIPEIT_GUIDE_RESOURCE_ROOT ?? path.join(repoRoot, 'resources', 'manuals', 'operator-guides'),
);
export const evidenceRoot = path.resolve(
    process.env.SNIPEIT_GUIDE_EVIDENCE_ROOT ?? path.join(guideResourceRoot, 'evidence'),
);
export const acceptedPdfRoot = path.resolve(
    process.env.SNIPEIT_GUIDE_PUBLISHED_PDF_ROOT ?? path.join(guideResourceRoot, 'pdf'),
);
export const draftPdfRoot = path.resolve(
    process.env.SNIPEIT_GUIDE_DRAFT_PDF_ROOT ?? path.join(guideResourceRoot, 'drafts'),
);
export const baselineRoot = path.resolve(
    process.env.SNIPEIT_GUIDE_BASELINE_ROOT ?? path.join(guideResourceRoot, 'baselines'),
);
export const generatedOutputRoot = path.resolve(
    process.env.SNIPEIT_GUIDE_OUTPUT_ROOT ?? path.join(repoRoot, 'output', 'manuals'),
);
export const repoPdfOutputRoot = path.resolve(
    process.env.SNIPEIT_GUIDE_PDF_OUT_DIR ?? path.join(repoRoot, 'output', 'pdf'),
);

function readJson(file) {
    if (!fs.existsSync(file)) {
        throw new Error(`Missing operator-guide manifest: ${file}`);
    }

    return JSON.parse(fs.readFileSync(file, 'utf8'));
}

const evidenceManifest = readJson(path.join(evidenceRoot, 'manifest.json'));
const evidenceById = new Map(evidenceManifest.sources.map((entry) => [entry.id, entry]));

export function evidencePath(sourceId) {
    const entry = evidenceById.get(sourceId);
    if (!entry) {
        throw new Error(`Unknown canonical evidence source: ${sourceId}`);
    }

    return path.join(evidenceRoot, entry.file);
}

export function baselinePath(file) {
    return path.join(baselineRoot, file);
}

export function guideOutputDir(name) {
    return path.join(generatedOutputRoot, 'proofs', name);
}

export function captureOutputDir(name) {
    return path.join(generatedOutputRoot, 'captures', name);
}

export function loadGuideDependency(name) {
    try {
        return require(name);
    } catch (localError) {
        const modulesRoot = process.env.GUIDE_NODE_MODULES_ROOT;
        if (modulesRoot) {
            try {
                return require(path.join(path.resolve(modulesRoot), name));
            } catch (externalError) {
                throw new Error(
                    `Cannot load ${name} from GUIDE_NODE_MODULES_ROOT=${modulesRoot}: ${externalError.message}`,
                    { cause: externalError },
                );
            }
        }

        throw new Error(
            `Cannot load ${name}. Run npm install in scripts/manuals or set GUIDE_NODE_MODULES_ROOT.`,
            { cause: localError },
        );
    }
}

function executableExtensions() {
    if (process.platform !== 'win32') return [''];
    return (process.env.PATHEXT ?? '.EXE;.CMD;.BAT')
        .split(';')
        .filter(Boolean)
        .map((extension) => extension.toLowerCase());
}

function findOnPath(name) {
    const extensions = path.extname(name) ? [''] : executableExtensions();
    for (const directory of (process.env.PATH ?? '').split(path.delimiter).filter(Boolean)) {
        for (const extension of extensions) {
            const candidate = path.join(directory, `${name}${extension}`);
            if (fs.existsSync(candidate)) return candidate;
        }
    }

    return null;
}

export function resolveCommand(environmentVariable, names, { required = true } = {}) {
    const configured = process.env[environmentVariable];
    if (configured) {
        const resolved = path.resolve(configured);
        if (!fs.existsSync(resolved)) {
            throw new Error(`${environmentVariable} does not exist: ${resolved}`);
        }
        return resolved;
    }

    for (const name of Array.isArray(names) ? names : [names]) {
        if (path.isAbsolute(name) && fs.existsSync(name)) return name;
        const resolved = findOnPath(name);
        if (resolved) return resolved;
    }

    if (!required) return null;
    throw new Error(`Cannot find ${Array.isArray(names) ? names.join(' or ') : names}; set ${environmentVariable}.`);
}

export function resolveChromeExecutable({ required = true } = {}) {
    const candidates = process.platform === 'win32'
        ? [
            path.join(process.env.PROGRAMFILES ?? 'C:\\Program Files', 'Google', 'Chrome', 'Application', 'chrome.exe'),
            path.join(process.env['PROGRAMFILES(X86)'] ?? 'C:\\Program Files (x86)', 'Google', 'Chrome', 'Application', 'chrome.exe'),
            process.env.LOCALAPPDATA
                ? path.join(process.env.LOCALAPPDATA, 'Google', 'Chrome', 'Application', 'chrome.exe')
                : '',
        ].filter(Boolean)
        : ['/usr/bin/google-chrome', '/usr/bin/chromium', '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'];

    const configured = process.env.GUIDE_CHROME_PATH;
    if (configured) candidates.unshift(path.resolve(configured));
    const existing = candidates.find((candidate) => fs.existsSync(candidate));
    if (existing) return existing;

    const fromPath = findOnPath(process.platform === 'win32' ? 'chrome' : 'google-chrome')
        ?? findOnPath('chromium');
    if (fromPath) return fromPath;
    if (!required) return null;
    throw new Error('Cannot find Chrome or Chromium; set GUIDE_CHROME_PATH.');
}

export function browserLaunchOptions(options = {}) {
    const executablePath = resolveChromeExecutable({ required: false });
    return executablePath ? { ...options, executablePath } : options;
}

export function runtimeSummary() {
    return {
        repoRoot,
        guideResourceRoot,
        evidenceRoot,
        acceptedPdfRoot,
        draftPdfRoot,
        baselineRoot,
        generatedOutputRoot,
        repoPdfOutputRoot,
        platform: process.platform,
        home: os.homedir(),
    };
}
