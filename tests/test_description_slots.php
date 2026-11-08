<?php
/**
 * Plugin slots framing the album description (#73).
 *
 * Rule under test: template/index.tpl renders PLUGIN_INDEX_CONTENT_BEFORE_DESCRIPTION right
 * before the description block and PLUGIN_INDEX_CONTENT_AFTER_DESCRIPTION right after it,
 * before #content — both OUTSIDE the {if !empty($CONTENT_DESCRIPTION)} test, so a plugin block
 * shows even for an album without description. Plugins (OSM_redux) detect the slots by reading
 * this template, comments stripped: the variables must appear in code, not only in a comment.
 *
 * Run: php tests/test_description_slots.php
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

$tpl = (string) file_get_contents(dirname(__DIR__) . '/template/index.tpl');
$code = preg_replace('/\{\*.*?\*\}/s', '', $tpl);

$before = strpos($code, '{if !empty($PLUGIN_INDEX_CONTENT_BEFORE_DESCRIPTION)}{$PLUGIN_INDEX_CONTENT_BEFORE_DESCRIPTION}{/if}');
$after = strpos($code, '{if !empty($PLUGIN_INDEX_CONTENT_AFTER_DESCRIPTION)}{$PLUGIN_INDEX_CONTENT_AFTER_DESCRIPTION}{/if}');
$descIf = strpos($code, '{if !empty($CONTENT_DESCRIPTION)}');
$descDiv = strpos($code, '<div id="content-description"');
$content = strpos($code, '<div id="content"');

bd_assert($before !== false, 'BEFORE_DESCRIPTION slot rendered (outside comments)');
bd_assert($after !== false, 'AFTER_DESCRIPTION slot rendered (outside comments)');
bd_assert($descIf !== false && $descDiv !== false && $content !== false, 'description block and #content found');
bd_assert($before !== false && $before < $descIf, 'BEFORE slot sits before the description test (shown without description)');
bd_assert($after !== false && $after > $descDiv && $after < $content, 'AFTER slot sits between the description and #content');
if ($after !== false && $descDiv !== false) {
  $between = substr($code, $descDiv, $after - $descDiv);
  bd_assert(substr_count($between, '{if') === substr_count($between, '{/if}') - 1,
    'AFTER slot sits after the {/if} closing the description test');
}
bd_assert(strpos($code, 'PLUGIN_INDEX_CONTENT_BEGIN') !== false, 'PLUGIN_INDEX_CONTENT_BEGIN kept');

exit($fail === 0 ? 0 : 1);
