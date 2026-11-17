"""Build an owner-run Affinity script; never launch or control Affinity."""

from __future__ import annotations

import hashlib
import argparse
import json
from pathlib import Path
import xml.etree.ElementTree as ET
import zipfile


ROOT = Path(__file__).resolve().parents[2]
PREPARED = ROOT / "output/manuals/final-guide-set-2026-11-04"
DEST = ROOT / "output/manuals/affinity-conversion-2026-11-05"
TEMPLATE = ROOT / "scripts/manuals/convert-guides-in-affinity.template.js"


def read_json(path: Path):
    return json.loads(path.read_text(encoding="utf-8-sig"))


def sha(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument('--idml-directory', type=Path, default=PREPARED)
    parser.add_argument('--destination', type=Path, default=DEST)
    args = parser.parse_args()
    idml_root = args.idml_directory.resolve()
    destination = args.destination.resolve()
    if destination.exists():
        raise FileExistsError('Existing script package preserved; choose a fresh destination: ' + str(destination))
    destination.mkdir(parents=True)
    guides = read_json(PREPARED / "source/inventory.json")["guides"]
    checksums = {}
    for line in (PREPARED / "preparation.sha256").read_text(encoding="utf-8").splitlines():
        digest, relative = line.split("  ", 1)
        checksums[relative] = digest
    qa = {item["code"]: item for item in read_json(PREPARED / "qa/idml-preparation.json")}
    inputs = {
        "inputSet": "final-guide-set-2026-11-04; 25 proposed guides; 68 pages",
        "root": PREPARED.as_posix(),
        "idmlRoot": idml_root.as_posix(),
        "guides": [],
        "assets": [],
    }
    references = set()
    for guide in guides:
        relative = f'idml/{guide["code"]}-v{guide["version"]}.idml'
        path = idml_root / relative
        digest = sha(path)
        if sha(PREPARED / relative) != checksums[relative]:
            raise ValueError("Prepared IDML checksum mismatch: " + relative)
        with zipfile.ZipFile(path) as archive:
            body_frames = 0
            image_count = 0
            pages = 0
            for name in archive.namelist():
                if not name.endswith(".xml"):
                    continue
                xml = ET.fromstring(archive.read(name))
                for link in xml.iter("Link"):
                    uri = link.attrib["LinkResourceURI"]
                    prefix = "file:/" + PREPARED.as_posix() + "/"
                    if not uri.startswith(prefix):
                        raise ValueError("Unexpected linked resource: " + uri)
                    references.add(uri.removeprefix(prefix))
                if name.startswith("Spreads/"):
                    body_frames += sum(1 for _ in xml.iter("TextFrame"))
                    image_count += sum(1 for _ in xml.iter("Image"))
                    pages += sum(1 for _ in xml.iter("Page"))
            if pages != guide["pages"] or image_count != qa[guide["code"]]["pictureFrames"]:
                raise ValueError("IDML inventory mismatch: " + relative)
        inputs["guides"].append({
            "code": guide["code"], "version": guide["version"], "pages": guide["pages"],
            "idml": relative, "bytes": path.stat().st_size, "sha256": digest,
            "bodyTextFrames": body_frames, "pictures": image_count,
        })
    for asset in sorted(read_json(PREPARED / "source/assets.json").values(), key=lambda item: item["file"]):
        path = PREPARED / asset["file"]
        digest = sha(path)
        if digest != asset["sha256"] or digest != checksums[asset["file"]]:
            raise ValueError("Original image checksum mismatch: " + asset["file"])
        inputs["assets"].append({"file": asset["file"], "bytes": path.stat().st_size, "sha256": digest})
    if len(inputs["guides"]) != 25 or sum(item["pages"] for item in inputs["guides"]) != 68:
        raise ValueError("Expected exactly 25 guides / 68 pages.")
    if len(inputs["assets"]) != 82 or not references <= {item["file"] for item in inputs["assets"]}:
        raise ValueError("Image inventory does not match IDML resource references.")
    literal = "const INPUTS = " + json.dumps(inputs, ensure_ascii=True, indent=2) + ";"
    template = TEMPLATE.read_text(encoding="utf-8")
    start = template.index("/* INPUTS_START */") + len("/* INPUTS_START */")
    end = template.index("/* INPUTS_END */")
    script = template[:start] + "\n" + literal + "\n" + template[end:]
    (destination / "convert-all-25-guides.js").write_text(script, encoding="utf-8", newline="\n")
    (destination / "input-manifest.json").write_text(
        json.dumps(inputs, ensure_ascii=True, indent=2) + "\n", encoding="utf-8", newline="\n")
    print(json.dumps({"script": str(destination / "convert-all-25-guides.js"), "guides": len(guides),
                      "pages": sum(item["pages"] for item in guides), "assets": len(inputs["assets"]),
                      "referencedAssets": len(references),
                      "inputHashesVerified": True, "affinityExecuted": False}))


if __name__ == "__main__":
    main()
