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
OUT_OF_SCOPE = (".navbar-transparent", ".pswp", ".page-header", ".carousel")

# Navigation bars (spec addendum): handled only under a bar whose background is
# a neutral one of the skin (neutral_backgrounds()); the bar title is text.
NAVBAR_BACKGROUNDS = (".bg-primary", ".bg-secondary", ".bg-light", ".bg-dark", ".bg-white")

# Disabled controls keep their muted look: neither orange nor pushed.
DISABLED = ("disabled",)

# What a link is, judged on the last compound of a selector.
LINK_CLASSES = ("nav-link", "dropdown-item", "page-link", "btn-link", "card-link")
LINK_STATES = (":hover", ":focus", ":active", ".active", "[aria-current")

# Above this HSL saturation, a background is a colour, not a surface.
SATURATION_LIMIT = 0.25

# Below this contrast with the skin's page background, a text colour is not
# meant for the page: it sits on a surface of its own (see classify()).
MIN_SURFACE_CONTRAST = 2.0

# Added to every mixed selector: the out-of-scope areas are excluded by
# position, not only by the selector's text (a generic `a` rule reaches the
# menus of a navbar). :where() adds no specificity; every browser that knows
# color-mix() knows it. See generic_guard() and navbar_guard().
PSEUDO_ELEMENT = re.compile(r"::|:(?:before|after|first-line|first-letter)(?![\w-])")

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


def contrast_ratio(a, b):
    """WCAG contrast ratio of two colours, 1 to 21."""
    hi, lo = sorted((luminance(a), luminance(b)), reverse=True)
    return (hi + 0.05) / (lo + 0.05)


def is_navbar(selector):
    return ".navbar" in selector


def navbar_target(rule, navbars, selector=""):
    """(extreme, guard) of a navbar rule, or None when no neutral bar matches.

    The extreme follows the text colour (light text sits on a dark bar), and the
    rule only applies under a neutral bar of that polarity. Bootstrap says which
    bar a rule is for (.navbar-dark / .navbar-light); otherwise the text colour
    tells, at the luminance where white and black contrast equally (0.179).
    """
    colour = _colour_decl(rule)
    if not colour:
        return None
    if ".navbar-dark" in selector:
        light_text = True
    elif ".navbar-light" in selector:
        light_text = False
    else:
        light_text = luminance(parse_color(colour[0])) > 0.179
    classes = (navbars or {}).get("dark" if light_text else "light", [])
    if not classes:
        return None
    return ("#fff" if light_text else "#000", navbar_guard(classes))


def classify(selector, rule, scheme, page_bg=None, navbars=None):
    """Role of one selector of a rule, for a skin of `scheme` ('light' / 'dark').

    `page_bg` is the skin's page background (body). A text colour that does not
    read on it sits on a surface of its own (a white menu in a dark skin, a dark
    panel in a light one), declared by another rule: it is left alone.
    A navbar selector is checked against the bar instead (navbar_target()).
    """
    role = _classify(selector, rule, scheme)
    if role == Role.SKIP:
        return role
    if is_navbar(selector):
        return role if navbar_target(rule, navbars, selector) else Role.SKIP
    if page_bg is not None:
        colour = _colour_decl(rule)
        if contrast_ratio(parse_color(colour[0]), page_bg) < MIN_SURFACE_CONTRAST:
            return Role.SKIP
    return role


def _classify(selector, rule, scheme):
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
    if any(s in selector for s in OUT_OF_SCOPE + DISABLED):
        return Role.SKIP
    last = _last_compound(selector)
    # The bar title is text (spec addendum), even written a.navbar-brand.
    is_link = "navbar-brand" not in last and (bool(re.match(r"a(?![\w-])", last)) or any(
        re.search(r"\." + re.escape(c) + r"(?![\w-])", last) for c in LINK_CLASSES))
    if is_link:
        if any(s in last for s in LINK_STATES):
            return Role.LINK_STATE
        return Role.LINK_REST
    # Ordinary text is grey; a saturated text colour carries a meaning
    # (validation feedback, an icon accent) and is kept.
    colour = _colour_decl(rule)
    if colour and saturation(parse_color(colour[0])) > SATURATION_LIMIT:
        return Role.SKIP
    return Role.TEXT


# --- Rendering -------------------------------------------------------------

HEADER = "/* GENERATED by tools/gen_contrast.py — do not edit. */"
SUPPORTS = "@supports (color: color-mix(in srgb, red, blue))"
MIX = "color-mix(in srgb, {src}, {dst} calc(var(--bd-contrast, 0) * 1%))"


def extreme(scheme):
    return "#000" if scheme == "light" else "#fff"


def _colour_decl(rule):
    """The rule's effective `color` declaration (the last one), or None."""
    for prop, value, important in reversed(rule.decls):
        if prop == "color":
            return (value, important) if parse_color(value) else None
    return None


def _raw_colour_decl(rule):
    """The rule's last `color` declaration as written (any value), or None."""
    for prop, value, important in reversed(rule.decls):
        if prop == "color":
            return (value, important)
    return None


def _neutral_list(navbars):
    present = set((navbars or {}).get("dark", [])) | set((navbars or {}).get("light", []))
    return [c for c in NAVBAR_BACKGROUNDS if c in present]


def generic_guard(navbars):
    """Guard of the generic rules: everywhere but the out-of-scope areas.

    Navigation bars are excluded unless their background is a neutral one of the
    skin; the transparent bar (over the photo) always is.
    """
    neutral = _neutral_list(navbars)
    bars = ".navbar:not({}) *".format(", ".join(neutral)) if neutral else ".navbar *"
    return ":where(:not(.navbar-transparent *, {}, .pswp *, .page-header *, .carousel *))".format(bars)


def navbar_guard(classes):
    """Guard of a navbar rule: under a bar of one of these neutral backgrounds."""
    return ":where(.navbar:is({}) *)".format(", ".join(classes))


def guard(selector, suffix):
    """The selector with `suffix` added before any pseudo-element."""
    m = PSEUDO_ELEMENT.search(selector)
    if m:
        return selector[:m.start()] + suffix + selector[m.start():]
    return selector + suffix


def overrides(rules, scheme, page_bg=None, navbars=None):
    """(wrappers, selectors, declarations) of the generated file, in cascade order.

    Every source rule declaring a colour is restated at its own place: mixed
    when it qualifies, as written otherwise. The file is loaded after the whole
    stylesheet it corrects, so a mixed rule would otherwise climb above the
    later rules of the same specificity it used to lose to.
    """
    out = []
    general = (extreme(scheme), generic_guard(navbars))
    for rule in rules:
        raw = _raw_colour_decl(rule)
        if not raw:
            continue
        value, important = raw
        bang = " !important" if important else ""
        underline = "text-decoration: underline" + bang
        groups = {}
        skipped = []
        for selector in rule.selectors:
            role = classify(selector, rule, scheme, page_bg, navbars) if _colour_decl(rule) else Role.SKIP
            if role == Role.SKIP:
                skipped.append(selector)
                continue
            target = navbar_target(rule, navbars, selector) if is_navbar(selector) else general
            groups.setdefault((role,) + target, []).append(selector)
        for (role, dst, suffix), selectors in groups.items():
            to_extreme = "color: " + MIX.format(src=value, dst=dst) + bang
            if role == Role.TEXT:
                out.append((rule.wrappers, [guard(s, suffix) for s in selectors], [to_extreme]))
            elif role == Role.LINK_STATE:
                out.append((rule.wrappers, [guard(s, suffix) for s in selectors],
                            [to_extreme, underline]))
            else:
                out.append((rule.wrappers, [guard(s, suffix) for s in selectors],
                            ["color: " + MIX.format(src=value, dst=ORANGE) + bang]))
                # A link coloured by a selector more specific than a:hover keeps
                # its colour on hover too: give every resting link its own states.
                states = [guard(s + p, suffix) for s in selectors if not PSEUDO_ELEMENT.search(s)
                          for p in (":hover", ":focus")]
                if states:
                    out.append((rule.wrappers, states, [to_extreme, underline]))
        if skipped:
            out.append((rule.wrappers, skipped, ["color: " + value + bang]))
    return out


def render(rules, scheme, page_bg=None, navbars=None):
    """Text of a generated file: header, then every rule inside @supports."""
    lines = [HEADER, SUPPORTS + " {"]
    opened = ()
    for wrappers, selectors, decls in overrides(rules, scheme, page_bg, navbars):
        if wrappers != opened:
            for _ in opened:
                lines.append("  " * len(opened) + "}")
                opened = opened[:-1]
            for w in wrappers:
                lines.append("  " * (len(opened) + 1) + w + " {")
                opened = opened + (w,)
        pad = "  " * (len(opened) + 1)
        lines.append(pad + ", ".join(selectors) + " {")
        for d in decls:
            lines.append(pad + "  " + d + ";")
        lines.append(pad + "}")
    while opened:
        lines.append("  " * len(opened) + "}")
        opened = opened[:-1]
    lines.append("}")
    return "\n".join(lines) + "\n"


# --- Repository ------------------------------------------------------------

def light_skins(skins_php):
    """The $light list of include/skins.php, the single light / dark source."""
    text = Path(skins_php).read_text(encoding="utf-8")
    m = re.search(r"\$light\s*=\s*array\((.*?)\);", text, flags=re.S)
    if not m:
        raise ValueError("no $light list in " + str(skins_php))
    return set(re.findall(r"'([^']+)'", m.group(1)))


def list_skins(css_dir):
    return sorted(p.name for p in Path(css_dir).iterdir()
                  if (p / "bootstrap.min.css").is_file())


def _read_rules(*paths):
    rules = []
    for path in paths:
        if path.is_file():
            rules.extend(parse_css(path.read_text(encoding="utf-8")))
    return rules


def skin_rules(root, skin):
    folder = Path(root) / "css" / skin
    return _read_rules(folder / "bootstrap.min.css", folder / "skin.css")


def body_background(rules):
    """Colour of the last background declared on `body`, outside any at-rule."""
    found = None
    for rule in rules:
        if rule.wrappers or "body" not in rule.selectors:
            continue
        for prop, value, _ in rule.decls:
            if prop in ("background-color", "background"):
                colour = _background(Rule(["body"], [(prop, value, False)], ()))
                if colour:
                    found = colour
    return found


def generate(root=ROOT):
    """Every generated path, with its expected content."""
    root = Path(root)
    light = light_skins(root / "include" / "skins.php")
    theme_rules = _read_rules(root / "theme.css")
    files = {}
    for skin in list_skins(root / "css"):
        scheme = "light" if skin in light else "dark"
        folder = root / "css" / skin
        rules = skin_rules(root, skin)
        page_bg = body_background(rules)
        navbars = neutral_backgrounds(rules)
        files[folder / "contrast.css"] = render(rules, scheme, page_bg, navbars)
        files[folder / "contrast-theme.css"] = render(theme_rules, scheme, page_bg, navbars)
    return files


def neutral_backgrounds(rules):
    """Neutral `.bg-*` classes of a skin, by polarity: the bars handled (addendum)."""
    found = {}
    for rule in rules:
        if rule.wrappers or len(rule.selectors) != 1 or rule.selectors[0] not in NAVBAR_BACKGROUNDS:
            continue
        colour = _background(rule)
        if colour:
            found[rule.selectors[0]] = colour
    out = {"dark": [], "light": []}
    for cls in NAVBAR_BACKGROUNDS:
        colour = found.get(cls)
        if colour and colour[3] >= 0.5 and saturation(colour) <= SATURATION_LIMIT:
            out["light" if luminance(colour) >= 0.5 else "dark"].append(cls)
    return out


def report(root=ROOT):
    root = Path(root)
    light = light_skins(root / "include" / "skins.php")
    sources = (("skin", None), ("theme.css", _read_rules(root / "theme.css")))
    for skin in list_skins(root / "css"):
        scheme = "light" if skin in light else "dark"
        page_bg = body_background(skin_rules(root, skin))
        print("=== {} ({})".format(skin, scheme))
        for label, rules in sources:
            rules = rules if rules is not None else skin_rules(root, skin)
            by_role = {}
            for rule in rules:
                if not _colour_decl(rule):
                    continue
                for s in rule.selectors:
                    by_role.setdefault(classify(s, rule, scheme, page_bg, neutral_backgrounds(skin_rules(root, skin))), []).append(s)
            for role in (Role.TEXT, Role.LINK_REST, Role.LINK_STATE, Role.SKIP):
                items = by_role.get(role, [])
                print("--- {} / {} ({})".format(label, role, len(items)))
                for s in items:
                    print("    " + s)


def main(argv):
    files = generate()
    if "--report" in argv:
        report()
        return 0
    if "--check" in argv:
        stale = [p for p, text in files.items()
                 if not p.is_file() or p.read_text(encoding="utf-8") != text]
        for p in stale:
            print("stale: " + str(p.relative_to(ROOT)))
        return 1 if stale else 0
    for path, text in files.items():
        with open(path, "w", encoding="utf-8", newline="\n") as handle:
            handle.write(text)
    print("{} files written".format(len(files)))
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
