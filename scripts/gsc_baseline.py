#!/usr/bin/env python3
"""Снимок позиций и кликов из Search Console для сравнения до/после запуска.

Пишет baseline/gsc-baseline.json и baseline/gsc-baseline.md.
"""
import json
import sys
from datetime import date, timedelta
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from google_api_check import ROOT, SC_SITE, call, get_token, load_key  # noqa: E402

URL = "https://www.googleapis.com/webmasters/v3/sites/{}/searchAnalytics/query"
DAYS = 90
TOP = 50


def query(token, start, end, dims, limit):
    payload = {"startDate": start, "endDate": end, "dimensions": dims, "rowLimit": limit}
    status, res = call(token, URL.format(SC_SITE.replace(":", "%3A")), payload)
    if status != 200:
        sys.exit(f"Ошибка {status}: {res}")
    return res.get("rows", [])


def pack(rows, names):
    return [dict(zip(names, r["keys"]), clicks=r["clicks"], impressions=r["impressions"],
                 ctr=round(r["ctr"] * 100, 2), position=round(r["position"], 1)) for r in rows]


def main():
    token = get_token(load_key())
    end = date.today() - timedelta(days=3)  # свежие данные Search Console приходят с задержкой
    start = end - timedelta(days=DAYS - 1)
    s, e = start.isoformat(), end.isoformat()

    total = query(token, s, e, [], 1)
    totals = pack(total, [])[0] if total else {"clicks": 0, "impressions": 0, "ctr": 0, "position": 0}
    queries = pack(query(token, s, e, ["query"], TOP), ["query"])
    pages = pack(query(token, s, e, ["page"], TOP), ["page"])

    out = {"period": {"start": s, "end": e, "days": DAYS}, "site": SC_SITE,
           "totals": totals, "queries": queries, "pages": pages}
    d = ROOT / "baseline"
    d.mkdir(exist_ok=True)
    (d / "gsc-baseline.json").write_text(json.dumps(out, ensure_ascii=False, indent=2))

    md = [f"# Базовые показатели Search Console\n", f"Период: {s} — {e} ({DAYS} дней)\n",
          f"**Итого:** клики {totals['clicks']:.0f}, показы {totals['impressions']:.0f}, "
          f"CTR {totals['ctr']}%, средняя позиция {totals['position']}\n",
          "## Топ запросов\n", "| Запрос | Клики | Показы | CTR % | Позиция |", "|---|---|---|---|---|"]
    md += [f"| {q['query']} | {q['clicks']:.0f} | {q['impressions']:.0f} | {q['ctr']} | {q['position']} |" for q in queries]
    md += ["\n## Топ страниц\n", "| Страница | Клики | Показы | CTR % | Позиция |", "|---|---|---|---|---|"]
    md += [f"| {p['page']} | {p['clicks']:.0f} | {p['impressions']:.0f} | {p['ctr']} | {p['position']} |" for p in pages]
    (d / "gsc-baseline.md").write_text("\n".join(md) + "\n")
    print(json.dumps(out["totals"], ensure_ascii=False), len(queries), "запросов,", len(pages), "страниц")


if __name__ == "__main__":
    main()
