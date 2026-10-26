#!/usr/bin/env python3
"""Restricted receiver for APM2 NAS VPN accounts on the backup VPS."""

import ipaddress
import json
import os
import re
import stat
import sys
import tempfile
from pathlib import Path


TARGET = Path("/etc/ppp/chap-secrets")
MARK_BEGIN = "# BEGIN APM2 MANAGED"
MARK_END = "# END APM2 MANAGED"
USERNAME = re.compile(r"^[A-Za-z0-9][A-Za-z0-9_.-]{2,63}$")
PASSWORD = re.compile(r"^[A-Za-z0-9!@#$%^&*+=._-]{8,128}$")


def fail(message: str) -> None:
    print(message, file=sys.stderr)
    raise SystemExit(1)


def normalize_accounts(value: object) -> list[dict[str, str]]:
    if not isinstance(value, list) or len(value) > 512:
        fail("Invalid account list.")

    result: list[dict[str, str]] = []
    ips: set[str] = set()
    users: set[str] = set()
    for row in value:
        if not isinstance(row, dict):
            fail("Invalid account row.")
        username = row.get("username")
        password = row.get("password")
        address = row.get("ip")
        if not isinstance(username, str) or not USERNAME.fullmatch(username):
            fail("Invalid account username.")
        if not isinstance(password, str) or not PASSWORD.fullmatch(password):
            fail("Invalid account password.")
        try:
            ip = ipaddress.IPv4Address(address)
        except ipaddress.AddressValueError:
            fail("Invalid account address.")
        if ip not in ipaddress.IPv4Network("10.9.0.0/24") or str(ip) == "10.9.0.1":
            fail("Account address is outside the VPN pool.")
        if username in users or str(ip) in ips:
            fail("Duplicate account data.")
        users.add(username)
        ips.add(str(ip))
        result.append({"username": username, "password": password, "ip": str(ip)})
    return result


def write_accounts(accounts: list[dict[str, str]]) -> None:
    try:
        existing = TARGET.read_text(encoding="utf-8")
        metadata = TARGET.stat()
    except OSError:
        fail("Backup VPN account store is unavailable.")

    lines = [MARK_BEGIN]
    lines.extend(f'"{item["username"]}" * "{item["password"]}" {item["ip"]}' for item in accounts)
    lines.append(MARK_END)
    block = "\n".join(lines)
    pattern = re.compile(re.escape(MARK_BEGIN) + r".*?" + re.escape(MARK_END), re.DOTALL)
    updated = pattern.sub(block, existing) if pattern.search(existing) else existing.rstrip() + "\n\n" + block + "\n"

    descriptor, temporary = tempfile.mkstemp(prefix=".apm2-vpn-", dir=str(TARGET.parent), text=True)
    try:
        with os.fdopen(descriptor, "w", encoding="utf-8") as output:
            output.write(updated)
            output.flush()
            os.fsync(output.fileno())
        os.chmod(temporary, stat.S_IMODE(metadata.st_mode))
        os.chown(temporary, metadata.st_uid, metadata.st_gid)
        os.replace(temporary, TARGET)
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def main() -> None:
    raw = sys.stdin.buffer.read(131073)
    if len(raw) > 131072:
        fail("Payload is too large.")
    try:
        payload = json.loads(raw.decode("utf-8"))
    except (UnicodeDecodeError, json.JSONDecodeError):
        fail("Invalid payload.")
    if not isinstance(payload, dict) or set(payload) != {"accounts"}:
        fail("Invalid payload shape.")
    write_accounts(normalize_accounts(payload["accounts"]))


if __name__ == "__main__":
    main()
