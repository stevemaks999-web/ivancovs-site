#!/usr/bin/env python3
"""Builds EN WooCommerce import files from data/instruments-en.json, data/instruments.json (short_en) and the LV files
(prices, deposits, SKUs come from LV).

Usage: python3 scripts/build_catalog_en.py
Output: data/woocommerce-import-en.csv, data/woocommerce-import-kits-en.csv
"""
import csv, html, json
from pathlib import Path

D = Path(__file__).resolve().parent.parent / "data"
CATS = {
    "Gaisa plūsma un ventilācija": "Airflow and ventilation",
    "Temperatūra un mitrums": "Temperature and humidity",
    "Spiediens": "Pressure",
    "CO₂ un gaisa kvalitāte": "CO₂ and air quality",
    "Skaņa\\, gaisma\\, elektrība": "Sound\\, light\\, electricity",
    "Biezums\\, vibrācija\\, ultraskaņa": "Thickness\\, vibration\\, ultrasound",
    "Gāzes un dūmi": "Gases and smoke",
    "Termokameras un endoskopi": "Thermal cameras and endoscopes",
    "Komplekti > Ventilācija un gaiss": "Kits > Ventilation and air",
    "Komplekti > Apkure\\, aukstums un siltums": "Kits > Heating\\, cooling and heat",
    "Komplekti > Auto\\, grīdas\\, santehnika\\, drošība": "Kits > Cars\\, floors\\, plumbing\\, safety",
    "Komplekti > Sertifikācija\\, elektrība un noliktavas": "Kits > Certification\\, electrical and warehouses",
}
KIT_EN = {
    "KIT-01": "Ventilation commissioning for the building authority (Būvvalde).", "KIT-02": "Air duct leakage testing.",
    "KIT-03": "Draught audit in offices.", "KIT-04": "Quick draught check.",
    "KIT-05": "Heat pumps and refrigeration.", "KIT-06": "Nitrogen pressure testing.",
    "KIT-07": "Clamp-on heat meter.", "KIT-08": "Heating system balancing.",
    "KIT-09": "Heat and cold leaks.", "KIT-10": "Airtightness of timber-frame houses.",
    "KIT-11": "Checking a car before purchase.", "KIT-12": "Engine and turbocharger.",
    "KIT-13": "Workplace assessment.", "KIT-14": "Wells and tanks.",
    "KIT-15": "Parquet, laminate, epoxy.", "KIT-16": "Clean rooms, HEPA.",
    "KIT-17": "Plumbing detective.", "KIT-18": "Vibration monitoring.",
    "KIT-19": "Switchboard electrical audit.", "KIT-20": "GDP warehouses.",
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
en = json.load(open(D / "instruments-en.json", encoding="utf-8"))
out = []
for i, r in enumerate(rows("woocommerce-import-lv.csv"), 1):  # у прибора 13 в LV артикул «Fluke922», сопоставляем по порядку
    x, e = inst[i - 1], en[str(i)]
    assert x["id"] == i
    r = dict(r)
    r["Short description"] = x["short_en"]
    kit = f"<p><strong>Included:</strong> {html.escape(e['kit'])}.</p>" if e["kit"] and not e["kit"].startswith("See ") else ""
    r["Description"] = f"<p>{html.escape(e['purpose'])}</p>" + li(e["specs"]) + kit
    r["Categories"] = CATS[r["Categories"]]
    out.append(r)
write("woocommerce-import-en.csv", out)

short = {x["name"]: x["short_en"] for x in inst}
kout = []
for r in rows("woocommerce-import-kits-lv.csv"):
    r = dict(r)
    lis = []
    for it in r["Meta: _ivs_kit_items"].split(", "):
        n = it.split("× ", 1)[-1]
        s = next((v for k, v in short.items() if k.lower().startswith(n.lower()) or n.lower().startswith(k.lower())), "")
        lis.append(f"<li><strong>{html.escape(it)}</strong>" + (f": {html.escape(s)}" if s else "") + "</li>")
    r["Short description"] = KIT_EN[r["SKU"]]
    r["Description"] = (f"<p>{KIT_EN[r['SKU']]}</p><h3>Included</h3><ul>" + "".join(lis) +
                       "</ul><p>The kit is rented as a whole. If an instrument is out in a kit, it cannot be booked separately.</p>")
    r["Categories"] = CATS[r["Categories"]]
    kout.append(r)
write("woocommerce-import-kits-en.csv", kout)
print(len(out), "instruments,", len(kout), "kits")
