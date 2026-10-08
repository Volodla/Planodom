from pathlib import Path
import io, json, re, zipfile
from concurrent.futures import ThreadPoolExecutor
from urllib.request import urlopen
root = Path(__file__).resolve().parent.parent
theme = root / 'wordpress' / 'planodom'
manifest = json.loads((root / 'wordpress' / 'source-assets.json').read_text(encoding='utf-8'))
missing = [(name, url) for name, url in manifest.items() if not (theme / 'assets' / name).is_file()]
if missing:
    from PIL import Image, ImageOps
    (theme / 'assets').mkdir(exist_ok=True)
    def download_asset(pair):
        name, url = pair
        with urlopen(url, timeout=45) as response:
            image = ImageOps.exif_transpose(Image.open(io.BytesIO(response.read())))
        image.thumbnail((2000, 2000))
        image.convert('RGB').save(theme / 'assets' / name, 'WEBP', quality=88)
    with ThreadPoolExecutor(max_workers=6) as executor:
        list(executor.map(download_asset, missing))
source = (root / 'index.html').read_text(encoding='utf-8')
assets = set(re.findall(r'''assets/([^\s"'<>]+)''', source))
for file in theme.glob('*.html'):
    assets.update(re.findall(r'''\{\{ASSET_URL\}\}/([^\s"'<>]+)''', file.read_text(encoding='utf-8')))
for item in json.loads((theme / 'content.json').read_text(encoding='utf-8')):
    assets.update(item.get('images', []))
    assets.update(re.findall(r'''\{\{ASSET_URL\}\}/([^\s"'<>]+)''', item['content']))
output = root / 'wordpress' / 'planodom-wordpress-theme.zip'
temporary = output.with_suffix('.zip.tmp')
with zipfile.ZipFile(temporary, 'w', zipfile.ZIP_DEFLATED) as z:
    for f in theme.rglob('*'):
        if f.is_file(): z.write(f, 'planodom/' + f.relative_to(theme).as_posix())
    for name in sorted(assets):
        if (theme / 'assets' / name).is_file(): continue
        f = root / 'assets' / name
        if not f.is_file(): raise FileNotFoundError(f)
        z.write(f, 'planodom/assets/' + name)
with zipfile.ZipFile(temporary) as z:
    bad = z.testzip()
    if bad: raise ValueError('Invalid ZIP entry: ' + bad)
temporary.replace(output)
print(output)
