"""Browser fixtures for js/plugin-buttons.js (issue #45).

Serves the repository over a local HTTP server, renders each page of
tools/fixtures/ in headless Chrome (--dump-dom) and reads the JSON results the
page writes into <pre id="results">. Skipped when no Chrome/Edge is installed.
jQuery 1.11.3 is loaded from cdnjs, so the run needs network access.
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


class _QuietHandler(http.server.SimpleHTTPRequestHandler):
    def log_message(self, *args):
        pass


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
        match = re.search(r'<pre id="results">(.*?)</pre>', out.stdout, re.S)
        self.assertIsNotNone(match, 'no results block in %s' % page)
        body = html.unescape(match.group(1)).strip()
        self.assertTrue(body, 'empty results in %s (script error or jQuery not loaded?)' % page)
        return json.loads(body)

    def test_fixture_pages(self):
        for page in PAGES:
            results = self.render(page)
            self.assertTrue(results, 'no assertion ran in %s' % page)
            for result in results:
                with self.subTest(page=page, check=result['name']):
                    self.assertTrue(result['ok'], result['detail'])


if __name__ == '__main__':
    unittest.main()
