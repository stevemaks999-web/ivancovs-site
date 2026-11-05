#!/usr/bin/env python3
"""Проверка доступа сервисного аккаунта к Google API (Search Console, GA4).

Ключ берётся из GOOGLE_SERVICE_ACCOUNT_JSON (содержимое) или из
.secrets/google-service-account.json. Секреты в вывод не попадают.
"""
import base64
import json
import os
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding

ROOT = Path(__file__).resolve().parent.parent
SC_SITE = "sc-domain:ivancovs.com"
GA4_PROPERTY = "366666242"
SCOPES = [
    "https://www.googleapis.com/auth/webmasters.readonly",
    "https://www.googleapis.com/auth/analytics.readonly",
]


def load_key():
    raw = os.environ.get("GOOGLE_SERVICE_ACCOUNT_JSON")
    if raw:
        return json.loads(raw)
    return json.loads((ROOT / ".secrets" / "google-service-account.json").read_text())


def b64(data):
    return base64.urlsafe_b64encode(data).rstrip(b"=")


def get_token(key):
    now = int(time.time())
    header = b64(json.dumps({"alg": "RS256", "typ": "JWT"}).encode())
    claims = b64(json.dumps({
        "iss": key["client_email"],
        "scope": " ".join(SCOPES),
        "aud": key["token_uri"],
        "iat": now,
        "exp": now + 3600,
    }).encode())
    signing_input = header + b"." + claims
    private_key = serialization.load_pem_private_key(key["private_key"].encode(), password=None)
    signature = private_key.sign(signing_input, padding.PKCS1v15(), hashes.SHA256())
    assertion = (signing_input + b"." + b64(signature)).decode()
    body = urllib.parse.urlencode({
        "grant_type": "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "assertion": assertion,
    }).encode()
    with urllib.request.urlopen(urllib.request.Request(key["token_uri"], data=body)) as r:
        return json.load(r)["access_token"]


def call(token, url, payload=None):
    data = json.dumps(payload).encode() if payload is not None else None
    req = urllib.request.Request(url, data=data, headers={
        "Authorization": f"Bearer {token}",
        "Content-Type": "application/json",
    })
    try:
        with urllib.request.urlopen(req) as r:
            return r.status, json.load(r)
    except urllib.error.HTTPError as e:
        try:
            msg = json.load(e).get("error", {}).get("message", "")
        except Exception:
            msg = ""
        return e.code, msg


def main():
    key = load_key()
    token = get_token(key)
    print(f"Токен получен для {key['client_email']}")
    ok = True

    status, res = call(token, "https://www.googleapis.com/webmasters/v3/sites")
    if status == 200:
        sites = [(s["siteUrl"], s["permissionLevel"]) for s in res.get("siteEntry", [])]
        print(f"Search Console: OK, ресурсы: {sites}")
    else:
        ok = False
        print(f"Search Console: ОШИБКА {status} {res}")

    status, res = call(
        token,
        f"https://analyticsdata.googleapis.com/v1beta/properties/{GA4_PROPERTY}:runReport",
        {"dateRanges": [{"startDate": "7daysAgo", "endDate": "today"}],
         "metrics": [{"name": "activeUsers"}, {"name": "sessions"}]},
    )
    if status == 200:
        rows = res.get("rows", [])
        values = [v["value"] for v in rows[0]["metricValues"]] if rows else ["0", "0"]
        print(f"GA4 Data API: OK, пользователей/сеансов за 7 дней: {values}")
    else:
        ok = False
        print(f"GA4 Data API: ОШИБКА {status} {res}")

    sys.exit(0 if ok else 1)


if __name__ == "__main__":
    main()
