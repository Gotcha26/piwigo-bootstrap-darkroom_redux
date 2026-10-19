"""Browser fixtures for js/plugin-buttons.js (issue #45).

Serves the repository over a local HTTP server, renders each page of
tools/fixtures/ in headless Chrome (--dump-dom) and reads the JSON results the
page writes into <pre id="results">. Skipped when no Chrome/Edge is installed.
jQuery 1.11.3 is loaded from cdnjs: offline, a page reports "no-jquery" and the test is skipped.
"""

import functools
import html
import http.server
import json
import os
import re
import shutil
import subprocess
import threading
import unittest

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

PAGES = [
    'plugin-buttons.html',
    'plugin-buttons-switchbox.html',
    'plugin-buttons-nojs.html',
]

BROWSER_CANDIDATES = [
    r'C:\Program Files\Google\Chrome\Application\chrome.exe',
    r'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
    r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
    'google-chrome',
    'chromium',
    'chromium-browser',
]


def find_browser():
    for candidate in BROWSER_CANDIDATES:
        path = candidate if os.path.isfile(candidate) else shutil.which(candidate)
        if path:
            return path
    return None


OFFLINE_MARKER = 'no-jquery'


def parse_results(dom):
    """Results a fixture page wrote, or None when it could not load jQuery (offline)."""
    match = re.search(r'<pre id="results">(.*?)</pre>', dom, re.S)
    if not match:
        raise ValueError('no results block')
    body = html.unescape(match.group(1)).strip()
    if not body:
        raise ValueError('empty results (script error?)')
    results = json.loads(body)
    return None if results == OFFLINE_MARKER else results


class _QuietHandler(http.server.SimpleHTTPRequestHandler):
    def log_message(self, *args):
        pass


class ParseResults(unittest.TestCase):
    def test_results_are_decoded(self):
        dom = '<pre id="results">[{&quot;name&quot;:&quot;a&quot;,&quot;ok&quot;:true}]</pre>'
        self.assertEqual(parse_results(dom), [{'name': 'a', 'ok': True}])

    def test_offline_marker_means_no_results(self):
        self.assertIsNone(parse_results('<pre id="results">"no-jquery"</pre>'))

    def test_missing_block_fails(self):
        with self.assertRaises(ValueError):
            parse_results('<body></body>')


class PluginButtonsFixtures(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.browser = find_browser()
        if not cls.browser:
            raise unittest.SkipTest('no headless Chrome/Edge available')
        handler = functools.partial(_QuietHandler, directory=ROOT)
        cls.server = http.server.ThreadingHTTPServer(('127.0.0.1', 0), handler)
        threading.Thread(target=cls.server.serve_forever, daemon=True).start()

    @classmethod
    def tearDownClass(cls):
        cls.server.shutdown()
        cls.server.server_close()

    def render(self, page):
        url = 'http://127.0.0.1:%d/tools/fixtures/%s' % (self.server.server_port, page)
        out = subprocess.run(
            [self.browser, '--headless=new', '--disable-gpu', '--no-first-run',
             '--window-size=1280,800', '--virtual-time-budget=10000', '--dump-dom', url],
            capture_output=True, text=True, encoding='utf-8', timeout=120)
        try:
            return parse_results(out.stdout)
        except ValueError as e:
            self.fail('%s: %s' % (page, e))

    def test_fixture_pages(self):
        for page in PAGES:
            results = self.render(page)
            if results is None:
                self.skipTest('jQuery could not be loaded from cdnjs (offline?)')
            self.assertTrue(results, 'no assertion ran in %s' % page)
            for result in results:
                with self.subTest(page=page, check=result['name']):
                    self.assertTrue(result['ok'], result['detail'])


if __name__ == '__main__':
    unittest.main()
