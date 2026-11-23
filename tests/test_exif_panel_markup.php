<?php
/**
 * EXIF panel of the lightbox (#90): templates and translations.
 *
 * Rules under test:
 * - _photoswipe_div.tpl holds #pswp-exif as a direct child of .pswp, after
 *   .pswp__scroll-wrap (PhotoSwipe's gestures are bound to the scroll-wrap, so the
 *   panel's clicks never reach them), hidden until the item has an image id.
 * - The #89 sidebar and the EXIF panel share the .bd-tab-panel geometry.
 * - Both maintained languages define the panel's strings.
 *
 * Run: php tests/test_exif_panel_markup.php
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
function bd_read($rel) {
  global $root;
  return (string) file_get_contents($root . '/' . $rel);
}

// --- 1. Panel markup in the lightbox ---
$div = bd_read('template/_photoswipe_div.tpl');
$panel = strpos($div, 'id="pswp-exif"');
$wrapOpen = strpos($div, 'class="pswp__scroll-wrap"');
$lastClose = strrpos($div, '</div>');
bd_assert($panel !== false, 'panel #pswp-exif present');
bd_assert($panel !== false && $wrapOpen !== false && $panel > $wrapOpen && $panel < $lastClose, 'panel after the scroll-wrap, inside .pswp');

// Depth of the panel: it must sit at depth 1 (direct child of .pswp).
$before = substr($div, 0, (int) strrpos(substr($div, 0, (int) $panel), '<div'));
$depth = preg_match_all('/<div\b/', $before) - substr_count($before, '</div>');
bd_assert($depth === 1, "panel is a direct child of .pswp (depth $depth)");

bd_assert(strpos($div, 'class="bd-tab-panel pswp-exif d-none"') !== false, 'panel classes, hidden by default');
bd_assert(strpos($div, 'aria-controls="pswp-exif-content"') !== false && strpos($div, 'id="pswp-exif-content"') !== false, 'toggle controls the content');
bd_assert(strpos($div, 'aria-expanded="false"') !== false, 'toggle starts collapsed');
bd_assert(strpos($div, 'data-ws="{$ROOT_URL}ws.php"') !== false, 'web-service URL given to the script');
bd_assert(strpos($div, "data-empty=\"{'No EXIF data for this photo'|translate|escape}\"") !== false, 'empty message');
bd_assert(strpos($div, "data-error=\"{'EXIF data could not be loaded'|translate|escape}\"") !== false, 'error message');
bd_assert(strpos($div, "aria-label=\"{'EXIF data (i)'|translate|escape}\"") !== false, 'toggle accessible name');
foreach (array('pswp-exif__title', 'pswp-exif__summary', 'pswp-exif__table', 'pswp-exif__status') as $cls) {
  bd_assert(strpos($div, 'class="' . $cls . '"') !== false, "container .$cls");
}

// --- 2. Shared geometry ---
$side = bd_read('template/picture_info_sidebar.tpl');
bd_assert(strpos($side, '<div id="sidebar" class="bd-tab-panel">') !== false, 'sidebar carries .bd-tab-panel');
$css = bd_read('theme.css');
bd_assert(strpos($css, '.bd-tab-panel.open') !== false, 'open state on the shared class');
bd_assert(strpos($css, '#sidebar.open') === false, 'open state no longer duplicated on #sidebar');
bd_assert(strpos($css, '.pswp-exif__table') !== false && strpos($css, 'column-count: 2') !== false, 'two-column EXIF table');

// --- 3. Translations ---
foreach (array('en_UK', 'fr_FR') as $l) {
  $lang = array();
  include $root . "/language/$l/theme.lang.php";
  foreach (array('EXIF data (i)', 'No EXIF data for this photo', 'EXIF data could not be loaded') as $k) {
    bd_assert(isset($lang[$k]) && $lang[$k] !== '', "$l defines '$k'");
  }
}

// --- 4. Wiring to PhotoSwipe ---
// Each lightbox anchor carries the image id; the items expose it; the panel is
// attached to every instance right after init().
$anchors = array(
  'template/index.tpl' => 1,
  'template/picture.tpl' => 2,
);
foreach ($anchors as $file => $n) {
  $tpl = bd_read($file);
  $all = preg_match_all('/<a\b[^>]*data-index="\{\$idx\}"[^>]*>/', $tpl, $m);
  $with = count(preg_grep('/data-image-id="\{\$thumbnail\.id\}"/', $m[0]));
  bd_assert($all === $n && $with === $n, "$file: $with/$all lightbox anchors carry data-image-id");
}

$js = bd_read('template/_photoswipe_js.tpl');
bd_assert(substr_count($js, "imageId: \$(this).data('image-id')") === 2, 'items expose imageId (video and image)');
$init = strpos($js, 'photoSwipe.init();');
$attach = strpos($js, 'BdExifPanel.attach(photoSwipe);');
bd_assert($init !== false && $attach !== false && $attach > $init, 'panel attached after photoSwipe.init()');
bd_assert(strpos($js, "path='themes/bootstrap_darkroom/js/exif-panel.js'") !== false || strpos($js, 'path="themes/bootstrap_darkroom/js/exif-panel.js"') !== false, 'exif-panel.js combined');
bd_assert(preg_match('/\{footer_script[^}]*require=["\']bd\.exif-panel["\']/', $js) === 1, 'footer script requires bd.exif-panel');

exit($fail ? 1 : 0);
