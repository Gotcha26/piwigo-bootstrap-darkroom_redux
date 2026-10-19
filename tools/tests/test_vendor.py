import contextlib
import io
import json
import sys
import tempfile
import unittest
from pathlib import Path

TOOLS = Path(__file__).resolve().parent.parent
ROOT = TOOLS.parent
sys.path.insert(0, str(TOOLS))

import vendor  # noqa: E402


def component(**overrides):
    base = {"version": "1.0.0", "source": "npm", "license": "MIT", "files": ["dist/a.js"]}
    base.update(overrides)
    return base


class ManifestTest(unittest.TestCase):
    def write(self, data):
        handle = tempfile.NamedTemporaryFile("w", suffix=".json", delete=False, encoding="utf-8")
        json.dump(data, handle)
        handle.close()
        self.addCleanup(Path(handle.name).unlink)
        return Path(handle.name)

    def test_load_manifest_rejects_unknown_source(self):
        path = self.write({"components": {"x": component(source="cdn")}})
        with self.assertRaises(vendor.ManifestError):
            vendor.load_manifest(path)

    def test_load_manifest_requires_files_for_non_frozen(self):
        data = component()
        del data["files"]
        with self.assertRaises(vendor.ManifestError):
            vendor.load_manifest(self.write({"components": {"x": data}}))

    def test_load_manifest_requires_repo_and_ref_for_github(self):
        with self.assertRaises(vendor.ManifestError):
            vendor.load_manifest(self.write({"components": {"x": component(source="github")}}))


class CheckTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)

    def test_check_ok_when_consistent(self):
        manifest = {"components": {"x": component(files=["dist/*"])}}
        tracked = {"node_modules/x/dist/a.js", "node_modules/x/dist/b.css"}
        self.assertEqual(vendor.check(manifest, self.root, tracked), [])

    def test_check_reports_listed_file_missing_on_disk(self):
        manifest = {"components": {"x": component(files=["dist/a.js", "dist/gone.js"])}}
        problems = vendor.check(manifest, self.root, {"node_modules/x/dist/a.js"})
        self.assertEqual(len(problems), 1)
        self.assertIn("dist/gone.js", problems[0])

    def test_check_reports_tracked_file_not_listed(self):
        manifest = {"components": {"x": component()}}
        problems = vendor.check(manifest, self.root, {"node_modules/x/dist/a.js", "node_modules/x/extra.js"})
        self.assertEqual(len(problems), 1)
        self.assertIn("node_modules/x/extra.js", problems[0])

    def test_check_reports_tracked_file_of_unknown_component(self):
        problems = vendor.check({"components": {}}, self.root, {"node_modules/ghost/a.js"})
        self.assertEqual(len(problems), 1)
        self.assertIn("ghost", problems[0])

    def test_check_manual_file_must_exist_in_repo(self):
        manifest = {"components": {"m": component(source="manual", files=["js/m.js"])}}
        self.assertEqual(len(vendor.check(manifest, self.root, set())), 1)
        (self.root / "js").mkdir()
        (self.root / "js" / "m.js").write_text("x")
        self.assertEqual(vendor.check(manifest, self.root, set()), [])

    def test_expected_files_prefixes_node_modules(self):
        manifest = {"components": {"x": component(), "m": component(source="manual", files=["js/m.js"])}}
        self.assertEqual(vendor.expected_files(manifest), {"node_modules/x/dist/a.js", "js/m.js"})

    def test_main_check_returns_1_on_problem(self):
        manifest = self.root / "vendor.json"
        manifest.write_text(json.dumps({"components": {"x": component()}}))
        with contextlib.redirect_stdout(io.StringIO()) as out:
            code = vendor.main(["check", "--root", str(self.root), "--manifest", str(manifest)],
                               tracked=lambda root: set())
        self.assertEqual(code, 1)
        self.assertIn("dist/a.js", out.getvalue())


class RealRepoTest(unittest.TestCase):
    def test_real_manifest_matches_repo(self):
        manifest = vendor.load_manifest(TOOLS / "vendor.json")
        self.assertEqual(vendor.check(manifest, ROOT, vendor.tracked_files(ROOT)), [])


if __name__ == "__main__":
    unittest.main()
