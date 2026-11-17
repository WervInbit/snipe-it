"""Audit installed SDK references and package the owner-run script, without app execution."""

from __future__ import annotations

import hashlib
import html
import json
from pathlib import Path
import re
import zipfile


ROOT = Path(__file__).resolve().parents[2]
DEST = ROOT / "output/manuals/affinity-conversion-2026-11-05"
SDK = Path("C:/Program Files/WindowsApps/Canva.Affinity_3.3.3.5004_x64__8a0j1tnjnt4a4/App/Resources/JSLib")


def digest(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main() -> None:
    tests = json.loads((DEST / "qa/offline-tests.json").read_text(encoding="utf-8"))
    if tests["status"] != "passed" or tests["affinityExecuted"]:
        raise ValueError("Offline verification is missing or failed.")
    references = {
        "application.js": [
            "module.exports.Application = Application", "static get majorVersion()",
            "static get minorVersion()", "static get shortVersion()", "static get buildVersion()",
            "static get productPrimaryFileExtension()", "static get productFullName()",
            "static alert(message, title)",
        ],
        "dialog.js": [
            "module.exports.Dialog = Dialog", "module.exports.DialogResult = DialogResult",
            "static create(text)", "addColumn()", "addGroup(",
            "addStaticText(label, text)", "addComboBox(label, items, initialItem)",
            "addCheckBox(label, initialValue)", "runModal()", "get selectedIndex()",
            "get value()",
        ],
        "document.js": [
            "module.exports.Document = Document", "static get all()", "static load(path, options = null)",
            "get path()", "get isOpen()", "get isDirty()", "get rootNode()", "get dpi()", "get layers()",
            "isSameObject(otherDocument)", "enumerateFontNames(callback)", "saveAs(path)", "close()",
            "export(path, exportOptions, exportArea, size)", "saveAsPackage(path, policy)",
            "module.exports.PackageResourcesPolicy = PackageResourcesPolicy",
            "static get allPresetNames()", "static createWithPresetName(presetName)",
            "static createForPages(strPages)", "get count()", "enumerate(callback)",
            "get isSuccess()", "get hasWarnings()", "get warningMessage()", "get errorMessage()",
        ],
        "fs.js": [
            "module.exports.File = File", "module.exports.fs = FileSystemApi",
            "static create(path, mode)", "get isOpen()", "write(buffer, length)", "flush()", "close()",
        ],
        "buffer.js": ["module.exports.Buffer = Buffer", "static utf8(", "get length()"],
        "nodes.js": [
            "get pageCount()", "get spreadCount()", "get children()", "get isSpreadNode()",
            "getSpreadExtents(options)", "get isGroupNode()", "get isTextNode()",
            "get isImageNode()", "get isEmbeddedDocumentNode()", "get nodeId()",
            "get textFrameInterface()", "get imageResourceInterface()",
        ],
        "textframeinterface.js": ["get textFlow()"],
        "textflow.js": [
            "module.exports.TextFlowOverflowState = TextFlowOverflowState",
            "getOverflowInfo()", "get state()", "get position()",
        ],
        "imageresourceinterface.js": [
            "get imageFilePath()", "get imagePlacement()", "get originalSize()",
        ],
    }
    checked = []
    for relative, needles in references.items():
        path = SDK / relative
        text = path.read_text(encoding="utf-8")
        for needle in needles:
            if needle not in text:
                raise ValueError(f"Installed SDK reference missing: {relative}: {needle}")
        checked.append({"file": relative, "sha256": digest(path), "references": needles})
    types = (DEST / "qa/affinity.d.ts").read_text(encoding="utf-8")
    native_signatures = [
        "getFileSize(path: string, asBigInt: boolean): number",
        "exists(path: string): boolean", "isDirectory(path: string): boolean",
        "isRegularFile(path: string): boolean", "createDirectory(path: string): void",
        "saveAs(self: DocumentHandle, path: string): void",
        "saveAsPackage(self: DocumentHandle, path: string, policy: PackageResourcesPolicy): void",
    ]
    for signature in native_signatures:
        if signature not in types:
            raise ValueError("Official SDK declaration missing: " + signature)
    for enumeration, members in {
        "TextFlowOverflowState": ["Unknown", "None", "Safe", "Warn"],
        "PackageResourcesPolicy": ["IncludeImages"],
        "EnumerationResult": ["Continue"],
    }.items():
        match = re.search(r"export enum " + enumeration + r"\s*\{([^}]+)\}", types)
        if not match or any(not re.search(r"\b" + member + r"\b", match[1]) for member in members):
            raise ValueError("SDK enum declaration mismatch: " + enumeration)
    contract = {
        "status": "passed", "installedVersion": "3.3.3.5004",
        "officialDeclarationSource": "https://sdk.affinity.studio/latest/affinity.d.ts",
        "officialDeclarationSha256": digest(DEST / "qa/affinity.d.ts"),
        "localSdkFiles": checked, "nativeSignatures": native_signatures,
        "affinityExecuted": False, "desktopControlUsed": False,
        "limit": "Static SDK reference checks and mocked tests do not prove native import/rendering behavior.",
    }
    (DEST / "qa/sdk-contract.json").write_text(json.dumps(contract, indent=2) + "\n", encoding="utf-8")
    code = (DEST / "convert-all-25-guides.js").read_text(encoding="utf-8")
    page = """<!doctype html>
<html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width">
<title>Run the 25-guide Affinity conversion</title>
<style>
body{font:17px/1.55 Arial,sans-serif;max-width:1000px;margin:42px auto;padding:0 24px;color:#173347}
h1{font-size:30px;line-height:1.2}h2{font-size:22px;margin-top:32px}
a{color:#075e91}button{background:#075e91;color:white;border:0;border-radius:5px;padding:12px 18px;font:inherit;cursor:pointer}
textarea{width:100%;box-sizing:border-box;margin-top:14px;padding:12px;height:310px;font:12px/1.4 Consolas,monospace;border:1px solid #9caeb9}
code{font-size:14px;overflow-wrap:anywhere}.note{background:#eef5f7;padding:16px;border-radius:6px}
li{margin:10px 0}#copied{margin-left:12px}
</style>
<h1>Run the 25-guide conversion in Affinity</h1>
<p>Prepared for Affinity 3.3.3.5004. The code passes offline checks; it has not been executed inside Affinity.</p>
<ol>
<li>Keep the prepared guides at <code>C:/dev/snipe-it-fork/output/manuals/final-guide-set-2026-11-04</code>.</li>
<li>Click <strong>Copy complete script</strong> below. In Affinity, switch to the <strong>Scripting</strong> studio and open its <strong>Script Editor</strong>.</li>
<li>Create a new script, replace its starter code with the copied code, and run it.</li>
<li>Choose <strong>All 25 guides</strong> and a PDF print preset. A one-guide or three-guide proof is also available in the same dialog.</li>
<li>The completion message gives the path to <strong>report.html</strong>. Open it for output links and any failures.</li>
</ol>
<p class="note">Each run writes into a new <code>conversion-runs</code> folder. It saves genuine native files, reopens them,
exports PDFs from those files, and optionally packages images. Outputs still need visual and editing review.</p>
<button id="copy" type="button">Copy complete script</button><span id="copied" role="status"></span>
<p><a href="convert-all-25-guides.js">Script file</a> &middot; <a href="README.md">Full instructions and failure handling</a>
&middot; <a href="qa/offline-tests.json">Offline checks</a> &middot; <a href="qa/sdk-contract.json">SDK reference checks</a></p>
<details><summary>Show script / select and copy manually</summary><textarea id="code" readonly spellcheck="false">"""
    page += html.escape(code, quote=False)
    page += """</textarea></details>
<script>
document.getElementById('copy').addEventListener('click', async () => {
    const code = document.getElementById('code');
    const state = document.getElementById('copied');
    try {
        if (!navigator.clipboard) throw new Error('Clipboard API unavailable');
        await navigator.clipboard.writeText(code.value);
        state.textContent = 'Copied. Paste into Affinity.';
    } catch (error) {
        code.parentElement.open = true;
        code.focus(); code.select();
        state.textContent = 'Code selected. Press Ctrl+C to copy.';
    }
});
</script></html>
"""
    (DEST / "RUN-ME.html").write_text(page, encoding="utf-8", newline="\n")
    entries = [
        "convert-all-25-guides.js", "input-manifest.json", "README.md", "RUN-ME.html",
        "qa/offline-tests.json", "qa/sdk-contract.json",
    ]
    checksums = "".join(f"{digest(DEST / relative)}  {relative}\n" for relative in entries)
    (DEST / "script-package.sha256").write_text(checksums, encoding="utf-8", newline="\n")
    archive_path = DEST.parent / "affinity-conversion-script-2026-11-05.zip"
    with zipfile.ZipFile(archive_path, "w", compression=zipfile.ZIP_DEFLATED) as archive:
        for relative in entries + ["script-package.sha256"]:
            archive.write(DEST / relative, relative)
    with zipfile.ZipFile(archive_path) as archive:
        if archive.testzip() is not None:
            raise ValueError("Script archive CRC verification failed.")
    (archive_path.with_suffix(".zip.sha256")).write_text(
        digest(archive_path) + "  " + archive_path.name + "\n", encoding="utf-8", newline="\n")
    print(json.dumps({"status": "prepared", "archive": str(archive_path),
                      "bytes": archive_path.stat().st_size, "scriptSha256": digest(DEST / entries[0]),
                      "checks": tests["checksRun"], "sdkFilesChecked": len(checked),
                      "affinityExecuted": False, "desktopControlUsed": False}))


if __name__ == "__main__":
    main()
