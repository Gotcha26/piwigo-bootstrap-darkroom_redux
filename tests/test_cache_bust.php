<?php
/**
 * Cache-busting of the theme's stylesheets (#80).
 *
 * Rule under test: a stylesheet of THIS theme gets its mtime as version; core
 * assets, other themes, merged files and unknown files keep the URL the core built.
 *
 * Run: php tests/test_cache_bust.php
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

require_once dirname(__DIR__) . '/include/assets.php';

$prefix = 'themes/' . basename(dirname(__DIR__)) . '/';
$mtime = filemtime(dirname(__DIR__) . '/theme.css');
$base = 'https://example.org/';

$own = (object) array('path' => $prefix . 'theme.css');
bd_assert(bd_cache_bust_combined_css($base . $own->path . '?v16.5.0', $own) === $base . $own->path . '?v' . $mtime,
  'own theme.css: version replaced by the file mtime');

$coreAsset = (object) array('path' => 'themes/default/theme.css');
bd_assert(bd_cache_bust_combined_css($base . 'x.css?v16.5.0', $coreAsset) === $base . 'x.css?v16.5.0',
  'another theme: URL untouched');

$merged = (object) array('path' => '_data/combined/abc123.css');
bd_assert(bd_cache_bust_combined_css($base . 'm.css?v1', $merged) === $base . 'm.css?v1',
  'merged file: URL untouched');

$missing = (object) array('path' => $prefix . 'nope.css');
bd_assert(bd_cache_bust_combined_css($base . 'n.css?v16.5.0', $missing) === $base . 'n.css?v16.5.0',
  'unknown file: URL untouched');

bd_assert(bd_cache_bust_combined_css('u.css?v1', null) === 'u.css?v1', 'no combinable: URL untouched');

exit($fail ? 1 : 0);
