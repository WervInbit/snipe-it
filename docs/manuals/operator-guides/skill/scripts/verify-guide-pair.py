"""Check an exact IDML/PDF pair; rendering and usability review remain distinct."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import sys
import unicodedata
import xml.etree.ElementTree as ET
import zipfile

from pypdf import PdfReader


def normalize(value):
    return re.sub(r"\s+", "", unicodedata.normalize("NFKC", value)).replace("\u00ad", "")


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def inspect_pair(idml, pdf, tolerance_mm=0.8):
    parts = []
    with zipfile.ZipFile(idml) as archive:
        spread_names = [n for n in archive.namelist() if n.startswith("Spreads/") and n.endswith(".xml")]
        page_count = sum(sum(1 for n in ET.fromstring(archive.read(name)).iter() if n.tag.split("}")[-1] == "Page")
                         for name in spread_names)
        for name in archive.namelist():
            if name.startswith("Stories/") and name.endswith(".xml"):
                for node in ET.fromstring(archive.read(name)).iter():
                    if node.tag.split("}")[-1] == "Content" and node.text:
                        parts.append({"story": name, "text": node.text})
    reader = PdfReader(pdf)
    pdf_text = normalize("\n".join(page.extract_text() or "" for page in reader.pages))
    checked = [part for part in parts if normalize(part["text"])]
    missing = [part for part in checked if normalize(part["text"]) not in pdf_text]
    dimensions = [[float(p.mediabox.width) * 25.4 / 72, float(p.mediabox.height) * 25.4 / 72]
                  for p in reader.pages]
    checks = {
        "source_has_pages_and_text": bool(page_count and checked),
        "page_count_matches": len(reader.pages) == page_count,
        "a4_portrait": bool(dimensions) and all(abs(w - 210) <= tolerance_mm and abs(h - 297) <= tolerance_mm
                                                 for w, h in dimensions),
        "no_optional_layers": "/OCProperties" not in reader.trailer["/Root"],
        "all_source_fragments_found": not missing,
    }
    return {
        "idml": str(idml.resolve()), "pdf": str(pdf.resolve()),
        "idml_sha256": digest(idml), "pdf_sha256": digest(pdf),
        "source_pages": page_count, "pdf_pages": len(reader.pages),
        "dimensions_mm": dimensions, "a4_tolerance_mm": tolerance_mm,
        "source_fragments": len(checked), "missing_fragments": missing,
        "checks": checks, "passed": all(checks.values()),
        "limits": "Text extraction flags need investigation. Presence does not establish placement, legibility, usability, native editing or acceptance.",
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--idml", required=True, type=Path)
    parser.add_argument("--pdf", required=True, type=Path)
    parser.add_argument("--report", required=True, type=Path)
    parser.add_argument("--render-dir", type=Path, help="Optional fresh directory for PDFium PNGs.")
    parser.add_argument("--dpi", type=int, default=150)
    args = parser.parse_args()
    if not 72 <= args.dpi <= 600:
        parser.error("--dpi must be between 72 and 600")
    for path in (args.idml, args.pdf):
        if not path.is_file():
            parser.error(f"Input does not exist: {path}")
    if args.report.exists():
        parser.error("Use a fresh report path; existing evidence is preserved")
    if args.render_dir and args.render_dir.exists() and any(args.render_dir.iterdir()):
        parser.error("Use an empty/fresh render directory")
    result = inspect_pair(args.idml, args.pdf)
    if args.render_dir:
        import pypdfium2 as pdfium
        args.render_dir.mkdir(parents=True, exist_ok=True)
        document = pdfium.PdfDocument(str(args.pdf))
        try:
            for number in range(len(document)):
                page = document[number]
                try:
                    page.render(scale=args.dpi / 72).to_pil().save(args.render_dir / f"page-{number + 1}.png")
                finally:
                    page.close()
        finally:
            document.close()
        result["renders"] = {"directory": str(args.render_dir.resolve()), "engine": "PDFium", "dpi": args.dpi}
    args.report.parent.mkdir(parents=True, exist_ok=True)
    args.report.write_text(json.dumps(result, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    print(json.dumps({"passed": result["passed"], "pages": result["pdf_pages"],
                      "missing_fragments": len(result["missing_fragments"]), "report": str(args.report)}))
    return 0 if result["passed"] else 1


if __name__ == "__main__":
    sys.exit(main())
