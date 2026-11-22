import re
import sys
import unittest
from pathlib import Path

TOOLS = Path(__file__).resolve().parent.parent
ROOT = TOOLS.parent
sys.path.insert(0, str(TOOLS))

import gen_contrast as gc  # noqa: E402


class ParseTest(unittest.TestCase):
    def test_minified_rules_and_media(self):
        rules = gc.parse_css(
            "a{color:#9e9e9e}@media (min-width:768px){.x{color:red}}"
            "@font-face{font-family:x}")
        self.assertEqual([r.selectors for r in rules], [["a"], [".x"]])
        self.assertEqual(rules[1].wrappers, ("@media (min-width:768px)",))

    def test_comments_and_keyframes_are_ignored(self):
        rules = gc.parse_css(
            "/* a{color:red} */@keyframes k{from{color:red}to{color:blue}}"
            "@charset \"UTF-8\";b{color:#000}")
        self.assertEqual([r.selectors for r in rules], [["b"]])

    def test_commas_inside_not_do_not_split(self):
        rules = gc.parse_css("a:not([href]):not([tabindex]), .y{color:inherit}")
        self.assertEqual(rules[0].selectors, ["a:not([href]):not([tabindex])", ".y"])

    def test_important_is_kept(self):
        rules = gc.parse_css(".text-muted{color:#6c757d!important}")
        self.assertEqual(rules[0].decls, [("color", "#6c757d", True)])

    def test_nested_wrappers(self):
        rules = gc.parse_css("@media print{@supports (x:y){a{color:#000}}}")
        self.assertEqual(rules[0].wrappers, ("@media print", "@supports (x:y)"))


class ColourTest(unittest.TestCase):
    def test_formats(self):
        self.assertEqual(gc.parse_color("#fff"), (255, 255, 255, 1.0))
        self.assertEqual(gc.parse_color("#FFFFFF80")[:3], (255, 255, 255))
        self.assertEqual(gc.parse_color("rgba(255, 255, 255, .5)"), (255, 255, 255, 0.5))
        self.assertEqual(gc.parse_color("rgb(0,0,0)"), (0, 0, 0, 1.0))
        self.assertEqual(gc.parse_color("white"), (255, 255, 255, 1.0))
        for v in ("inherit", "currentColor", "transparent", "var(--x)", "initial"):
            self.assertIsNone(gc.parse_color(v))

    def test_luminance(self):
        self.assertAlmostEqual(gc.luminance((255, 255, 255, 1.0)), 1.0)
        self.assertAlmostEqual(gc.luminance((0, 0, 0, 1.0)), 0.0)


class ClassifyTest(unittest.TestCase):
    def role(self, css, scheme="dark", i=0):
        rule = gc.parse_css(css)[0]
        return gc.classify(rule.selectors[i], rule, scheme)

    def test_roles(self):
        self.assertEqual(self.role("body{color:#dbdbdb;background-color:#4f4f4f}"), gc.Role.TEXT)
        self.assertEqual(self.role("a{color:#9e9e9e}"), gc.Role.LINK_REST)
        self.assertEqual(self.role("a:hover{color:#fff}"), gc.Role.LINK_STATE)
        self.assertEqual(self.role(".nav-pills .nav-link.active{color:#fff}"), gc.Role.LINK_STATE)
        self.assertEqual(self.role(".text-muted{color:#6c757d!important}"), gc.Role.TEXT)
        self.assertEqual(self.role("a:not([href]):not([tabindex]){color:#999}"), gc.Role.LINK_REST)
        self.assertEqual(self.role(".card a:focus{color:#fff}"), gc.Role.LINK_STATE)

    def test_skips(self):
        self.assertEqual(self.role(".btn-primary{color:#fff}"), gc.Role.SKIP)
        self.assertEqual(self.role(".btn-link{color:#9e9e9e}"), gc.Role.LINK_REST)
        self.assertEqual(self.role(".text-white{color:#fff!important}"), gc.Role.SKIP)
        self.assertEqual(self.role(".badge-light{color:#212529}"), gc.Role.SKIP)
        self.assertEqual(
            self.role(".navbar-dark .navbar-nav .nav-link{color:rgba(255,255,255,.5)}"),
            gc.Role.SKIP)
        self.assertEqual(self.role(".pswp__button{color:#fff}"), gc.Role.SKIP)
        # saturated background = coloured surface
        self.assertEqual(self.role(".foo{color:#fff;background-color:#007bff}"), gc.Role.SKIP)
        # neutral background of the same polarity = page surface
        self.assertEqual(self.role(".card{color:#ccc;background-color:#2b2b2b}"), gc.Role.TEXT)
        # opposite polarity on a dark skin
        self.assertEqual(self.role(".foo{color:#222;background-color:#f8f9fa}", "dark"), gc.Role.SKIP)
        # a nearly transparent background does not count
        self.assertEqual(
            self.role(".foo{color:#222;background-color:rgba(0,123,255,.1)}", "light"),
            gc.Role.TEXT)

    def test_report_review_rulings(self):
        # semantic text colours (validation feedback, icon accents) keep their meaning
        self.assertEqual(self.role(".invalid-feedback{color:#dc3545}"), gc.Role.SKIP)
        self.assertEqual(self.role(".card .fas{color:#ffc107}"), gc.Role.SKIP)
        # ...but a saturated link still goes orange
        self.assertEqual(self.role("a{color:#007bff}", "light"), gc.Role.LINK_REST)
        # disabled controls stay muted
        self.assertEqual(self.role(".nav-link.disabled{color:#6c757d}"), gc.Role.SKIP)
        self.assertEqual(self.role(".page-item.disabled .page-link{color:#6c757d}"), gc.Role.SKIP)
        self.assertEqual(self.role(".btn-link:disabled{color:#6c757d}"), gc.Role.SKIP)
        # carousel controls and captions sit on pictures
        self.assertEqual(self.role(".carousel-control-next{color:#fff}", "light"), gc.Role.SKIP)

    def test_selector_list_is_split(self):
        rule = gc.parse_css("a, .btn-primary{color:#fff}")[0]
        self.assertEqual([gc.classify(s, rule, "dark") for s in rule.selectors],
                         [gc.Role.LINK_REST, gc.Role.SKIP])


class RenderTest(unittest.TestCase):
    MIX = "color-mix(in srgb, {src}, {dst} calc(var(--bd-contrast, 0) * 1%))"

    def test_header_and_supports(self):
        out = gc.render(gc.parse_css("body{color:#dbdbdb}"), "dark")
        self.assertTrue(out.startswith("/* GENERATED by tools/gen_contrast.py — do not edit. */"))
        self.assertIn("@supports (color: color-mix(in srgb, red, blue))", out)

    G = ":where(:not(.navbar-transparent *, .navbar *, .pswp *, .page-header *, .carousel *))"

    def test_link_rest_and_companion(self):
        out = gc.render(gc.parse_css(".card-body a{color:#9e9e9e}"), "dark")
        rest = out.index(".card-body a" + self.G + " {")
        hover = out.index(".card-body a:hover" + self.G + ", .card-body a:focus" + self.G + " {")
        self.assertLess(rest, hover)
        self.assertIn("color: " + self.MIX.format(src="#9e9e9e", dst="#ff7700"), out)
        self.assertIn("color: " + self.MIX.format(src="#9e9e9e", dst="#fff"), out)
        self.assertIn("text-decoration: underline", out)

    def test_link_state_goes_to_extreme_underlined(self):
        out = gc.render(gc.parse_css("a:hover{color:#0056b3}"), "light")
        self.assertIn("color: " + self.MIX.format(src="#0056b3", dst="#000"), out)
        self.assertIn("text-decoration: underline", out)

    def test_text_goes_to_black_on_light(self):
        out = gc.render(gc.parse_css("body{color:#212529;background-color:#fff}"), "light")
        self.assertIn(self.MIX.format(src="#212529", dst="#000"), out)
        self.assertNotIn("underline", out)

    def test_important_and_skip(self):
        out = gc.render(gc.parse_css(".text-muted{color:#6c757d!important}.btn-primary{color:#fff}"), "dark")
        self.assertIn("1%)) !important;", out)
        # a skipped rule is restated as is, never mixed
        self.assertIn(".btn-primary {\n    color: #fff;\n  }", out)

    def test_unparseable_colour_is_restated(self):
        out = gc.render(gc.parse_css("a:not([href]){color:inherit}"), "dark")
        self.assertIn("a:not([href]) {\n    color: inherit;\n  }", out)

    def test_skipped_rules_are_restated_in_place(self):
        # review I1: an override must not climb above a later skipped rule
        css = (".dropdown-item:hover{color:#16181b;background-color:#f8f9fa}"
               ".dropdown-item.active{color:#fff;background-color:#007bff}"
               "a{color:#007bff}a:not([href]){color:inherit!important}")
        out = gc.render(gc.parse_css(css), "light", (255, 255, 255, 1.0))
        self.assertLess(out.index(".dropdown-item:hover" + self.G), out.index(".dropdown-item.active {"))
        self.assertLess(out.index("a" + self.G + " {"), out.index("a:not([href]) {\n    color: inherit !important;"))

    def test_text_on_inverse_surface_is_kept(self):
        # review C1: a menu on a white surface in a dark skin, a dark panel in a light one
        dark_bg = (79, 79, 79, 1.0)
        out = gc.render(gc.parse_css(".dropdown-item{color:#4f4f4f}#quickconnect label{color:#212529}"),
                        "dark", dark_bg)
        self.assertNotIn("color-mix(in srgb, #4f4f4f", out)
        self.assertNotIn("color-mix(in srgb, #212529", out)
        out = gc.render(gc.parse_css("#sidebar .handle a{color:#fff}"), "light", (255, 255, 255, 1.0))
        self.assertNotIn("color-mix(in srgb, #fff", out)
        self.assertIn("#sidebar .handle a {\n    color: #fff;\n  }", out)

    def test_out_of_scope_guard_and_pseudo_element(self):
        # review I2: navbars, PhotoSwipe, page header and carousel excluded by position
        out = gc.render(gc.parse_css(".form-control::placeholder{color:#6c757d}"), "dark")
        self.assertIn(".form-control" + self.G + "::placeholder {", out)

    def test_media_wrapper_nested_in_supports(self):
        out = gc.render(gc.parse_css("@media (min-width:768px){a{color:#9e9e9e}}"), "dark")
        self.assertLess(out.index("@supports"), out.index("@media (min-width:768px)"))

    def test_no_companion_for_pseudo_element(self):
        out = gc.render(gc.parse_css("a::before{color:#9e9e9e}"), "dark")
        self.assertNotIn("::before:hover", out)


class NavbarTest(unittest.TestCase):
    """Spec addendum: navigation bars on a neutral background."""
    MIX = RenderTest.MIX
    NAVS = {"dark": [".bg-primary", ".bg-dark"], "light": []}
    UNDER_DARK = ":where(.navbar:is(.bg-primary, .bg-dark) *)"

    def out(self, css, scheme="dark"):
        return gc.render(gc.parse_css(css), scheme, (79, 79, 79, 1.0), self.NAVS)

    def test_nav_link_goes_orange_under_a_neutral_bar(self):
        out = self.out(".navbar-dark .navbar-nav .nav-link{color:#9e9e9e}")
        self.assertIn(".navbar-dark .navbar-nav .nav-link" + self.UNDER_DARK + " {", out)
        self.assertIn(self.MIX.format(src="#9e9e9e", dst="#ff7700"), out)

    def test_nav_link_state_goes_white_underlined(self):
        out = self.out(".navbar-dark .navbar-nav .nav-link:hover{color:#fff}")
        self.assertIn(self.MIX.format(src="#fff", dst="#fff"), out)
        self.assertIn("text-decoration: underline", out)

    def test_brand_is_text_not_link(self):
        out = self.out(".navbar-dark a.navbar-brand{color:#9e9e9e!important}"
                       ".navbar-dark a.navbar-brand:hover{color:#fff!important}")
        self.assertIn(self.MIX.format(src="#9e9e9e", dst="#fff") + " !important", out)
        self.assertNotIn("#ff7700", out)
        self.assertNotIn("underline", out)

    def test_extreme_follows_text_colour_not_skin(self):
        # a dark bar in a light skin pushes its text to white
        out = gc.render(gc.parse_css(".navbar-dark .navbar-text{color:#bbb}"), "light",
                        (255, 255, 255, 1.0), self.NAVS)
        self.assertIn(self.MIX.format(src="#bbb", dst="#fff"), out)

    def test_no_matching_bar_is_restated(self):
        out = self.out(".navbar-light .navbar-nav .nav-link{color:rgba(0,0,0,.5)}")
        self.assertNotIn("color-mix(in srgb, rgba", out)
        self.assertIn("color: rgba(0,0,0,.5);", out)

    def test_transparent_bar_is_restated(self):
        out = self.out(".navbar.navbar-transparent .nav-link{color:#fff}")
        self.assertNotIn("color-mix(in srgb, #fff", out)

    def test_generic_guard_spares_only_neutral_bars(self):
        out = self.out("a{color:#9e9e9e}")
        self.assertIn("a:where(:not(.navbar-transparent *, .navbar:not(.bg-primary, .bg-dark) *, "
                      ".pswp *, .page-header *, .carousel *)) {", out)

    def test_neutral_backgrounds_of_darkroom(self):
        navs = gc.neutral_backgrounds(gc.skin_rules(ROOT, "bootstrap-darkroom"))
        self.assertIn(".bg-dark", navs["dark"])
        self.assertIn(".bg-primary", navs["dark"])
        self.assertEqual(navs["light"], [".bg-white"] if ".bg-white" in navs["light"] else [])


class RepoTest(unittest.TestCase):
    def test_skins_are_listed(self):
        self.assertEqual(len(gc.list_skins(ROOT / "css")), 35)

    def test_light_list_matches_body_background(self):
        light = gc.light_skins(ROOT / "include" / "skins.php")
        self.assertEqual(len(light), 28)
        for skin in gc.list_skins(ROOT / "css"):
            bg = gc.body_background(gc.skin_rules(ROOT, skin))
            self.assertIsNotNone(bg, skin)
            self.assertEqual(gc.luminance(bg) >= 0.5, skin in light, skin)

    def test_no_mixed_colour_sits_on_an_inverse_surface(self):
        # review I3: in the generated files, every mixed source colour must read on
        # the skin's page background (otherwise it sits on a surface of its own)
        # navbar rules (guarded by .navbar:is(...)) are judged against their bar,
        # whose polarity is matched by construction (spec addendum)
        mix = re.compile(r"([^{}]+)\{\s*color: color-mix\(in srgb, ([^,]+?), #")
        for skin in gc.list_skins(ROOT / "css"):
            bg = gc.body_background(gc.skin_rules(ROOT, skin))
            for name in ("contrast.css", "contrast-theme.css"):
                text = (ROOT / "css" / skin / name).read_text(encoding="utf-8")
                for selector, src in mix.findall(text):
                    if ".navbar:is(" in selector:
                        continue
                    ratio = gc.contrast_ratio(gc.parse_color(src), bg)
                    self.assertGreaterEqual(ratio, gc.MIN_SURFACE_CONTRAST,
                                            "{} {}: {}".format(skin, name, src))

    def test_generated_files_are_up_to_date(self):
        self.assertEqual(gc.main(["--check"]), 0)


if __name__ == "__main__":
    unittest.main()
