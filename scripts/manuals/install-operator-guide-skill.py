"""Install the maintained project skill into the personal Codex skill directory."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil


ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / "docs/manuals/operator-guides/skill"
NAME = "snipeit-operator-guides"


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--destination", type=Path, help="Skill parent directory; defaults to CODEX_HOME/skills.")
    args = parser.parse_args()
    parent = args.destination or Path(os.environ.get("CODEX_HOME", str(Path.home() / ".codex"))) / "skills"
    destination = parent / NAME
    manifest_path = destination / "installed-from.json"
    files = sorted(p for p in SOURCE.rglob("*") if p.is_file() and "__pycache__" not in p.parts)
    if not files or not (SOURCE / "SKILL.md").is_file():
        parser.error("The maintained skill source is incomplete")
    if destination.exists():
        if not manifest_path.is_file():
            parser.error("An unmanaged skill already exists at the destination; preserve it and choose another destination")
        old = json.loads(manifest_path.read_text(encoding="utf-8"))
        for relative, previous_hash in old["files"].items():
            installed = destination / relative
            if installed.is_file() and digest(installed) != previous_hash and digest(installed) != digest(SOURCE / relative):
                parser.error(f"Installed skill has an independent edit: {relative}. Reconcile it before installation")
    destination.mkdir(parents=True, exist_ok=True)
    hashes = {}
    for source in files:
        relative = source.relative_to(SOURCE)
        target = destination / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, target)
        hashes[relative.as_posix()] = digest(target)
    manifest = {"source": str(SOURCE), "files": hashes}
    manifest_path.write_text(json.dumps(manifest, indent=2) + "\n", encoding="utf-8")
    print(json.dumps({"installed": str(destination), "files": len(hashes), "source": str(SOURCE)}))


if __name__ == "__main__":
    main()
