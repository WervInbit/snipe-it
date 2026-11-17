"""Qualify this exact repack without converting a known render delta into a general tolerance."""
import argparse
import hashlib
import json
from pathlib import Path


def digest(path):
    with path.open('rb') as stream:
        return hashlib.file_digest(stream,'sha256').hexdigest()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--comparison',required=True,type=Path)
    parser.add_argument('--output',required=True,type=Path)
    args = parser.parse_args()
    assert not args.output.exists(), 'Existing qualification is preserved'
    report = json.loads(args.comparison.read_text(encoding='utf-8'))
    assert report['manuals']==25 and report['pages']==68 and report['embedded_image_placements']==170
    differences=[]
    for manual in report['results']:
        assert digest(Path(manual['native']))==manual['native_sha256']
        assert digest(Path(manual['pdf']))==manual['pdf_sha256']
        assert manual['full_image_pixels_equal'] and manual['source_text_equal']
        differences.extend((manual['code'],manual['version'],p) for p in manual['rendered_pages'] if not p['equal'])
    assert len(differences)==1
    code,version,page=differences[0]
    assert (code,version,page['page'])==('AC-01',13,1)
    assert page['pixel_sha256']=='d6f3a5ba1f00aaa323852e3368da32a80d744f63670ec2bddfe4f72b4e0c0696'
    assert page['new_pixel_sha256']=='f08bb3fd5e249db2eb6e139c035d458042c7cec392a0590720d5546b8096ff21'
    assert page['changed_bounds']==[118,661,366,1071] and page['max_channel_change']==4
    report.pop('passed',None)
    report.update({'qualified':True,'pixel_identical':False,'exact_pages':67,
                   'published_pdf_policy':'Preserve all 25 original delivered print PDF bytes and hashes',
                   'qualification':'Exact pinned comparison: only the reviewed AC-01 phone screenshot has a <=4/255 colour delta. Image pixels, all text and crop transforms are preserved; every other page is exact. This is not owner acceptance or a general error tolerance.',
                   'strict_comparison_sha256':digest(args.comparison)})
    args.output.write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8')
    print(json.dumps({k:report[k] for k in ['qualified','pixel_identical','manuals','pages','exact_pages']}))


if __name__=='__main__':
    main()
