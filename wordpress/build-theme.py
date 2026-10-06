from pathlib import Path
import re, zipfile
root = Path(__file__).resolve().parent.parent
theme = root / 'wordpress' / 'planodom'
source = (root / 'index.html').read_text(encoding='utf-8')
assets = set(re.findall(r'''assets/([^\s"'<>]+)''', source))
output = root / 'wordpress' / 'planodom-wordpress-theme.zip'
with zipfile.ZipFile(output, 'w', zipfile.ZIP_DEFLATED) as z:
    for f in theme.iterdir():
        if f.is_file(): z.write(f, 'planodom/' + f.name)
    for name in sorted(assets):
        f = root / 'assets' / name
        if not f.is_file(): raise FileNotFoundError(f)
        z.write(f, 'planodom/assets/' + name)
print(output)
