"""Check, report and update the third-party components shipped by the theme.

The components live under node_modules/<name>/ (only their runtime files are
versioned, see .gitignore) and are described by tools/vendor.json, the single
source of truth for versions, origins and kept files. Python standard library
only: no npm, no Yarn, no Node.

Usage (from the repository root):

    python tools/vendor.py check
"""

import argparse
import fnmatch
import json
import subprocess
import sys
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
ROOT = TOOLS.parent
MANIFEST = TOOLS / "vendor.json"

SOURCES = ("npm", "github", "manual", "frozen")


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


def main(argv=None, tracked=tracked_files):
    common = argparse.ArgumentParser(add_help=False)
    common.add_argument("--root", default=str(ROOT), help="repository root")
    common.add_argument("--manifest", default=str(MANIFEST), help="path to vendor.json")
    parser = argparse.ArgumentParser(prog="vendor.py", description=__doc__.split("\n")[0])
    sub = parser.add_subparsers(dest="command", required=True)
    sub.add_parser("check", parents=[common], help="verify the manifest against the versioned files")
    args = parser.parse_args(argv)

    try:
        manifest = load_manifest(args.manifest)
    except ManifestError as error:
        print("vendor.json: %s" % error, file=sys.stderr)
        return 2

    if args.command == "check":
        problems = check(manifest, Path(args.root), tracked(Path(args.root)))
        for problem in problems:
            print(problem)
        if not problems:
            print("vendor.json is consistent with the repository.")
        return 1 if problems else 0
    return 2


if __name__ == "__main__":
    sys.exit(main())
