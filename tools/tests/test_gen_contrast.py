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

    def test_selector_list_is_split(self):
        rule = gc.parse_css("a, .btn-primary{color:#fff}")[0]
        self.assertEqual([gc.classify(s, rule, "dark") for s in rule.selectors],
                         [gc.Role.LINK_REST, gc.Role.SKIP])


if __name__ == "__main__":
    unittest.main()
