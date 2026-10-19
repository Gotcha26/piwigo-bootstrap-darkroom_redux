"""Check, report and update the third-party components shipped by the theme.

The components live under node_modules/<name>/ (only their runtime files are
versioned, see .gitignore) and are described by tools/vendor.json, the single
source of truth for versions, origins and kept files. Python standard library
only: no npm, no Yarn, no Node.

Usage (from the repository root):

    python tools/vendor.py check
    python tools/vendor.py credits [--apply]
"""

import argparse
import copy
import difflib
import fnmatch
import io
import json
import os
import re
import shutil
import subprocess
import sys
import tarfile
import urllib.request
from dataclasses import dataclass, field
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
ROOT = TOOLS.parent
MANIFEST = TOOLS / "vendor.json"

SOURCES = ("npm", "github", "manual", "frozen")
VERSION_RE = re.compile(r"^[0-9A-Za-z][0-9A-Za-z._+-]*$")


class ManifestError(Exception):
    pass


def load_manifest(path):
    """Read and validate the manifest; raise ManifestError when its shape is wrong."""
    try:
        data = json.loads(Path(path).read_text(encoding="utf-8"))
    except (OSError, ValueError) as error:
        raise ManifestError("cannot read %s: %s" % (path, error))
    components = data.get("components") if isinstance(data, dict) else None
    if not isinstance(components, dict):
        raise ManifestError("%s: missing 'components' object" % path)
    for name, entry in components.items():
        if entry.get("source") not in SOURCES:
            raise ManifestError("%s: unknown source %r" % (name, entry.get("source")))
        if not entry.get("version"):
            raise ManifestError("%s: missing version" % name)
        if entry["source"] != "frozen" and not entry.get("files"):
            raise ManifestError("%s: missing files" % name)
        if entry["source"] == "github" and not (entry.get("repo") and entry.get("ref")):
            raise ManifestError("%s: github source needs repo and ref" % name)
    return data


def tracked_files(root):
    """Files versioned under node_modules/, relative to root, with '/' separators."""
    output = subprocess.run(["git", "ls-files", "node_modules"], cwd=str(root),
                            capture_output=True, text=True, check=True).stdout
    return set(output.split("\n")) - {""}


def _is_manual(entry):
    return entry["source"] == "manual"


def _qualified(name, entry, pattern):
    return pattern if _is_manual(entry) else "node_modules/%s/%s" % (name, pattern)


def expected_files(manifest):
    """Every file or pattern the manifest declares, relative to the repository root."""
    expected = set()
    for name, entry in manifest["components"].items():
        for pattern in entry.get("files", []):
            expected.add(_qualified(name, entry, pattern))
    return expected


def check(manifest, root, tracked):
    """Return the list of inconsistencies between the manifest and the repository."""
    problems = []
    root = Path(root)
    covered = set()
    for name, entry in manifest["components"].items():
        for pattern in entry.get("files", []):
            qualified = _qualified(name, entry, pattern)
            if _is_manual(entry):
                matches = {qualified} if (root / qualified).is_file() else set()
            else:
                matches = {path for path in tracked if fnmatch.fnmatchcase(path, qualified)}
            if not matches:
                problems.append("%s: no file matches %s" % (name, qualified))
            covered |= matches
    for path in sorted(tracked - covered):
        problems.append("%s is versioned but not listed in the manifest" % path)
    return problems


CREDITS_FILE = Path("include") / "credits.php"
CREDIT_FIELDS = ("name", "url", "author", "license")
# The shared credits template prints these fields without escaping.
CREDIT_UNSAFE = re.compile(r"[<>&\"]")


def credit_entries(manifest):
    """Credits list for the admin modal: components (unless "credit": false), then
    credits_extra, in manifest order. A component name carries its version."""
    entries = []
    sources = [(name, entry) for name, entry in manifest["components"].items()
               if entry.get("credit", True)]
    sources += [(entry.get("name"), entry) for entry in manifest.get("credits_extra", [])]
    for key, entry in sources:
        values = {}
        for name in CREDIT_FIELDS:
            value = entry.get(name)
            if not isinstance(value, str) or not value:
                raise ManifestError("%s: credits need a non-empty %r" % (key, name))
            if CREDIT_UNSAFE.search(value):
                raise ManifestError("%s: %r must not contain <, >, & or \"" % (key, name))
            values[name] = value
        if entry.get("version"):
            values["name"] = "%s %s" % (values["name"], entry["version"])
        if entry.get("fork"):
            values["name"] += " (fork)"
        entries.append(values)
    return entries


def _php_string(value):
    return "'%s'" % value.replace("\\", "\\\\").replace("'", "\\'")


def render_credits(manifest):
    """Text of include/credits.php for the given manifest."""
    lines = [
        "<?php",
        "// Credits list of the admin modal: generated by tools/vendor.py credits, do not edit.",
        "// Source: tools/vendor.json (components, then credits_extra).",
        "defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');",
        "",
        "return array(",
    ]
    for entry in credit_entries(manifest):
        fields = ", ".join("%s => %s" % (_php_string(name), _php_string(entry[name])) for name in CREDIT_FIELDS)
        lines.append("  array(%s)," % fields)
    lines.append(");")
    return "\n".join(lines) + "\n"


def check_credits(manifest, root):
    """Problems with the generated credits file (missing or stale)."""
    path = Path(root) / CREDITS_FILE
    try:
        current = path.read_text(encoding="utf-8")
    except OSError:
        return ["%s is missing: run 'python tools/vendor.py credits --apply'" % CREDITS_FILE.as_posix()]
    if current != render_credits(manifest):
        return ["%s is stale: run 'python tools/vendor.py credits --apply'" % CREDITS_FILE.as_posix()]
    return []


class UpdateError(Exception):
    pass


@dataclass
class UpdatePlan:
    name: str
    version: str
    added: list
    modified: list
    removed: list
    staged: dict = field(repr=False)


def http_get(url):
    request = urllib.request.Request(url, headers={"User-Agent": "bootstrap-darkroom-vendor/1"})
    with urllib.request.urlopen(request, timeout=60) as response:
        return response.read()


def tarball_url(entry, name, version):
    if entry["source"] == "npm":
        return "https://registry.npmjs.org/%s/-/%s-%s.tgz" % (name, name.split("/")[-1], version)
    return "https://codeload.github.com/%s/tar.gz/%s" % (entry["repo"], version)


def _read_archive(data):
    """Return {relative path: bytes} for the regular files of a .tgz, minus its top folder.

    The whole archive is refused when any member is a link or tries to leave the
    extraction root: nothing is trusted from a downloaded file.
    """
    files = {}
    try:
        archive = tarfile.open(fileobj=io.BytesIO(data), mode="r:gz")
    except (tarfile.TarError, OSError, EOFError) as error:
        raise UpdateError("not a valid .tgz archive: %s" % error)
    with archive:
        for member in archive.getmembers():
            parts = member.name.replace("\\", "/").split("/")
            if member.name.startswith(("/", "\\")) or ".." in parts or any(":" in part for part in parts):
                raise UpdateError("unsafe path in archive: %s" % member.name)
            if member.issym() or member.islnk() or member.isdev():
                raise UpdateError("link or device in archive: %s" % member.name)
            if not member.isfile() or len(parts) < 2:
                continue
            files["/".join(parts[1:])] = archive.extractfile(member).read()
    return files


SOURCE_MAP_COMMENT = re.compile(rb"[ \t]*(?://#|/\*#)[ \t]*sourceMappingURL=[^\r\n]*?(?:\*/)?[ \t]*(?:\r?\n|$)")


def _without_source_map(path, content):
    """The theme ships no source maps: drop *.map files (done by the caller) and the
    sourceMappingURL comment of the scripts and stylesheets that point to them."""
    if path.endswith((".js", ".css")):
        return SOURCE_MAP_COMMENT.sub(b"", content)
    return content


def plan_update(name, version, manifest, root, fetch):
    """Download <name>@<version> and work out what would change; write nothing."""
    entry = manifest["components"].get(name)
    if entry is None:
        raise UpdateError("unknown component: %s" % name)
    if entry["source"] not in ("npm", "github"):
        raise UpdateError("%s is %s: it cannot be updated by this tool" % (name, entry["source"]))
    if not VERSION_RE.match(version):
        raise UpdateError("invalid version: %r" % version)
    url = tarball_url(entry, name, version)
    try:
        data = fetch(url)
    except OSError as error:
        raise UpdateError("cannot download %s: %s" % (url, error))
    available = _read_archive(data)

    staged = {}
    for pattern in entry["files"]:
        matches = {path: _without_source_map(path, content) for path, content in available.items()
                   if fnmatch.fnmatchcase(path, pattern) and not path.endswith(".map")}
        if not matches:
            raise UpdateError("%s@%s has no file matching %s" % (name, version, pattern))
        staged.update(matches)

    target = Path(root) / "node_modules" / name
    current = {}
    if target.is_dir():
        for path in target.rglob("*"):
            relative = path.relative_to(target).as_posix()
            if path.is_file() and any(fnmatch.fnmatchcase(relative, p) for p in entry["files"]):
                current[relative] = path.read_bytes()
    return UpdatePlan(
        name=name, version=version, staged=staged,
        added=sorted(set(staged) - set(current)),
        removed=sorted(set(current) - set(staged)),
        modified=sorted(p for p in staged if p in current and staged[p] != current[p]))


def _manifest_text(manifest):
    return json.dumps(manifest, indent=2, ensure_ascii=False) + "\n"


def apply_update(plan, manifest, root, manifest_path):
    """Replace node_modules/<name>/ by the staged files and bump the manifest.

    Everything is prepared beside the target first; the swap is rolled back if
    any step fails, so a component is never left half updated.
    """
    entry = manifest["components"][plan.name]
    updated = copy.deepcopy(manifest)
    updated["components"][plan.name]["version"] = plan.version
    if entry["source"] == "github":
        updated["components"][plan.name]["ref"] = plan.version

    target = Path(root) / "node_modules" / plan.name
    fresh = target.with_name(target.name + ".vendor-new")
    backup = target.with_name(target.name + ".vendor-old")
    manifest_path = Path(manifest_path)
    manifest_new = manifest_path.with_name(manifest_path.name + ".new")
    for leftover in (fresh, backup):
        if leftover.exists():
            shutil.rmtree(leftover)
    keep_backup = False
    try:
        for relative, content in plan.staged.items():
            destination = fresh / relative
            destination.parent.mkdir(parents=True, exist_ok=True)
            destination.write_bytes(content)
        manifest_new.write_bytes(_manifest_text(updated).encode("utf-8"))
        if target.exists():
            os.replace(target, backup)
        os.replace(fresh, target)
        os.replace(manifest_new, manifest_path)
    except OSError as error:
        try:
            if backup.exists():
                if target.exists():
                    shutil.rmtree(target)
                os.replace(backup, target)
        except OSError as rollback_error:
            keep_backup = True
            raise UpdateError("update of %s failed (%s) and the rollback failed too (%s): "
                              "the previous version is kept in %s" % (plan.name, error, rollback_error, backup))
        raise UpdateError("update of %s failed and was rolled back: %s" % (plan.name, error))
    finally:
        # The backup is the only copy of the previous version: delete it only
        # after a successful swap or a successful rollback.
        for leftover in (fresh,) if keep_backup else (fresh, backup):
            if leftover.exists():
                shutil.rmtree(leftover, ignore_errors=True)
        if manifest_new.exists():
            manifest_new.unlink()


STABLE_VERSION = re.compile(r"^v?(\d+(?:\.\d+)*)$")
# Bootstrap's scripts are loaded with the frozen 4.3.1 stylesheets (css/).
CSS_COUPLED = ("bootstrap", "bootstrap-material-design")
OSV_URL = "https://api.osv.dev/v1/query"


def _numeric(version):
    match = STABLE_VERSION.match(version)
    return tuple(int(part) for part in match.group(1).split(".")) if match else None


def latest_same_major(current, available):
    """Highest stable version sharing the major of 'current'; None if nothing newer."""
    base = _numeric(current)
    if base is None:
        return None
    best = None
    for candidate in available:
        number = _numeric(candidate)
        if number and number[0] == base[0] and number > base and (best is None or number > best[0]):
            best = (number, candidate)
    return STABLE_VERSION.match(best[1]).group(1) if best else None


def npm_versions(name, fetch):
    return list(json.loads(fetch("https://registry.npmjs.org/%s" % name))["versions"])


def git_run(command):
    return subprocess.run(command, capture_output=True, text=True, check=True, timeout=60).stdout


def git_tags(repo, run):
    output = run(["git", "ls-remote", "--tags", "https://github.com/%s.git" % repo])
    tags = []
    for line in output.splitlines():
        reference = line.split("\t")[-1]
        if reference.startswith("refs/tags/") and not reference.endswith("^{}"):
            tags.append(reference[len("refs/tags/"):])
    return tags


def http_post(url, body):
    request = urllib.request.Request(url, data=body, headers={
        "User-Agent": "bootstrap-darkroom-vendor/1", "Content-Type": "application/json"})
    with urllib.request.urlopen(request, timeout=60) as response:
        return response.read()


def advisories(name, version, post):
    """Known advisory identifiers (OSV) for an npm package at a given version."""
    body = json.dumps({"version": version, "package": {"name": name, "ecosystem": "npm"}}).encode("utf-8")
    return [vuln["id"] for vuln in json.loads(post(OSV_URL, body)).get("vulns", [])]


@dataclass
class OutdatedRow:
    name: str
    current: str
    kind: str
    fork: bool = False
    latest: str = None
    advisories: list = field(default_factory=list)
    notes: list = field(default_factory=list)
    error: str = None


def outdated(manifest, fetch, run, post):
    """Compare every component to what is published. Read-only. Returns (rows, failed)."""
    rows = []
    failed = False
    for name, entry in manifest["components"].items():
        row = OutdatedRow(name=name, current=entry["version"], kind=entry["source"],
                          fork=bool(entry.get("fork")))
        rows.append(row)
        if entry["source"] in ("manual", "frozen") or row.fork:
            continue
        try:
            if entry["source"] == "npm":
                row.latest = latest_same_major(entry["version"], npm_versions(name, fetch))
            else:
                row.latest = latest_same_major(entry["version"], git_tags(entry["repo"], run))
        except (OSError, ValueError, KeyError, subprocess.SubprocessError) as error:
            row.error = "%s" % error
        if entry["source"] == "npm":
            try:
                row.advisories = advisories(name, entry["version"], post)
            except (OSError, ValueError) as error:
                row.error = row.error or "advisories: %s" % error
        if row.latest and name in CSS_COUPLED:
            row.notes.append("scripts newer than the frozen 4.3.1 stylesheets: not tested together")
        failed = failed or row.error is not None
    return rows, failed


def _print_rows(rows):
    print("%-32s %-12s %-14s %s" % ("component", "shipped", "latest (same major)", "advisories / notes"))
    for row in rows:
        if row.error:
            latest, extra = "unreachable", row.error
        elif row.kind in ("manual", "frozen"):
            latest, extra = "-", "%s: not comparable" % row.kind
        elif row.fork:
            latest, extra = "-", "fork: compare with the upstream project by hand"
        else:
            latest = row.latest or "up to date"
            if row.kind != "npm":
                extra = "advisories not checked (not an npm package)"
            else:
                extra = ", ".join(row.advisories) if row.advisories else "none listed in OSV"
        print("%-32s %-12s %-19s %s" % (row.name, row.current, latest, extra))
        for note in row.notes:
            print("%-32s   note: %s" % ("", note))


def _print_plan(plan):
    print("%s -> %s" % (plan.name, plan.version))
    for label, paths in (("added", plan.added), ("modified", plan.modified), ("removed", plan.removed)):
        for path in paths:
            print("  %-8s %s" % (label, path))
    if not (plan.added or plan.modified or plan.removed):
        print("  no file changes")


def main(argv=None, tracked=tracked_files, fetch=http_get, run=git_run, post=http_post):
    common = argparse.ArgumentParser(add_help=False)
    common.add_argument("--root", default=str(ROOT), help="repository root")
    common.add_argument("--manifest", default=str(MANIFEST), help="path to vendor.json")
    parser = argparse.ArgumentParser(prog="vendor.py", description=__doc__.split("\n")[0])
    sub = parser.add_subparsers(dest="command", required=True)
    sub.add_parser("check", parents=[common], help="verify the manifest against the versioned files")
    sub.add_parser("outdated", parents=[common], help="compare shipped versions to the latest published (read-only)")
    update = sub.add_parser("update", parents=[common],
                            help="show (or with --apply, perform) the update of one component")
    update.add_argument("name")
    update.add_argument("version", help="npm version, or tag / commit for a GitHub source")
    update.add_argument("--apply", action="store_true", help="write the changes (default: dry run)")
    credits = sub.add_parser("credits", parents=[common],
                             help="show (or with --apply, write) include/credits.php from the manifest")
    credits.add_argument("--apply", action="store_true", help="write the file (default: dry run)")
    args = parser.parse_args(argv)

    try:
        manifest = load_manifest(args.manifest)
    except ManifestError as error:
        print("vendor.json: %s" % error, file=sys.stderr)
        return 2

    if args.command == "outdated":
        rows, failed = outdated(manifest, fetch, run, post)
        _print_rows(rows)
        if failed:
            print("Some lookups failed (see 'unreachable' above): the report is incomplete.", file=sys.stderr)
        return 1 if failed else 0

    if args.command == "update":
        try:
            plan = plan_update(args.name, args.version, manifest, Path(args.root), fetch)
            _print_plan(plan)
            if not args.apply:
                print("Dry run: nothing written. Re-run with --apply to perform it.")
                return 0
            apply_update(plan, manifest, Path(args.root), args.manifest)
        except UpdateError as error:
            print("update: %s" % error, file=sys.stderr)
            return 1
        print("Applied. Review with 'git diff', then run the manual checklist (see README).")
        return 0

    if args.command == "credits":
        try:
            text = render_credits(manifest)
        except ManifestError as error:
            print("vendor.json: %s" % error, file=sys.stderr)
            return 2
        path = Path(args.root) / CREDITS_FILE
        current = path.read_text(encoding="utf-8") if path.is_file() else ""
        diff = difflib.unified_diff(current.splitlines(True), text.splitlines(True),
                                    CREDITS_FILE.as_posix(), CREDITS_FILE.as_posix())
        sys.stdout.writelines(diff)
        if current == text:
            print("%s is up to date." % CREDITS_FILE.as_posix())
            return 0
        if not args.apply:
            print("Dry run: nothing written. Re-run with --apply to write it.")
            return 0
        path.write_text(text, encoding="utf-8", newline="\n")
        print("Written: %s" % CREDITS_FILE.as_posix())
        return 0

    if args.command == "check":
        problems = check(manifest, Path(args.root), tracked(Path(args.root)))
        try:
            problems += check_credits(manifest, Path(args.root))
        except ManifestError as error:
            problems.append("vendor.json: %s" % error)
        for problem in problems:
            print(problem)
        if not problems:
            print("vendor.json is consistent with the repository.")
        return 1 if problems else 0
    return 2


if __name__ == "__main__":
    sys.exit(main())
