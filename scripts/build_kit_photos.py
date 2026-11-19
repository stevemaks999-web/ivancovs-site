#!/usr/bin/env python3
"""Фото комплектов: на картинке все приборы комплекта, по одной карточке на прибор (4 прибора = 4 карточки).
Фото приборов берём из backup-photos-original (имена файлов в data/instruments.json), состав из data/woocommerce-import-kits-lv.csv.
Результат: data/kit-photos/KIT-XX.jpg, 1200x900 (как карточка каталога 4:3)."""
import csv, json, os, re, sys
from PIL import Image, ImageChops, ImageDraw, ImageFont

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PHOTOS = os.path.join(os.path.dirname(ROOT), 'backup-photos-original')
OUT = os.path.join(ROOT, 'data', 'kit-photos')
W, H = 1200, 900
BG, CARD, LINE = (244, 246, 250), (255, 255, 255), (214, 220, 230)
ACC = (15, 98, 190)
FONT = next((p for p in ('/System/Library/Fonts/Supplemental/Arial Bold.ttf', '/Library/Fonts/Arial Bold.ttf') if os.path.exists(p)), None)


def photo_file(url):
    base = os.path.basename(url)
    for f in os.listdir(PHOTOS):
        if f.endswith('__' + base) or f == base:
            return os.path.join(PHOTOS, f)
    raise SystemExit('нет файла для ' + url)


def trim(im):
    """Обрезаем поля цвета угла."""
    im = im.convert('RGB')
    bg = Image.new('RGB', im.size, im.getpixel((2, 2)))
    box = ImageChops.difference(im, bg).point(lambda v: 255 if v > 18 else 0).getbbox()
    return im.crop(box) if box else im


def layout(n):
    """Ячейки [x, y, w, h] под n карточек."""
    m, g = 40, 28
    if n == 1:
        return [(m, m, W - 2 * m, H - 2 * m)]
    if n == 2:
        w = (W - 2 * m - g) // 2
        return [(m + i * (w + g), 130, w, H - 260) for i in range(2)]
    if n == 3:
        w = (W - 2 * m - 2 * g) // 3
        return [(m + i * (w + g), 190, w, H - 380) for i in range(3)]
    if n == 4:
        w, h = (W - 2 * m - g) // 2, (H - 2 * m - g) // 2
        return [(m + (i % 2) * (w + g), m + (i // 2) * (h + g), w, h) for i in range(4)]
    w, h = (W - 2 * m - 2 * g) // 3, (H - 2 * m - g) // 2
    top = [(m + i * (w + g), m, w, h) for i in range(3)]
    rest = n - 3
    x0 = (W - (rest * w + (rest - 1) * g)) // 2
    return top + [(x0 + i * (w + g), m + h + g, w, h) for i in range(rest)]


def badge(d, x, y, text):
    f = ImageFont.truetype(FONT, 44) if FONT else ImageFont.load_default()
    tw = d.textlength(text, font=f)
    d.rounded_rectangle((x - tw - 32, y, x, y + 64), 32, fill=ACC)
    d.text((x - 16 - tw, y + 8), text, font=f, fill='white')


def build(sku, items, devices):
    canvas = Image.new('RGB', (W, H), BG)
    d = ImageDraw.Draw(canvas)
    cells = layout(len(items))
    for (name, qty), (x, y, w, h) in zip(items, cells):
        d.rounded_rectangle((x, y, x + w, y + h), 28, fill=CARD, outline=LINE, width=2)
        ph = trim(Image.open(photo_file(devices[name.lower()]['photo'])))
        pad = 34
        ph.thumbnail((w - 2 * pad, h - 2 * pad), Image.LANCZOS)
        canvas.paste(ph, (x + (w - ph.width) // 2, y + (h - ph.height) // 2))
        if qty > 1:
            badge(d, x + w - 18, y + 18, '×%d' % qty)
    canvas.save(os.path.join(OUT, sku + '.jpg'), quality=90, optimize=True)


def main():
    devices = {i['name'].lower(): i for i in json.load(open(os.path.join(ROOT, 'data', 'instruments.json')))}
    os.makedirs(OUT, exist_ok=True)
    for r in csv.DictReader(open(os.path.join(ROOT, 'data', 'woocommerce-import-kits-lv.csv'))):
        items = []
        for part in r['Meta: _ivs_kit_items'].split(','):
            m = re.match(r'^\s*(\d+)\s*[×x]\s*(.+?)\s*$', part)
            qty, name = (int(m.group(1)), m.group(2)) if m else (1, part.strip())
            items.append((name, qty))
        build(r['SKU'], items, devices)
        print(r['SKU'], len(items))
    # контактный лист для просмотра
    files = sorted(f for f in os.listdir(OUT) if f.startswith('KIT-'))
    sheet = Image.new('RGB', (5 * 400, 4 * 300), 'white')
    for i, f in enumerate(files):
        sheet.paste(Image.open(os.path.join(OUT, f)).resize((400, 300)), ((i % 5) * 400, (i // 5) * 300))
    sheet.save(os.path.join(os.environ.get('SHEET_DIR', OUT), 'sheet.jpg'), quality=85)


if __name__ == '__main__':
    main()
