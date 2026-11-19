#!/usr/bin/env python3
"""Собирает таблицу для Google Ads Editor (черновик, все кампании на паузе) и проверяет лимиты текстов.

Usage: python3 scripts/build_google_ads.py
Результат: data/google-ads-editor.csv
Лимиты: заголовок 30 знаков без «!», описание 90, путь 15.
"""
import csv, sys
from pathlib import Path

OUT = Path(__file__).resolve().parent.parent / "data" / "google-ads-editor.csv"
SITE = "https://ivancovs.com"

# (ключ группы, LV-название, RU-название, цена от € в сутки, страница, LV-ключи, RU-ключи)
DEV = [
    ("termokamera", "Termokameras", "Тепловизора", 35, "/product/tooltop/",
     ["termokamera noma", "termokameras noma rīgā", "termovizors noma", "termovizora noma"],
     ["аренда тепловизора", "тепловизор аренда рига", "прокат тепловизора"]),
    ("endoskops", "Endoskopa", "Эндоскопа", 20, "/product/endoskops/",
     ["endoskops noma", "endoskopa noma", "endoskopa kamera noma"],
     ["аренда эндоскопа", "эндоскоп аренда рига", "прокат эндоскопа"]),
    ("stroboskops", "Stroboskopa", "Стробоскопа", 3, "/product/holdpeak/",
     ["stroboskops noma", "stroboskopa noma"],
     ["аренда стробоскопа", "стробоскоп аренда рига"]),
    ("co2", "CO₂ mērītāja", "Измерителя CO₂", 5, "/product-category/co%e2%82%82-un-gaisa-kvalitate/",
     ["co2 mērītājs noma", "co2 mērītāja noma", "gaisa kvalitātes mērītājs noma"],
     ["аренда измерителя co2", "аренда анализатора качества воздуха"]),
    ("manometrs", "Manometra", "Манометра", 3, "/product/manometer-gt5101/",
     ["manometrs noma", "spiediena mērītājs noma", "spiediena mērītāja noma"],
     ["манометр аренда", "аренда манометра рига"]),
    ("gazes", "Gāzes detektora", "Газоанализатора", 5, "/product/portable-4-gas-detector/",
     ["gāzes detektors noma", "4 gāzu detektors noma", "tvana gāzes detektors noma"],
     ["газоанализатор аренда", "аренда газоанализатора", "аренда 4 газового детектора"]),
    ("dumi", "Dūmu ģeneratora", "Дымогенератора", 7.5, "/product/nsf-150admx/",
     ["dūmu ģenerators noma", "dūmu mašīna noma"],
     ["генератор дыма аренда", "аренда дым машины"]),
    ("anemometrs", "Anemometra", "Анемометра", 5, "/product-category/gaisa-plusma-un-ventilacija/",
     ["anemometrs noma", "anemometra noma", "gaisa plūsmas mērītājs noma"],
     ["аренда анемометра", "анемометр аренда рига"]),
    ("biezums", "Krāsas biezuma mērītāja", "Толщиномера", 12.5, "/product/nk-200/",
     ["krāsas biezuma mērītājs noma", "biezuma mērītājs noma"],
     ["толщиномер аренда", "аренда толщиномера лкп"]),
    ("talmers", "Lāzera tālmēra", "Лазерного дальномера", 10, "/product/nohawk-np/",
     ["lāzera tālmērs noma", "lāzera tālmēra noma"],
     ["лазерный дальномер аренда", "аренда лазерного дальномера"]),
    ("mitrums", "Mitruma mērītāja", "Измерителя влажности", 3, "/product/holdpeak-hp-5000t/",
     ["mitruma mērītājs noma", "koksnes mitruma mērītājs noma"],
     ["аренда измерителя влажности", "измеритель влажности аренда"]),
]
GEN_LV = ["instrumentu noma", "instrumentu noma rīga", "mērinstrumentu noma", "mērinstrumentu noma rīgā", "noma rīgā", "mērinstrumentu īre", "testo noma"]
GEN_RU = ["аренда измерительных приборов", "аренда приборов рига", "аренда оборудования рига", "прокат оборудования рига", "аренда инструмента рига", "аренда testo"]

NEG_LV = ["pirkt", "cena", "pirkšana", "veikals", "lietots", "olx", "ss.lv", "aliexpress", "amazon", "ebay", "remonts", "instrukcija", "pdf",
          "kā lietot", "bezmaksas", "vakance", "darbs", "ramirent", "storent", "cramo", "kurss", "apmācība", "grāmata", "attēls", "wiki"]
NEG_RU = ["купить", "цена", "магазин", "б/у", "olx", "ss.lv", "aliexpress", "amazon", "ebay", "ремонт", "инструкция", "скачать", "pdf",
          "бесплатно", "вакансия", "работа", "своими руками", "ramirent", "storent", "cramo", "курс", "обучение", "книга", "фото", "википедия"]

COMMON_LV = ["Rezervē Un Maksā Online", "Izņemšana Rīgā 9:00–18:00", "Atgriezeniska Drošības Nauda", "Ātra Rezervācija Bez Zvaniem",
             "Profesionāli Mērinstrumenti", "46 Instrumenti, 20 Komplekti"]
COMMON_RU = ["Бронь И Оплата Онлайн", "Выдача В Риге 9:00–18:00", "Залог Возвращается", "Быстрая Бронь Без Звонков",
             "Профессиональные Приборы", "46 Приборов, 20 Комплектов"]
DESC_LV = ["Profesionāli mērinstrumenti nomai Rīgā. Izvēlies datumus, rezervē un maksā online.",
           "Testo, Fluke, termokameras, endoskopi. Izņemšana Rīgā, Lastādijas iela 32.",
           "Gatavi komplekti ventilācijai, apkurei un auto. Rezervē bez zvaniem!",
           "Atgriezeniska drošības nauda. Izsniegšana un atgriešana darba dienās 9:00–18:00."]
DESC_RU = ["Профессиональные приборы в аренду в Риге. Выберите даты, забронируйте и оплатите онлайн.",
           "Testo, Fluke, тепловизоры, эндоскопы. Выдача в Риге, Lastādijas iela 32.",
           "Готовые комплекты для вентиляции, отопления и авто. Бронь без звонков!",
           "Залог возвращается. Выдача и возврат в будни с 9:00 до 18:00."]

HEAD = ["Campaign", "Campaign Status", "Campaign Type", "Budget", "Budget type", "Bid Strategy Type", "Networks", "Languages", "Location",
        "Ad Group", "Ad Group Status", "Max CPC", "Keyword", "Criterion Type", "Final URL", "Path 1", "Path 2"]
HEAD += [f"Headline {i}" for i in range(1, 16)] + ["Headline 1 position"] + [f"Description {i}" for i in range(1, 5)]

rows, errs = [], []


def chk(s, lim, what):
    if len(s) > lim:
        errs.append(f"{what} {len(s)}>{lim}: {s}")
    if what.startswith("Headline") and "!" in s:
        errs.append(f"'!' in {s}")
    return s


def row(**kw):
    r = {h: "" for h in HEAD}
    r.update(kw)
    rows.append(r)


def campaign(name, budget, lang, loc="Riga, Latvia"):
    row(**{"Campaign": name, "Campaign Status": "Paused", "Campaign Type": "Search", "Budget": budget, "Budget type": "Daily",
           "Bid Strategy Type": "Maximize clicks", "Networks": "Google search", "Languages": lang, "Location": loc})


def negatives(name, words):
    for w in words:
        row(**{"Campaign": name, "Keyword": w, "Criterion Type": "Negative Phrase"})


def group(camp, name, cpc, kws, url, p1, p2, heads, descs):
    row(**{"Campaign": camp, "Ad Group": name, "Ad Group Status": "Enabled", "Max CPC": cpc})
    for k in kws:
        row(**{"Campaign": camp, "Ad Group": name, "Keyword": k, "Criterion Type": "Phrase"})
        row(**{"Campaign": camp, "Ad Group": name, "Keyword": k, "Criterion Type": "Exact"})
    r = {"Campaign": camp, "Ad Group": name, "Final URL": url, "Path 1": chk(p1, 15, "Path"), "Path 2": chk(p2, 15, "Path")}
    for i, h in enumerate(heads[:15], 1):
        r[f"Headline {i}"] = chk(h, 30, f"Headline {i}")
    r["Headline 1 position"] = "1"
    for i, d in enumerate(descs[:4], 1):
        r[f"Description {i}"] = chk(d, 90, f"Description {i}")
    row(**r)


def price(p):
    return str(p).rstrip("0").rstrip(".").replace(".", ",") if isinstance(p, float) else str(p)


# --- Кампания 1: LV общая аренда ---
c1 = "LV | Noma | Vispārīgi"
campaign(c1, 4, "Latvian"); negatives(c1, NEG_LV)
group(c1, "Instrumentu noma", 0.35, GEN_LV, f"{SITE}/noma/", "noma", "instrumenti",
      ["Instrumentu Noma Rīgā", "Mērinstrumentu Noma Rīgā", "Noma No 3 € Dienā"] + COMMON_LV, DESC_LV)

# --- Кампания 2: LV по приборам ---
c2 = "LV | Noma | Pa instrumentiem"
campaign(c2, 6, "Latvian"); negatives(c2, NEG_LV)
for key, lv, ru, p, url, kl, kr in DEV:
    h1 = chk(f"{lv} Noma Rīgā", 30, "Headline 1") if len(f"{lv} Noma Rīgā") <= 30 else f"{lv} Noma"
    group(c2, f"{lv} noma", 0.4, kl, SITE + url, "noma", key[:15],
          [h1, f"Noma No {price(p)} € Dienā", "Rezervē Online, Izņem Rīgā"] + COMMON_LV, DESC_LV)

RU_PATH = {"termokamera": "тепловизор", "endoskops": "эндоскоп", "stroboskops": "стробоскоп", "co2": "измеритель co2", "manometrs": "манометр",
           "gazes": "газоанализатор", "dumi": "дымогенератор", "anemometrs": "анемометр", "biezums": "толщиномер", "talmers": "дальномер",
           "mitrums": "влажность"}

# --- Кампания 3: RU ---
c3 = "RU | Аренда | Рига"
campaign(c3, 3, "Russian")
negatives(c3, NEG_RU)
group(c3, "Аренда приборов", 0.3, GEN_RU, f"{SITE}/ru/arenda/", "аренда", "приборы",
      ["Аренда Приборов В Риге", "Аренда Измерительных Приборов", "Аренда От 3 € В Сутки"] + COMMON_RU, DESC_RU)
for key, lv, ru, p, url, kl, kr in DEV:
    h1 = f"Аренда {ru}"
    if len(h1) > 30:
        h1 = h1[:30].rsplit(" ", 1)[0]
    group(c3, f"Аренда {ru.lower()}", 0.3, kr, f"{SITE}/ru{url}".replace("/ru/product-category/", "/ru/kategoriya/").replace("/ru/product/", "/ru/pribor/"), "аренда", RU_PATH[key][:15],
          [h1, f"Аренда От {price(p)} € В Сутки", "Бронь Онлайн, Выдача В Риге"] + COMMON_RU, DESC_RU)

if errs:
    print("\n".join(errs)); sys.exit(1)
with open(OUT, "w", encoding="utf-8", newline="") as f:
    w = csv.DictWriter(f, fieldnames=HEAD); w.writeheader(); w.writerows(rows)
print(f"{len(rows)} строк, групп: {sum(1 for r in rows if r['Ad Group Status'])}, ключей: {sum(1 for r in rows if r['Criterion Type'] in ('Phrase','Exact'))}")
