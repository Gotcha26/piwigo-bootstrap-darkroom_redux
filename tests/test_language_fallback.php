<?php
/**
 * English fallback of the theme's language catalogues (piwigo_local#1275).
 *
 * Why: the fallback of load_language() is the gallery's DEFAULT language, not
 * en_UK. With a default of en_GB or en_US, a user whose language is not shipped
 * saw raw keys. Every call loading the theme's or a plugin's catalogue must
 * therefore pass force_fallback => 'en_UK'.
 *
 * METHOD: static scan of every load_language() call in the theme's PHP sources
 * (the only calls exempt are the gallery's own local/ override, which is
 * deliberately no_fallback), plus a parity check of the shipped catalogues:
 * en_UK is the fallback, so it must hold every key fr_FR holds.
 *
 * Run: php tests/test_language_fallback.php
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

$root = dirname(__DIR__);
$files = array(
  'admin/admin.inc.php',
  'include/themecontroller.php',
  'themeconf.inc.php',
);

$calls = 0;
foreach ($files as $rel) {
  $src = file_get_contents($root . '/' . $rel);
  if ($src === false) {
    bd_assert(false, "$rel is readable");
    continue;
  }
  preg_match_all('/load_language\s*\((?:[^;]*?)\)\s*;/s', $src, $m);
  foreach ($m[0] as $call) {
    $calls++;
    $label = $rel . ': ' . preg_replace('/\s+/', ' ', $call);
    if (strpos($call, "'local'") !== false || strpos($call, '"local"') !== false) {
      bd_assert(strpos($call, 'no_fallback') !== false, "$label (local override, no_fallback)");
    } else {
      bd_assert(preg_match('/force_fallback[\'"]?\s*=>\s*[\'"]en_UK[\'"]/', $call) === 1, "$label forces the en_UK fallback");
    }
  }
}
bd_assert($calls >= 4, "the scan found the theme's load_language() calls ($calls)");

$catalogue = function ($lang) use ($root) {
  $f = $root . '/language/' . $lang . '/theme.lang.php';
  if (!is_file($f)) {
    return null;
  }
  $lang = array();
  include $f;
  return array_keys($lang);
};

$en = $catalogue('en_UK');
$fr = $catalogue('fr_FR');
bd_assert($en !== null && count($en) > 0, 'en_UK theme.lang.php exists and defines keys');
bd_assert($fr !== null && count($fr) > 0, 'fr_FR theme.lang.php exists and defines keys');
if ($en && $fr) {
  // 1:1 with fr_FR, including keys that are the English text themselves.
  $missing = array_diff($fr, $en);
  bd_assert(!$missing, 'en_UK holds every key of fr_FR' . ($missing ? ' (missing: ' . implode(', ', $missing) . ')' : ''));
  $untranslated = array_diff($en, $fr);
  bd_assert(!$untranslated, 'fr_FR holds every key of en_UK' . ($untranslated ? ' (missing: ' . implode(', ', $untranslated) . ')' : ''));
}

exit($fail ? 1 : 0);
