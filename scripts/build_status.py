#!/usr/bin/env python3
"""Build status.json for the ivancovs-status WP plugin from PLAN.md + the GitHub API.

Usage: python3 scripts/build_status.py [output_path]
Env:   GITHUB_REPOSITORY (default stevemaks999-web/ivancovs-site), GITHUB_TOKEN (optional locally), GITHUB_SHA, GITHUB_REF_NAME
"""
import json, os, re, subprocess, sys, time, urllib.request
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


def main():
    pulls = []
    for pr in api(f"repos/{REPO}/pulls?state=all&per_page=30"):
        state = "merged" if pr.get("merged_at") else ("draft" if pr.get("draft") else pr["state"])
        pulls.append({"number": pr["number"], "title": pr["title"], "url": pr["html_url"], "state": state,
                      "checks": checks_state(pr["head"]["sha"])})
    issues = [{"number": i["number"], "title": i["title"], "url": i["html_url"], "state": i["state"]}
              for i in api(f"repos/{REPO}/issues?state=all&per_page=50") if "pull_request" not in i]
    data = {"generated_at": time.strftime("%Y-%m-%d %H:%M UTC", time.gmtime()),
            "commit": os.environ.get("GITHUB_SHA", ""), "branch": os.environ.get("GITHUB_REF_NAME", ""),
            "phases": phases(), "pulls": pulls, "issues": issues}
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=1), encoding="utf-8")
    print(f"wrote {OUT} phases={len(data['phases'])} pulls={len(pulls)} issues={len(issues)}")


if __name__ == "__main__":
    main()
