import contextlib
import io
import json
import sys
import tarfile
import tempfile
import unittest
from pathlib import Path
from unittest import mock

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


def make_tgz(files, prefix="package", extra=()):
    """Build a .tgz in memory; 'extra' holds ready-made TarInfo + bytes pairs."""
    buffer = io.BytesIO()
    with tarfile.open(fileobj=buffer, mode="w:gz") as archive:
        for name, content in files.items():
            info = tarfile.TarInfo("%s/%s" % (prefix, name))
            info.size = len(content)
            archive.addfile(info, io.BytesIO(content))
        for info, content in extra:
            archive.addfile(info, io.BytesIO(content))
    return buffer.getvalue()


class UpdateTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name)
        target = self.root / "node_modules" / "lib" / "dist"
        target.mkdir(parents=True)
        (target / "a.js").write_bytes(b"old-a")
        (target / "gone.js").write_bytes(b"old-gone")
        self.manifest_path = self.root / "vendor.json"
        self.manifest = {"components": {"lib": component(files=["dist/*"])}}
        self.manifest_path.write_text(json.dumps(self.manifest), encoding="utf-8")
        self.urls = []

    def fetcher(self, archive):
        def fetch(url):
            self.urls.append(url)
            return archive
        return fetch

    def plan(self, archive, version="2.0.0", name="lib"):
        return vendor.plan_update(name, version, self.manifest, self.root, self.fetcher(archive))

    def new_archive(self):
        return make_tgz({"dist/a.js": b"new-a", "dist/b.js": b"new-b", "README.md": b"ignored"})

    def test_tarball_url_npm_and_github(self):
        self.assertEqual(vendor.tarball_url(component(), "lib", "2.0.0"),
                         "https://registry.npmjs.org/lib/-/lib-2.0.0.tgz")
        self.assertEqual(vendor.tarball_url(component(), "@scope/lib", "2.0.0"),
                         "https://registry.npmjs.org/@scope/lib/-/lib-2.0.0.tgz")
        self.assertEqual(vendor.tarball_url(component(source="github", repo="o/r", ref="x"), "lib", "v2"),
                         "https://codeload.github.com/o/r/tar.gz/v2")

    def test_plan_update_lists_added_modified_removed(self):
        plan = self.plan(self.new_archive())
        self.assertEqual(plan.added, ["dist/b.js"])
        self.assertEqual(plan.modified, ["dist/a.js"])
        self.assertEqual(plan.removed, ["dist/gone.js"])
        self.assertEqual(self.urls, ["https://registry.npmjs.org/lib/-/lib-2.0.0.tgz"])

    def test_plan_update_writes_nothing(self):
        self.plan(self.new_archive())
        self.assertEqual((self.root / "node_modules/lib/dist/a.js").read_bytes(), b"old-a")
        self.assertTrue((self.root / "node_modules/lib/dist/gone.js").exists())

    def test_main_without_apply_writes_nothing(self):
        archive = self.new_archive()
        with contextlib.redirect_stdout(io.StringIO()) as out:
            code = vendor.main(["update", "lib", "2.0.0", "--root", str(self.root),
                                "--manifest", str(self.manifest_path)],
                               fetch=self.fetcher(archive))
        self.assertEqual(code, 0)
        self.assertIn("dist/b.js", out.getvalue())
        self.assertIn("--apply", out.getvalue())
        self.assertEqual((self.root / "node_modules/lib/dist/a.js").read_bytes(), b"old-a")
        self.assertEqual(json.loads(self.manifest_path.read_text())["components"]["lib"]["version"], "1.0.0")

    def test_apply_replaces_files_and_bumps_version(self):
        vendor.apply_update(self.plan(self.new_archive()), self.manifest, self.root, self.manifest_path)
        dist = self.root / "node_modules/lib/dist"
        self.assertEqual((dist / "a.js").read_bytes(), b"new-a")
        self.assertEqual((dist / "b.js").read_bytes(), b"new-b")
        self.assertFalse((dist / "gone.js").exists())
        self.assertFalse((self.root / "node_modules/lib/README.md").exists())
        saved = json.loads(self.manifest_path.read_text(encoding="utf-8"))
        self.assertEqual(saved["components"]["lib"]["version"], "2.0.0")

    def test_apply_sets_ref_for_github_sources(self):
        self.manifest["components"]["lib"].update(source="github", repo="o/r", ref="old")
        vendor.apply_update(self.plan(self.new_archive(), version="v2"), self.manifest, self.root,
                            self.manifest_path)
        saved = json.loads(self.manifest_path.read_text(encoding="utf-8"))["components"]["lib"]
        self.assertEqual((saved["version"], saved["ref"]), ("v2", "v2"))

    def test_update_never_stages_source_maps_and_strips_their_comment(self):
        archive = make_tgz({
            "dist/a.js": b"code();\n//# sourceMappingURL=a.js.map\n",
            "dist/a.js.map": b"{}",
            "dist/s.css": b"a{}\n/*# sourceMappingURL=s.css.map */\n",
        })
        plan = self.plan(archive)
        self.assertNotIn("dist/a.js.map", plan.staged)
        self.assertEqual(plan.staged["dist/a.js"], b"code();\n")
        self.assertEqual(plan.staged["dist/s.css"], b"a{}\n")

    def test_rejects_path_traversal_in_archive(self):
        evil = tarfile.TarInfo("package/../evil.js")
        archive = make_tgz({"dist/a.js": b"x"}, extra=[(evil, b"boom")])
        with self.assertRaises(vendor.UpdateError):
            self.plan(archive)
        self.assertFalse((self.root / "evil.js").exists())

    def test_rejects_symlink_in_archive(self):
        link = tarfile.TarInfo("package/dist/link.js")
        link.type = tarfile.SYMTYPE
        link.linkname = "/etc/passwd"
        with self.assertRaises(vendor.UpdateError):
            self.plan(make_tgz({"dist/a.js": b"x"}, extra=[(link, b"")]))

    def test_rejects_bad_version_string(self):
        for bad in ("../x", "", "1.0 ", "a/b", "-rf"):
            with self.subTest(version=bad):
                with self.assertRaises(vendor.UpdateError):
                    self.plan(self.new_archive(), version=bad)
        self.assertEqual(self.urls, [])

    def test_unknown_or_unupdatable_component_is_an_error(self):
        with self.assertRaises(vendor.UpdateError):
            self.plan(self.new_archive(), name="ghost")
        self.manifest["components"]["lib"]["source"] = "manual"
        with self.assertRaises(vendor.UpdateError):
            self.plan(self.new_archive())

    def test_missing_listed_file_in_archive_is_an_error(self):
        self.manifest["components"]["lib"]["files"] = ["dist/a.js", "dist/never.js"]
        with self.assertRaises(vendor.UpdateError):
            self.plan(self.new_archive())

    def test_download_failure_is_an_update_error(self):
        def broken(url):
            raise OSError("offline")
        with self.assertRaises(vendor.UpdateError):
            vendor.plan_update("lib", "2.0.0", self.manifest, self.root, broken)

    def test_apply_failure_leaves_component_untouched(self):
        plan = self.plan(self.new_archive())
        real_replace = vendor.os.replace
        calls = []

        def flaky(src, dst):
            calls.append(dst)
            if len(calls) == 2:
                raise OSError("disk full")
            return real_replace(src, dst)

        with mock.patch.object(vendor.os, "replace", flaky):
            with self.assertRaises(vendor.UpdateError):
                vendor.apply_update(plan, self.manifest, self.root, self.manifest_path)
        dist = self.root / "node_modules/lib/dist"
        self.assertEqual((dist / "a.js").read_bytes(), b"old-a")
        self.assertTrue((dist / "gone.js").exists())
        self.assertFalse((dist / "b.js").exists())
        self.assertEqual(json.loads(self.manifest_path.read_text())["components"]["lib"]["version"], "1.0.0")
        leftovers = [p.name for p in (self.root / "node_modules").iterdir()]
        self.assertEqual(leftovers, ["lib"])


class RealRepoTest(unittest.TestCase):
    def test_real_manifest_matches_repo(self):
        manifest = vendor.load_manifest(TOOLS / "vendor.json")
        self.assertEqual(vendor.check(manifest, ROOT, vendor.tracked_files(ROOT)), [])


if __name__ == "__main__":
    unittest.main()
