#!/usr/bin/env python3
"""Deploy a folder to cPanel hosting over the cPanel API (no SSH/FTP needed).

Usage:  python3 deploy/deploy.py staging|production [--dry-run]

Credentials come from env vars (GitHub Secrets in CI, a local .env for direct runs):
  CPANEL_HOST   e.g. server hostname (no scheme)
  CPANEL_USER   cPanel username
  CPANEL_TOKEN  cPanel API token
"""
import json, os, sys, time, uuid, zipfile, tempfile
import urllib.request, urllib.parse
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent


def load_env():
    env_file = ROOT / ".env"
    if env_file.exists():
        for line in env_file.read_text().splitlines():
            line = line.strip()
            if line and not line.startswith("#") and "=" in line:
                k, v = line.split("=", 1)
                os.environ.setdefault(k.strip(), v.strip().strip('"').strip("'"))


def build_zip(source: Path, dest: Path) -> int:
    n = 0
    with zipfile.ZipFile(dest, "w", zipfile.ZIP_DEFLATED) as z:
        for p in sorted(source.rglob("*")):
            if p.is_file() and p.name != ".gitkeep" and ".git" not in p.parts:
                z.write(p, p.relative_to(source).as_posix())
                n += 1
    return n


class Cpanel:
    def __init__(self, host, user, token):
        self.base = f"https://{host}:2083"
        self.user = user
        self.auth = f"cpanel {user}:{token}"

    def _open(self, req):
        req.add_header("Authorization", self.auth)
        with urllib.request.urlopen(req, timeout=300) as r:
            return json.load(r)

    def upload(self, local: Path, remote_dir: str):
        boundary = uuid.uuid4().hex
        body = b""
        for name, val in (("dir", remote_dir), ("overwrite", "1")):
            body += (f"--{boundary}\r\nContent-Disposition: form-data; name=\"{name}\"\r\n\r\n{val}\r\n").encode()
        body += (f"--{boundary}\r\nContent-Disposition: form-data; name=\"file-1\"; "
                 f"filename=\"{local.name}\"\r\nContent-Type: application/zip\r\n\r\n").encode()
        body += local.read_bytes() + f"\r\n--{boundary}--\r\n".encode()
        req = urllib.request.Request(f"{self.base}/execute/Fileman/upload_files", data=body)
        req.add_header("Content-Type", f"multipart/form-data; boundary={boundary}")
        res = self._open(req)
        if res.get("status") != 1:
            sys.exit(f"upload failed: {res.get('errors')}")

    def fileop(self, op, source, dest=None):
        q = {"cpanel_jsonapi_user": self.user, "cpanel_jsonapi_apiversion": "2",
             "cpanel_jsonapi_module": "Fileman", "cpanel_jsonapi_func": "fileop",
             "op": op, "sourcefiles": source}
        if dest:
            q["destfiles"] = dest
        res = self._open(urllib.request.Request(f"{self.base}/json-api/cpanel?" + urllib.parse.urlencode(q)))
        data = (res.get("cpanelresult", {}).get("data") or [{}])[0]
        if res.get("cpanelresult", {}).get("error") or data.get("result") != 1:
            sys.exit(f"fileop {op} failed: {data.get('reason') or res}")


def main():
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    dry = "--dry-run" in sys.argv
    if len(args) != 1:
        sys.exit(__doc__)
    cfg = json.loads((ROOT / "deploy/targets.json").read_text()).get(args[0])
    if not cfg:
        sys.exit(f"unknown target {args[0]}")
    source = ROOT / cfg["source"]
    stamp = time.strftime("%Y%m%d-%H%M%S")
    zpath = Path(tempfile.gettempdir()) / f"deploy-{stamp}.zip"
    count = build_zip(source, zpath)
    print(f"target={args[0]} ({cfg['domain']}) files={count} zip={zpath.stat().st_size}B")
    if count == 0:
        print("nothing to deploy"); return
    if dry:
        print("dry-run: not uploading"); return
    load_env()
    try:
        c = Cpanel(os.environ["CPANEL_HOST"], os.environ["CPANEL_USER"], os.environ["CPANEL_TOKEN"])
    except KeyError as e:
        sys.exit(f"missing env var {e}")
    remote_zip = f"{cfg['docroot']}/{zpath.name}"
    c.upload(zpath, cfg["docroot"])
    c.fileop("extract", remote_zip, cfg["docroot"])
    c.fileop("unlink", remote_zip)
    zpath.unlink(missing_ok=True)
    print("deployed OK")


if __name__ == "__main__":
    main()
