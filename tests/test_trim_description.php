<?php
/**
 * Empty paragraphs trimmed at the edges of the album description.
 *
 * Rule under test: ThemeController::trimEmptyParagraphs() drops leading and trailing blank
 * <p>/<div> blocks (whitespace, non-breaking spaces, <br>) and leaves everything else as is —
 * including blank blocks between two real paragraphs. The controller hooks it on loc_end_index.
 *
 * Run: php tests/test_trim_description.php
 */

$fail = 0;
function bd_assert($c, $m) {
  global $fail;
  if (!$c) {
    fwrite(STDERR, "FAIL: $m\n");
    $fail++;
  } else {
    echo "ok: $m\n";
  }
}

// The class file has no top-level code: loading it needs nothing from Piwigo.
require_once dirname(__DIR__) . '/include/themecontroller.php';
$trim = function ($html) { return \BootstrapDarkroom\ThemeController::trimEmptyParagraphs($html); };

bd_assert($trim('<p>Hello</p>') === '<p>Hello</p>', 'plain description untouched');
bd_assert($trim('<p>Hello</p><p> </p>') === '<p>Hello</p>', 'trailing whitespace paragraph removed');
bd_assert($trim("<p>Hello</p>\n<p>&nbsp;</p>\n") === '<p>Hello</p>', 'trailing &nbsp; paragraph removed');
bd_assert($trim("<p>Hello</p><p>\xC2\xA0</p>") === '<p>Hello</p>', 'trailing raw U+00A0 paragraph removed');
bd_assert($trim('<p>Hello</p><p><br></p><p><br /></p>') === '<p>Hello</p>', 'several trailing <br> paragraphs removed');
bd_assert($trim('<p class="x">&#160;</p><p>Hello</p>') === '<p>Hello</p>', 'leading empty paragraph with attributes removed');
bd_assert($trim('<div><br></div><p>Hello</p><div> </div>') === '<p>Hello</p>', 'empty <div> blocks removed at both ends');
bd_assert($trim('<p>A</p><p>&nbsp;</p><p>B</p>') === '<p>A</p><p>&nbsp;</p><p>B</p>', 'blank paragraph between two paragraphs kept');
bd_assert($trim('<p>&nbsp;</p>') === '', 'description made only of blank lines becomes empty');
bd_assert($trim('Plain text') === 'Plain text', 'text without markup untouched');
bd_assert($trim('<p>Hello</p><div id="map"></div>') === '<p>Hello</p><div id="map"></div>', 'empty container with an id kept (plugin placeholder)');
bd_assert($trim('<p>Hello</p><div class="w" data-widget="x"></div>') === '<p>Hello</p><div class="w" data-widget="x"></div>', 'empty container with a data-* attribute kept');
bd_assert($trim('<p>Hello</p><p style="text-align:center">&nbsp;</p>') === '<p>Hello</p>', 'empty paragraph with a style attribute removed');
bd_assert($trim('<p><img src="a.jpg"></p>') === '<p><img src="a.jpg"></p>', 'paragraph holding an image is not empty');
bd_assert($trim("<p>caf\xE9</p><p> </p>") === "<p>caf\xE9</p><p> </p>", 'invalid UTF-8 returned unchanged');

$src = (string) file_get_contents(dirname(__DIR__) . '/include/themecontroller.php');
bd_assert(strpos($src, "add_event_handler('loc_end_index', array(\$this, 'trimDescription'))") !== false,
  'trimDescription hooked on loc_end_index');

exit($fail === 0 ? 0 : 1);
