#!/usr/bin/env python3
"""Один файл для WooCommerce-импорта с текстами RU и EN в полях товара (без платных плагинов перевода).

Товар один (LV), тексты на других языках лежат в мета-полях _ivs_short_ru / _ivs_desc_ru / _ivs_short_en / _ivs_desc_en.
Тема (inc/woocommerce.php) показывает нужный язык. Наличие и брони остаются общими.
Импорт: Товары → Импорт, «Обновить существующие товары», сопоставление по артикулу.

Usage: python3 scripts/build_catalog_i18n.py   (после build_catalog_ru.py и build_catalog_en.py)
"""
import csv
from pathlib import Path

D = Path(__file__).resolve().parent.parent / "data"


def rows(name):
    return list(csv.DictReader(open(D / name, encoding="utf-8")))


out = []
for lv_f, ru_f, en_f in (("woocommerce-import-lv.csv", "woocommerce-import-ru.csv", "woocommerce-import-en.csv"),
                         ("woocommerce-import-kits-lv.csv", "woocommerce-import-kits-ru.csv", "woocommerce-import-kits-en.csv")):
    for lv, ru, en in zip(rows(lv_f), rows(ru_f), rows(en_f)):
        assert lv["SKU"] == ru["SKU"] == en["SKU"] and lv["Name"] == ru["Name"] == en["Name"]
        out.append({
            "SKU": lv["SKU"], "Type": "simple", "Name": lv["Name"],
            "Meta: _ivs_short_ru": ru["Short description"], "Meta: _ivs_desc_ru": ru["Description"],
            "Meta: _ivs_short_en": en["Short description"], "Meta: _ivs_desc_en": en["Description"],
        })
with open(D / "woocommerce-import-i18n.csv", "w", encoding="utf-8", newline="") as f:
    w = csv.DictWriter(f, fieldnames=list(out[0].keys()))
    w.writeheader()
    w.writerows(out)
print(len(out), "строк")
