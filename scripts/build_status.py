#!/usr/bin/env python3
"""Build status.json for the ivancovs-status WP plugin from PLAN.md + the GitHub API.

Usage: python3 scripts/build_status.py [output_path]
Env:   GITHUB_REPOSITORY (default stevemaks999-web/ivancovs-site), GITHUB_TOKEN (optional locally), GITHUB_SHA, GITHUB_REF_NAME
"""
import json, os, re, subprocess, sys, time, urllib.parse, urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
REPO = os.environ.get("GITHUB_REPOSITORY", "stevemaks999-web/ivancovs-site")
OUT = Path(sys.argv[1]) if len(sys.argv) > 1 else ROOT / "src/wp-content/plugins/ivancovs-status/status.json"


def token():
    t = os.environ.get("GITHUB_TOKEN")
    if t:
        return t
    try:
        return subprocess.check_output(["gh", "auth", "token"], text=True, stderr=subprocess.DEVNULL).strip()
    except Exception:
        return None


def api(path):
    req = urllib.request.Request(f"https://api.github.com/{path}", headers={"Accept": "application/vnd.github+json"})
    t = token()
    if t:
        req.add_header("Authorization", f"Bearer {t}")
    with urllib.request.urlopen(req, timeout=30) as r:
        return json.load(r)


def phases():
    out, cur = [], None
    for line in (ROOT / "PLAN.md").read_text(encoding="utf-8").splitlines():
        if line.startswith("## Фаза"):
            cur = {"title": line[3:].strip(), "items": []}
            out.append(cur)
        elif line.startswith("## "):
            cur = None
        m = re.match(r"- \[([ xX])\] (.*)", line)
        if m and cur is not None:
            cur["items"].append({"done": m.group(1) != " ", "text": re.sub(r"[*`]", "", m.group(2))})
    for p in out:
        p["done"] = sum(i["done"] for i in p["items"])
        p["total"] = len(p["items"])
    for p in out:
        if p["total"] and p["done"] < p["total"]:
            p["current"] = True
            break
    return out


def checks_state(sha):
    runs = api(f"repos/{REPO}/commits/{sha}/check-runs").get("check_runs", [])
    if not runs:
        return "none"
    if any(r["status"] != "completed" for r in runs):
        return "pending"
    return "success" if all(r["conclusion"] in ("success", "skipped", "neutral") for r in runs) else "failure"


def metrics():
    """Цифры за 28 дней из Search Console и GA4; без ключа или при сбое возвращает None."""
    try:
        sys.path.insert(0, str(ROOT / "scripts"))
        import google_api_check as g
        tok = g.get_token(g.load_key())
        out = {"period_days": 28}
        st, res = g.call(tok, "https://www.googleapis.com/webmasters/v3/sites/" + urllib.parse.quote(g.SC_SITE, safe="") + "/searchAnalytics/query",
                         {"startDate": time.strftime("%Y-%m-%d", time.gmtime(time.time() - 28 * 86400)),
                          "endDate": time.strftime("%Y-%m-%d", time.gmtime()), "dimensions": []})
        if st == 200:
            r = (res.get("rows") or [{}])[0]
            out["clicks"], out["impressions"] = int(r.get("clicks", 0)), int(r.get("impressions", 0))
        base = f"https://analyticsdata.googleapis.com/v1beta/properties/{g.GA4_PROPERTY}:runReport"
        st, res = g.call(tok, base, {"dateRanges": [{"startDate": "28daysAgo", "endDate": "today"}],
                                     "metrics": [{"name": "activeUsers"}, {"name": "sessions"}]})
        if st == 200:
            rows = res.get("rows")
            v = [int(x["value"]) for x in rows[0]["metricValues"]] if rows else [0, 0]
            out["users"], out["sessions"] = v
        st, res = g.call(tok, base, {"dateRanges": [{"startDate": "28daysAgo", "endDate": "today"}],
                                     "dimensions": [{"name": "eventName"}], "metrics": [{"name": "eventCount"}],
                                     "dimensionFilter": {"filter": {"fieldName": "eventName", "inListFilter": {"values": [
                                         "view_item", "add_to_cart", "purchase", "click_call", "click_whatsapp"]}}}})
        if st == 200:
            out["events"] = {r["dimensionValues"][0]["value"]: int(r["metricValues"][0]["value"]) for r in res.get("rows", [])}
        return out if len(out) > 1 else None
    except Exception as e:
        print("metrics skipped:", type(e).__name__)
        return None


def main():
    pulls = []
    for pr in api(f"repos/{REPO}/pulls?state=all&per_page=30"):
        state = "merged" if pr.get("merged_at") else ("draft" if pr.get("draft") else pr["state"])
        pulls.append({"number": pr["number"], "title": pr["title"], "url": pr["html_url"], "state": state,
                      "checks": checks_state(pr["head"]["sha"])})
    issues = [{"number": i["number"], "title": i["title"], "url": i["html_url"], "state": i["state"]}
              for i in api(f"repos/{REPO}/issues?state=all&per_page=50") if "pull_request" not in i]
    branch = os.environ.get("STATUS_BRANCH", "staging")
    commits = [{"sha": c["sha"][:7], "message": c["commit"]["message"].splitlines()[0],
                "author": (c.get("author") or {}).get("login") or c["commit"]["author"]["name"],
                "date": c["commit"]["author"]["date"], "url": c["html_url"]}
               for c in api(f"repos/{REPO}/commits?sha={branch}&per_page=15")]
    runs = [{"name": r["name"], "title": r["display_title"], "branch": r["head_branch"], "status": r["status"],
             "conclusion": r["conclusion"], "event": r["event"], "date": r["created_at"], "url": r["html_url"]}
            for r in api(f"repos/{REPO}/actions/runs?per_page=12").get("workflow_runs", [])]
    data = {"generated_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
            "commit": os.environ.get("GITHUB_SHA", ""), "branch": branch,
            "metrics": metrics(), "phases": phases(), "pulls": pulls, "issues": issues, "commits": commits, "runs": runs}
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=1), encoding="utf-8")
    print(f"wrote {OUT} phases={len(data['phases'])} pulls={len(pulls)} issues={len(issues)}")


if __name__ == "__main__":
    main()
