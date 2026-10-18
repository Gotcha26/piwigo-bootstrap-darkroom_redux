"""Vendor the theme's web fonts from @fontsource into assets/fonts/.

The theme never loads a font from a CDN. Each style that needs web fonts gets a
generated css/<style>/fonts.css, loaded by template/header.tpl with
combine_css. Font files are woff2 only, every subset is kept: each subset is
declared with a unicode-range, so a browser only downloads the files that the
page actually needs.

Usage (from the repository root, network required):

    python tools/vendor_fonts.py

Re-running it rewrites assets/fonts/ and every css/*/fonts.css from scratch.
To upgrade a font, bump its version in FAMILIES and re-run.
"""

import io
import json
import re
import shutil
import sys
import tarfile
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
FONTS_DIR = ROOT / "assets" / "fonts"

# fontsource package -> (version, CSS family name, [(weight, style)])
# Weights are those requested by the styles' CSS (Bootswatch @import lines,
# PT Sans/Roboto as previously shipped by the typeface-* packages).
FAMILIES = {
    "pt-sans": ("5.3.0", "PT Sans", [(400, "normal"), (700, "normal"), (400, "italic"), (700, "italic")]),
    "roboto": ("5.3.0", "Roboto", [(w, s) for s in ("normal", "italic") for w in (300, 400, 500, 700)]),
    "lato": ("5.3.0", "Lato", [(300, "normal"), (400, "normal"), (700, "normal"), (400, "italic")]),
    "open-sans": ("5.3.0", "Open Sans", [(w, s) for s in ("normal", "italic") for w in (300, 400, 700)]),
    "source-sans-pro": ("5.3.0", "Source Sans Pro",
                        [(300, "normal"), (400, "normal"), (700, "normal"), (400, "italic")]),
    "news-cycle": ("5.3.0", "News Cycle", [(400, "normal"), (700, "normal")]),
    "nunito-sans": ("5.3.0", "Nunito Sans", [(400, "normal"), (600, "normal")]),
    "montserrat": ("5.3.0", "Montserrat", [(400, "normal")]),
    "neucha": ("5.3.0", "Neucha", [(400, "normal")]),
    "cabin-sketch": ("5.3.0", "Cabin Sketch", [(400, "normal")]),
    "ubuntu": ("5.3.0", "Ubuntu", [(400, "normal"), (700, "normal")]),
}

MATERIAL = ["amber", "blue", "brown", "deep-orange", "deep-purple", "indigo",
            "light-green", "lime", "purple", "red", "teal"]

# Style directory under css/ -> fonts it uses (body and headings font-family).
STYLES = {
    "bootstrap-darkroom": ["pt-sans"],
    "material-darkroom": ["pt-sans"],
    **{f"material-{name}": ["roboto"] for name in MATERIAL},
    "bootswatch-cosmo": ["source-sans-pro"],
    "bootswatch-cyborg": ["roboto"],
    "bootswatch-darkly": ["lato"],
    "bootswatch-flatly": ["lato"],
    "bootswatch-journal": ["news-cycle"],
    "bootswatch-lumen": ["source-sans-pro"],
    "bootswatch-lux": ["nunito-sans"],
    "bootswatch-materia": ["roboto"],
    "bootswatch-minty": ["montserrat"],
    "bootswatch-sandstone": ["roboto"],
    "bootswatch-simplex": ["open-sans"],
    "bootswatch-sketchy": ["neucha", "cabin-sketch"],
    "bootswatch-solar": ["source-sans-pro"],
    "bootswatch-spacelab": ["open-sans"],
    "bootswatch-superhero": ["lato"],
    "bootswatch-united": ["ubuntu"],
    "bootswatch-yeti": ["open-sans"],
}

FONT_FACE = re.compile(r"@font-face\s*\{.*?\}", re.S)
SRC = re.compile(r"src:\s*url\(\./files/([^)]+?\.woff2)\)\s*format\('woff2'\)[^;]*;")


def fetch_package(name, version):
    url = f"https://registry.npmjs.org/@fontsource/{name}/-/{name}-{version}.tgz"
    data = urllib.request.urlopen(url, timeout=60).read()
    return tarfile.open(fileobj=io.BytesIO(data))


def read(tar, member):
    return tar.extractfile(f"package/{member}").read()


def vendor_family(name, version, css_family, variants):
    """Copy the woff2 files and return the family's @font-face blocks, with
    url() relative to assets/fonts/<name>/ (placeholder {base})."""
    tar = fetch_package(name, version)
    dest = FONTS_DIR / name
    dest.mkdir(parents=True)
    (dest / "LICENSE").write_bytes(read(tar, "LICENSE"))
    metadata = json.loads(read(tar, "metadata.json"))

    blocks = []
    for weight, style in variants:
        css_name = f"{weight}.css" if style == "normal" else f"{weight}-italic.css"
        css = read(tar, css_name).decode("utf-8")
        for block in FONT_FACE.findall(css):
            if f"font-family: '{css_family}';" not in block:
                sys.exit(f"{name}: unexpected family in {css_name}: {block[:80]}")
            match = SRC.search(block)
            if not match:
                sys.exit(f"{name}: no woff2 source in {css_name}")
            woff2 = match.group(1)
            (dest / woff2).write_bytes(read(tar, f"files/{woff2}"))
            blocks.append(block.replace(match.group(0), f"src: url('{{base}}{woff2}') format('woff2');"))
    return {
        "blocks": blocks,
        "license": metadata.get("license", {}).get("type", "?"),
        "version": version,
    }


def main():
    if FONTS_DIR.exists():
        shutil.rmtree(FONTS_DIR)
    vendored = {}
    for name, (version, css_family, variants) in FAMILIES.items():
        vendored[name] = vendor_family(name, version, css_family, variants)
        print(f"{name:<16} {version}  {len(vendored[name]['blocks']):>3} faces  {vendored[name]['license']}")

    for style, names in STYLES.items():
        style_dir = ROOT / "css" / style
        if not style_dir.is_dir():
            sys.exit(f"unknown style directory: css/{style}")
        out = [
            "/* Generated by tools/vendor_fonts.py from @fontsource — do not edit.",
            f" * Fonts: {', '.join(f'{n} {vendored[n]['version']}' for n in names)} (assets/fonts/). */",
            "",
        ]
        for n in names:
            out += [b.replace("{base}", f"../../assets/fonts/{n}/") for b in vendored[n]["blocks"]]
        (style_dir / "fonts.css").write_text("\n".join(out) + "\n", encoding="utf-8", newline="\n")

    total = sum(p.stat().st_size for p in FONTS_DIR.rglob("*.woff2"))
    print(f"{len(STYLES)} fonts.css written, {total / 1024:.0f} KB of woff2 in assets/fonts/")


if __name__ == "__main__":
    main()
