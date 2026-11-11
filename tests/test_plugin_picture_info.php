<?php
/**
 * Plugin rows in the picture information panel (PLUGIN_PICTURE_INFO).
 *
 * Rule under test: each of the three variants (cards, sidebar, tabs) loops over
 * PLUGIN_PICTURE_INFO right after the "Posted on" block (#datepost) and before #visits.
 * A row is {id, label, value}: id and label go through |escape, value is rendered as is
 * (HTML already safe, provided by the plugin). Nothing is emitted when the variable is
 * undefined or empty. Plugins (community_redux) rely on this generic contract.
 *
 * Run: php tests/test_plugin_picture_info.php
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

$variants = array(
  'cards' => array(
    'file' => 'picture_info_cards.tpl',
    'markup' => '<div id="{$row.id|escape}" class="imageInfo"><dl class="row mb-0"><dt class="col-sm-5">{$row.label|escape}</dt><dd class="col-sm-7">{$row.value}</dd></dl></div>',
  ),
  'sidebar' => array(
    'file' => 'picture_info_sidebar.tpl',
    'markup' => '<div id="{$row.id|escape}" class="imageInfo"><dt>{$row.label|escape}</dt><dd>{$row.value}</dd></div>',
  ),
  'tabs' => array(
    'file' => 'picture_info_tabs.tpl',
    'markup' => '<tr><th scope="row">{$row.label|escape}</th><td><div id="{$row.id|escape}" class="imageInfo">{$row.value}</div></td></tr>',
  ),
);

foreach ($variants as $name => $v) {
  $tpl = (string) file_get_contents(dirname(__DIR__) . '/template/' . $v['file']);
  $code = preg_replace('/\{\*.*?\*\}/s', '', $tpl);
  $flat = preg_replace('/>\s+</', '><', $code);

  $date = strpos($code, 'id="datepost"');
  $visits = strpos($code, 'id="visits"');
  $guard = strpos($code, '{if !empty($PLUGIN_PICTURE_INFO)}');
  $loop = strpos($code, '{foreach from=$PLUGIN_PICTURE_INFO item=row}');
  $end = strpos($code, '{/foreach}', (int) $loop);

  bd_assert($guard !== false && $loop !== false, "$name: PLUGIN_PICTURE_INFO guarded loop present (outside comments)");
  bd_assert($date !== false && $visits !== false && $guard > $date && $loop < $visits,
    "$name: loop sits after #datepost and before #visits");
  bd_assert($loop !== false && $end !== false && $end < $visits, "$name: loop closed before #visits");
  bd_assert(strpos($flat, $v['markup']) !== false, "$name: row markup as specified (id and label escaped, value raw)");
  bd_assert(substr_count($code, '{if') === substr_count($code, '{/if}'), "$name: {if} balanced");
  bd_assert(substr_count($code, '{foreach') === substr_count($code, '{/foreach}'), "$name: {foreach} balanced");
}

exit($fail === 0 ? 0 : 1);
