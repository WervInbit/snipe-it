"""Seal this new delivery without accepting independently changed native/PDF files."""
import argparse
import hashlib
import json
from pathlib import Path
import zipfile


def digest(path):
    with path.open('rb') as stream:
        return hashlib.file_digest(stream,'sha256').hexdigest()


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root',required=True,type=Path)
    parser.add_argument('--zip',required=True,type=Path)
    parser.add_argument('--report',required=True,type=Path)
    args=parser.parse_args()
    root=args.root.resolve()
    assert not args.zip.exists() and not args.report.exists(),'Existing sealed output is preserved'
    data=json.loads((root/'_Beheer/handleidingen.json').read_text(encoding='utf-8'))
    assert len(data['manuals'])==25 and data['pages']==68
    for manual in data['manuals']:
        for key in ['native','pdf']:
            entry=manual[key]
            assert digest(root/entry['file'])==entry['sha256'],f'Independent edit preserved: {entry["file"]}'
    files=sorted(p for p in root.rglob('*') if p.is_file() and p.relative_to(root).as_posix()!='_Beheer/controle/bestanden.sha256')
    assert not any(p.suffix.lower() in ('.ttf','.otf','.woff','.woff2','.pyc') for p in files)
    checksum=root/'_Beheer/controle/bestanden.sha256'
    checksum.write_text('\n'.join(f'{digest(p)}  {p.relative_to(root).as_posix()}' for p in files)+'\n',encoding='utf-8')
    files.append(checksum)
    args.zip.parent.mkdir(parents=True,exist_ok=True)
    with zipfile.ZipFile(args.zip,'x',compression=zipfile.ZIP_DEFLATED,compresslevel=6) as archive:
        for file in files:
            archive.write(file,file.relative_to(root).as_posix())
    with zipfile.ZipFile(args.zip) as archive:
        assert archive.testzip() is None
        assert len(archive.namelist())==len(files)
        assert {name.split('/')[0] for name in archive.namelist()}=={'AGENTS.md','START HIER.html','PDF','Affinity','_Beheer'}
    value={'root':str(root),'manuals':25,'pages':68,'files':len(files),'archive':str(args.zip.resolve()),
           'bytes':args.zip.stat().st_size,'sha256':digest(args.zip),'zip_integrity':True,'extra_outer_folder':False}
    args.zip.with_suffix(args.zip.suffix+'.sha256').write_text(value['sha256']+'  '+args.zip.name+'\n',encoding='ascii')
    args.report.write_text(json.dumps(value,indent=2)+'\n',encoding='utf-8')
    print(json.dumps(value))


if __name__=='__main__':
    main()
