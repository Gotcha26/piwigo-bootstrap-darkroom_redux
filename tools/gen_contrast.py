"""Generate the per-skin overrides of the text contrast setting (#83).

The public CSS is frozen (compiled Bootstrap 4.3.1, colours written in full,
no custom property to hook on), and color-mix() cannot read the colour it
replaces: inside `color`, currentColor is the colour inherited from the
parent. So the original colour of every rule has to be written into its
override. This tool does that inventory, instead of a hand-kept list.

For each skin (css/<skin>/), it reads the stylesheets actually served, in
cascade order, classifies every `color` declaration by role and writes:

    css/<skin>/contrast.css        overrides of bootstrap.min.css + skin.css
    css/<skin>/contrast-theme.css  overrides of the parent theme.css

Roles: ordinary text is mixed towards black (light skin) or white (dark
skin), links towards the signature orange, link states (hover, focus,
active) towards black / white and underlined; text on a coloured background,
explicit utilities, navbars, PhotoSwipe and the photo page header are left
alone. One custom property, --bd-contrast (1-100), drives every mix; the
theme sets it, and loads these files, only when the setting is above 0.

Generated files are versioned and never edited by hand. Python standard
library only.

Usage (from the repository root):

    python tools/gen_contrast.py            write every file
    python tools/gen_contrast.py --check    exit 1 if a file is missing or stale
    python tools/gen_contrast.py --report   print the classification, per skin
"""

import re
import sys
from collections import namedtuple
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

ORANGE = "#ff7700"

Rule = namedtuple("Rule", "selectors decls wrappers")


class Role:
    SKIP = "skip"
    TEXT = "text"
    LINK_REST = "link"
    LINK_STATE = "link-state"


# At-rules whose content is made of ordinary rules: recursed, kept as wrappers.
WRAPPING_AT_RULES = ("@media", "@supports")

# Elements drawn on a coloured background of their own: their text colour is
# functional (white on a blue button...). Closed list, matched on class names.
# - prefixes: the class itself and every variant (.badge, .badge-primary...)
COLOURED_PREFIXES = ("badge", "alert", "bg-", "tooltip", "popover", "toast")
# - exact classes
COLOURED_EXACT = ("btn", "modal-header")
# - compound states of an otherwise neutral element
COLOURED_COMPOUNDS = (
    ".list-group-item.active",
    ".page-item.active",
    ".dropdown-item.active",
    ".dropdown-item:active",
)

# Colour utilities chosen explicitly by a template: left alone, except the two
# that only say "ordinary text".
TEXT_UTILITY_KEPT = ("text-muted", "text-body")

# Out of scope for the first version (spec, non-goals): navbars (their
# background is a setting, up to bg-primary), the PhotoSwipe viewer (black
# background), the photo page header (text over a picture).
OUT_OF_SCOPE = (".navbar", ".pswp", ".page-header")

# What a link is, judged on the last compound of a selector.
LINK_CLASSES = ("nav-link", "dropdown-item", "page-link", "btn-link", "card-link")
LINK_STATES = (":hover", ":focus", ":active", ".active", "[aria-current")

# Above this HSL saturation, a background is a colour, not a surface.
SATURATION_LIMIT = 0.25

NAMED_COLOURS = {
    "black": (0, 0, 0), "white": (255, 255, 255), "red": (255, 0, 0),
    "green": (0, 128, 0), "blue": (0, 0, 255), "yellow": (255, 255, 0),
    "gray": (128, 128, 128), "grey": (128, 128, 128), "silver": (192, 192, 192),
    "maroon": (128, 0, 0), "purple": (128, 0, 128), "fuchsia": (255, 0, 255),
    "lime": (0, 255, 0), "olive": (128, 128, 0), "navy": (0, 0, 128),
    "teal": (0, 128, 128), "aqua": (0, 255, 255), "orange": (255, 165, 0),
    "darkgray": (169, 169, 169), "darkgrey": (169, 169, 169),
    "lightgray": (211, 211, 211), "lightgrey": (211, 211, 211),
    "dimgray": (105, 105, 105), "dimgrey": (105, 105, 105),
    "gainsboro": (220, 220, 220), "whitesmoke": (245, 245, 245),
}


# --- Parsing ---------------------------------------------------------------

def _strip_comments(text):
    return re.sub(r"/\*.*?\*/", "", text, flags=re.S)


def _scan_to(text, i, stops):
    """Index of the first char of `stops` at depth 0 (parens, brackets, strings)."""
    depth = 0
    quote = None
    n = len(text)
    while i < n:
        c = text[i]
        if quote:
            if c == "\\":
                i += 2
                continue
            if c == quote:
                quote = None
        elif c in "\"'":
            quote = c
        elif c in "([":
            depth += 1
        elif c in ")]":
            depth -= 1
        elif depth == 0 and c in stops:
            return i
        i += 1
    return n


def _skip_block(text, i):
    """i points just after a '{': index just after its matching '}'."""
    depth = 1
    while i < len(text) and depth:
        j = _scan_to(text, i, "{}")
        if j >= len(text):
            return j
        depth += 1 if text[j] == "{" else -1
        i = j + 1
    return i


def split_top(text, sep):
    """Split on `sep` outside parens, brackets and strings."""
    parts = []
    i = 0
    while True:
        j = _scan_to(text, i, sep)
        parts.append(text[i:j])
        if j >= len(text):
            return parts
        i = j + 1


def _normalize(text):
    return " ".join(text.split())


def _parse_decls(body):
    decls = []
    for chunk in split_top(body, ";"):
        if ":" not in chunk:
            continue
        prop, value = chunk.split(":", 1)
        value = value.strip()
        important = False
        m = re.search(r"!\s*important\s*$", value, flags=re.I)
        if m:
            important = True
            value = value[:m.start()].strip()
        decls.append((prop.strip().lower(), value, important))
    return decls


def _parse_block(text, i, wrappers, out):
    n = len(text)
    while i < n:
        while i < n and text[i].isspace():
            i += 1
        if i >= n:
            return n
        if text[i] == "}":
            return i + 1
        j = _scan_to(text, i, "{;}")
        prelude = _normalize(text[i:j])
        if j >= n:
            return n
        if text[j] == ";":
            i = j + 1
            continue
        if text[j] == "}":
            return j + 1
        if prelude.startswith("@"):
            keyword = prelude.split(" ", 1)[0].split("(", 1)[0].lower()
            if keyword in WRAPPING_AT_RULES:
                i = _parse_block(text, j + 1, wrappers + (prelude,), out)
            else:
                i = _skip_block(text, j + 1)
            continue
        end = _scan_to(text, j + 1, "}")
        selectors = [s.strip() for s in split_top(prelude, ",") if s.strip()]
        if selectors:
            out.append(Rule([_normalize(s) for s in selectors],
                            _parse_decls(text[j + 1:end]), wrappers))
        i = end + 1
    return n


def parse_css(text):
    """Rules of a stylesheet, in source order (see Rule)."""
    out = []
    _parse_block(_strip_comments(text), 0, (), out)
    return out


# --- Colours ---------------------------------------------------------------

def _channel(token):
    token = token.strip()
    if token.endswith("%"):
        return round(float(token[:-1]) * 255 / 100)
    return round(float(token))


def parse_color(value):
    """(r, g, b, alpha) of a colour value, None when it is not a plain colour."""
    v = value.strip().lower()
    if v.startswith("#"):
        h = v[1:]
        if not re.fullmatch(r"[0-9a-f]{3,8}", h) or len(h) not in (3, 4, 6, 8):
            return None
        if len(h) in (3, 4):
            h = "".join(c * 2 for c in h)
        alpha = int(h[6:8], 16) / 255 if len(h) == 8 else 1.0
        return (int(h[0:2], 16), int(h[2:4], 16), int(h[4:6], 16), alpha)
    m = re.fullmatch(r"rgba?\((.*)\)", v)
    if m:
        parts = re.split(r"[\s,/]+", m.group(1).strip())
        if len(parts) not in (3, 4):
            return None
        try:
            r, g, b = (_channel(p) for p in parts[:3])
            alpha = 1.0
            if len(parts) == 4:
                a = parts[3]
                alpha = float(a[:-1]) / 100 if a.endswith("%") else float(a)
        except ValueError:
            return None
        return (r, g, b, alpha)
    if v in NAMED_COLOURS:
        return NAMED_COLOURS[v] + (1.0,)
    return None


def luminance(rgba):
    """WCAG relative luminance, 0 (black) to 1 (white)."""
    def lin(c):
        c = c / 255
        return c / 12.92 if c <= 0.03928 else ((c + 0.055) / 1.055) ** 2.4
    r, g, b = rgba[:3]
    return 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b)


def saturation(rgba):
    """HSL saturation, 0 to 1."""
    hi = max(rgba[:3]) / 255
    lo = min(rgba[:3]) / 255
    if hi == lo:
        return 0.0
    light = (hi + lo) / 2
    return (hi - lo) / (1 - abs(2 * light - 1))


def is_coloured_background(rgba, scheme):
    """A background that is a colour, not a page surface of this skin."""
    if rgba is None or rgba[3] < 0.5:
        return False
    if saturation(rgba) > SATURATION_LIMIT:
        return True
    is_light = luminance(rgba) >= 0.5
    return is_light != (scheme == "light")


# --- Classification --------------------------------------------------------

def _background(rule):
    for prop, value, _ in reversed(rule.decls):
        if prop == "background-color":
            return parse_color(value)
        if prop == "background":
            colour = parse_color(value)
            if colour:
                return colour
            for token in split_top(value, " "):
                colour = parse_color(token)
                if colour:
                    return colour
            return None
    return None


def _classes(selector):
    return re.findall(r"\.(-?[_a-zA-Z][\w-]*)", re.sub(r":not\([^)]*\)", "", selector))


def _last_compound(selector):
    """Last compound selector, :not(...) contents removed."""
    plain = re.sub(r":not\([^)]*\)", "", selector)
    parts = re.split(r"\s*[>+~]\s*|\s+", plain.strip())
    return parts[-1] if parts else plain


def classify(selector, rule, scheme):
    """Role of one selector of a rule, for a skin of `scheme` ('light' / 'dark')."""
    if is_coloured_background(_background(rule), scheme):
        return Role.SKIP
    classes = _classes(selector)
    for cls in classes:
        if cls in COLOURED_EXACT or cls.startswith(COLOURED_PREFIXES):
            return Role.SKIP
        if cls.startswith("btn-") and cls != "btn-link":
            return Role.SKIP
    if any(c in selector for c in COLOURED_COMPOUNDS):
        return Role.SKIP
    if any(c.startswith("text-") and c not in TEXT_UTILITY_KEPT for c in classes):
        return Role.SKIP
    if any(s in selector for s in OUT_OF_SCOPE):
        return Role.SKIP
    last = _last_compound(selector)
    is_link = bool(re.match(r"a(?![\w-])", last)) or any(
        re.search(r"\." + re.escape(c) + r"(?![\w-])", last) for c in LINK_CLASSES)
    if is_link:
        if any(s in last for s in LINK_STATES):
            return Role.LINK_STATE
        return Role.LINK_REST
    return Role.TEXT
