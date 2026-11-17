"""Read-only integrity check for a copied Handleidingen directory (Python 3.10+)."""
import hashlib
from html.parser import HTMLParser
import json
from pathlib import Path
from urllib.parse import unquote, urlsplit


class LocalLinks(HTMLParser):
    def __init__(self):
        super().__init__()
        self.links = []

    def handle_starttag(self, tag, attrs):
        for name, value in attrs:
            if name in ('href', 'src') and value:
                self.links.append(value)


def digest(path):
    with path.open('rb') as stream:
        return hashlib.file_digest(stream, 'sha256').hexdigest() if hasattr(hashlib, 'file_digest') else hashlib.sha256(stream.read()).hexdigest()


def main():
    root = Path(__file__).resolve().parents[2]
    register = json.loads((root / '_Beheer/handleidingen.json').read_text(encoding='utf-8'))
    errors, checked = [], 0
    for line in (root / '_Beheer/controle/bestanden.sha256').read_text(encoding='utf-8').splitlines():
        expected, relative = line.split('  ', 1)
        path = (root / relative).resolve()
        if not path.is_relative_to(root):
            errors.append('Outside delivery: ' + relative)
        elif not path.is_file() or digest(path) != expected:
            errors.append('Missing or changed: ' + relative)
        checked += 1
    for manual in register['manuals']:
        for kind in ('pdf', 'native'):
            record = manual[kind]
            path = root / record['file']
            if not path.is_file() or digest(path) != record['sha256']:
                errors.append(manual['code'] + ': missing/changed ' + kind)
    parser = LocalLinks()
    parser.feed((root / 'START HIER.html').read_text(encoding='utf-8'))
    for link in parser.links:
        url = urlsplit(link)
        if not url.scheme and not url.netloc and url.path:
            target = (root / unquote(url.path)).resolve()
            if not target.is_relative_to(root) or not target.exists():
                errors.append('Broken index link: ' + link)
    result = {'passed': not errors, 'manuals': len(register['manuals']),
              'files_checked': checked, 'local_index_links': len(parser.links), 'errors': errors}
    print(json.dumps(result, ensure_ascii=True, indent=2))
    raise SystemExit(0 if not errors else 1)


if __name__ == '__main__':
    main()
