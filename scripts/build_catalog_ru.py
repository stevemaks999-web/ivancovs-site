#!/usr/bin/env python3
"""Собирает RU-файлы импорта WooCommerce из data/instruments.json и LV-файлов (цены, залоги, SKU берутся из LV).

Usage: python3 scripts/build_catalog_ru.py
Результат: data/woocommerce-import-ru.csv, data/woocommerce-import-kits-ru.csv
"""
import csv, html, json
from pathlib import Path

D = Path(__file__).resolve().parent.parent / "data"
CATS = {
    "Gaisa plūsma un ventilācija": "Поток воздуха и вентиляция",
    "Temperatūra un mitrums": "Температура и влажность",
    "Spiediens": "Давление",
    "CO₂ un gaisa kvalitāte": "CO₂ и качество воздуха",
    "Skaņa\\, gaisma\\, elektrība": "Звук\\, свет\\, электричество",
    "Biezums\\, vibrācija\\, ultraskaņa": "Толщина\\, вибрация\\, ультразвук",
    "Gāzes un dūmi": "Газы и дым",
    "Termokameras un endoskopi": "Тепловизоры и эндоскопы",
    "Komplekti > Ventilācija un gaiss": "Комплекты > Вентиляция и воздух",
    "Komplekti > Apkure\\, aukstums un siltums": "Комплекты > Отопление\\, холод и тепло",
    "Komplekti > Auto\\, grīdas\\, santehnika\\, drošība": "Комплекты > Авто\\, полы\\, сантехника\\, безопасность",
    "Komplekti > Sertifikācija\\, elektrība un noliktavas": "Комплекты > Аттестация\\, электрика и склады",
}
KIT_RU = {
    "KIT-01": "Сдача вентиляции в Būvvalde.", "KIT-02": "Плотность вентиляционных каналов.",
    "KIT-03": "Аудит сквозняков в офисах.", "KIT-04": "Экспресс-проверка тяги.",
    "KIT-05": "Теплонасосы и холод.", "KIT-06": "Опрессовка азотом.",
    "KIT-07": "Накладной теплосчётчик.", "KIT-08": "Балансировка отопления.",
    "KIT-09": "Утечки тепла и холода.", "KIT-10": "Герметичность каркасных домов.",
    "KIT-11": "Проверка авто перед покупкой.", "KIT-12": "Мотор и турбина.",
    "KIT-13": "Аттестация рабочих мест.", "KIT-14": "Колодцы и резервуары.",
    "KIT-15": "Паркет, ламинат, эпоксид.", "KIT-16": "Чистые комнаты, HEPA.",
    "KIT-17": "Сантехнический сыщик.", "KIT-18": "Вибромониторинг.",
    "KIT-19": "Электроаудит щитовых.", "KIT-20": "Склады GDP.",
}
li = lambda xs: "<ul>" + "".join(f"<li>{html.escape(x)}</li>" for x in xs) + "</ul>"


def rows(name):
    return list(csv.DictReader(open(D / name, encoding="utf-8")))


def write(name, rs):
    with open(D / name, "w", encoding="utf-8", newline="") as f:
        w = csv.DictWriter(f, fieldnames=list(rs[0].keys()))
        w.writeheader()
        w.writerows(rs)


inst = json.load(open(D / "instruments.json", encoding="utf-8"))
by_sku = {f"IVS-{x['id']:02d}": x for x in inst}
out = []
for i, r in enumerate(rows("woocommerce-import-lv.csv"), 1):
    x = by_sku[f"IVS-{i:02d}"]  # в LV-файле у прибора 13 артикул «Fluke922», сопоставляем по порядку
    r = dict(r)
    r["Name"] = r["Name"]
    r["Short description"] = x["short_ru"]
    kit = f"<p><strong>Комплектация:</strong> {html.escape(x['kit_ru'])}.</p>" if x.get("kit_ru") else ""
    r["Description"] = f"<p>{html.escape(x['purpose_ru'])}</p>" + li(x["specs_ru"]) + kit
    r["Categories"] = CATS[r["Categories"]]
    out.append(r)
write("woocommerce-import-ru.csv", out)

short = {f"{x['name']}": x["short_ru"] for x in inst}
kout = []
for r in rows("woocommerce-import-kits-lv.csv"):
    r = dict(r)
    items = r["Meta: _ivs_kit_items"].split(", ")
    lis = []
    for it in items:
        n = it.split("× ", 1)[-1]
        s = next((v for k, v in short.items() if k.lower().startswith(n.lower()) or n.lower().startswith(k.lower())), "")
        lis.append(f"<strong>{html.escape(it)}</strong>" + (f": {html.escape(s)}" if s else ""))
    r["Short description"] = KIT_RU[r["SKU"]]
    r["Description"] = (f"<p>{KIT_RU[r['SKU']]}</p><h3>В комплекте</h3><ul>" + "".join(f"<li>{l}</li>" for l in lis) +
                       "</ul><p>Комплект сдаётся целиком. Если прибор ушёл в комплекте, отдельно его не забронировать.</p>")
    r["Categories"] = CATS[r["Categories"]]
    kout.append(r)
write("woocommerce-import-kits-ru.csv", kout)
print(len(out), "приборов,", len(kout), "комплектов")
