<?php
/**
 * About page (#75): title, mention of Piwigo, legal notice, lineage of the theme
 * and of a child theme, component list.
 *
 * METHOD: the real include/about.php and include/config.php, with the Piwigo
 * boundary doubled ($conf, conf_update_param(), PHPWG_ROOT_PATH / PWG_LOCAL_DIR on
 * a temporary folder). The shared sanitizer is the hub's copy, the one the build
 * injects into include/. A child process checks the fallback without it.
 *
 * Run: php tests/test_about_page.php
 */

require_once __DIR__ . '/_hub.php';

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
$tmp = sys_get_temp_dir() . '/bd_about_' . getmypid() . '_' . uniqid();
@mkdir($tmp, 0777, true);
define('PHPWG_ROOT_PATH', $tmp . '/');
define('PWG_LOCAL_DIR', 'local/');

$conf = array();
function conf_update_param($param, $value) {
  global $conf;
  $conf[$param] = stripslashes($value);
}

require_once jm_test_hub_root() . '/include/jm-html-sanitizer.php';
require $root . '/include/config.php';

use BootstrapDarkroom\About;
use BootstrapDarkroom\Config;

// --- 1. Title ---
bd_assert(About::normalizeTitle('  Legal   <b>notice</b> ') === 'Legal notice', 'title: tags stripped, spaces collapsed, trimmed');
bd_assert(About::normalizeTitle(array('x')) === '' && About::normalizeTitle(null) === '', 'title: a non-string is the default (empty)');
bd_assert(mb_strlen(About::normalizeTitle(str_repeat('é', 200)), 'UTF-8') === About::TITLE_MAX, 'title: capped at TITLE_MAX characters, UTF-8 safe');

// --- 2. Mention of Piwigo ---
bd_assert(About::normalizePiwigoMode('original') === 'original' && About::normalizePiwigoMode('none') === 'none', 'piwigo: known modes kept');
bd_assert(About::normalizePiwigoMode('bogus') === 'original' && About::normalizePiwigoMode(null) === 'original', 'piwigo: anything else is the default, the original text');

// --- 3. Legal notice: no formatting limit, no active content ---
$rich = '<h2 style="color: red">Publisher</h2><p><strong>Jane</strong> <u>Doe</u></p>'
  . '<table><tbody><tr><td>Host</td></tr></tbody></table><blockquote>Quote</blockquote>'
  . '<p><a href="https://example.org">site</a> <img src="https://example.org/a.png" alt="a"></p>';
$clean = About::cleanNotice($rich);
foreach (array('<h2', '<strong>', '<u>', '<table>', '<td>', '<blockquote>', 'href="https://example.org"', '<img', 'color: red') as $kept) {
  bd_assert(strpos($clean, $kept) !== false, "notice: formatting kept ($kept)");
}
$evil = '<p onclick="alert(1)">Hi</p><script>alert(2)</script><iframe src="https://evil.example"></iframe>'
  . '<a href="javascript:alert(3)">x</a><form><input name="p"></form><img src="x" onerror="alert(4)">';
$out = About::cleanNotice($evil);
foreach (array('onclick', '<script', 'alert(2)', '<iframe', 'javascript:', '<form', '<input', 'onerror') as $gone) {
  bd_assert(stripos($out, $gone) === false, "notice: active content removed ($gone)");
}
bd_assert(strpos($out, 'Hi') !== false, 'notice: the text around active content is kept');
bd_assert(About::cleanNotice('<p>&nbsp;</p><p> </p>') === '' && About::cleanNotice(null) === '', 'notice: a blank notice is empty');
bd_assert(About::renderNotice('<p>ok</p><script>alert(1)</script>') !== '' && stripos(About::renderNotice('<script>alert(1)</script><p>ok</p>'), 'script') === false,
  'notice: sanitized again before display');

// --- 4. Storage through Config ---
$file = $tmp . '/local/bootstrap_darkroom/about-notice.html';
$config = new Config();
$config->fromPost(array(
  'about_title'  => addslashes('  Mentions <i>légales</i> '),
  'about_piwigo' => 'bogus',
  'about_notice' => addslashes('<p onclick="x()">Éditeur : Jane</p><script>bad()</script>'),
));
$config->save();
$stored = json_decode($conf['bootstrap_darkroom'], true);
bd_assert($stored['about_title'] === 'Mentions légales', 'config: title stored cleaned');
bd_assert($stored['about_piwigo'] === 'original', 'config: an unknown mode is stored as the default');
bd_assert(!isset($stored['about_notice']), 'config: the notice is a file, its text never in the conf row');
bd_assert(is_file($file) && strpos(file_get_contents($file), 'Éditeur') !== false
  && stripos(file_get_contents($file), 'script') === false && stripos(file_get_contents($file), 'onclick') === false,
  'config: notice file written, sanitized before storage');

$reread = new Config();
bd_assert(strpos((string) $reread->about_notice, 'Éditeur') !== false, 'config: the notice reads back from its file');

$config = new Config();
$config->fromPost(array('about_title' => '', 'about_piwigo' => 'none', 'about_notice' => '<p>&nbsp;</p>'));
$config->save();
$stored = json_decode($conf['bootstrap_darkroom'], true);
bd_assert($stored['about_title'] === null, 'config: an empty title is stored as null (default name)');
bd_assert(!file_exists($file), 'config: a blank notice deletes the file');
$config->save();
bd_assert(!file_exists($file), 'config: no empty file created when there is no notice');

// Reset keeps the typed content, resets the mention of Piwigo.
$config = new Config();
$config->fromPost(array('about_title' => 'Legal', 'about_piwigo' => 'simple', 'about_notice' => '<p>Kept</p>'));
$config->save();
$config = new Config();
$config->reset();
$stored = json_decode($conf['bootstrap_darkroom'], true);
bd_assert($stored['about_title'] === 'Legal' && is_file($file), 'reset: title and notice kept');
bd_assert($stored['about_piwigo'] === 'original', 'reset: the mention of Piwigo goes back to its default');

// A row stored before #75 (conf_version 21) gets the defaults in memory.
$conf['bootstrap_darkroom'] = json_encode(array('conf_version' => 21, 'bootstrap_theme' => 'bootswatch-cosmo'));
$old = new Config();
bd_assert($old->about_piwigo === 'original' && $old->about_title === null && $old->bootstrap_theme === 'bootswatch-cosmo',
  'config: an older row is completed with the About defaults');

// --- 5. Lineage ---
$strings = array('by' => '%1$s by %2$s', 'fork_of' => 'fork of %1$s', 'fork_of_by' => 'fork of %1$s by %2$s', 'license' => 'Licence: %s.');
$lines = About::lineageHtml(array(About::themeEntry()), $strings);
bd_assert(count($lines) === 1, 'lineage: one line for the theme');
bd_assert(strpos($lines[0], '>Bootstrap Darkroom (redux)</a> by <a href="https://julien-moreau.fr"') !== false,
  'lineage: the fork and its author, linked');
bd_assert(strpos($lines[0], 'fork of <a href="https://github.com/Piwigo/piwigo-bootstrap-darkroom"') !== false
  && strpos($lines[0], '</a> by Thomas Kuther.') !== false, 'lineage: the upstream project, linked to GitHub, and its author');
$end = '</a> by Thomas Kuther. Licence: Apache 2.0.';
bd_assert(substr($lines[0], -strlen($end)) === $end, 'lineage: the licence ends the line (' . $lines[0] . ')');

// A child theme declares itself first; its values are data.
$child = array(
  'name' => 'BC-<b>BG</b>', 'url' => 'javascript:alert(1)', 'author' => 'Jane "J" <Doe>',
  'author_url' => 'https://example.org/?a=1&b=2',
);
$lines = About::lineageHtml(array($child, array('author' => 'no name'), 'junk', About::themeEntry()), $strings);
bd_assert(count($lines) === 2, 'lineage: an entry without a name or not an array is ignored');
bd_assert(strpos($lines[0], 'BC-BG by ') === 0, 'lineage: child first, tags stripped, unsafe URL not linked (' . $lines[0] . ')');
bd_assert(strpos($lines[0], 'Jane &quot;J&quot;') !== false && strpos($lines[0], '<Doe>') === false, 'lineage: author escaped');
bd_assert(strpos($lines[0], 'href="https://example.org/?a=1&amp;b=2"') !== false, 'lineage: a safe URL is linked, escaped');
bd_assert(About::lineageHtml('nope', $strings) === array(), 'lineage: a non-list declaration gives nothing');

// Every line is presented as a theme, the child's and the parent's alike.
$lines = About::lineageHtml(array($child, About::themeEntry()), $strings + array('theme' => 'Thème : %s'));
bd_assert(count($lines) === 2 && strpos($lines[0], 'Thème : BC-BG by ') === 0 && strpos($lines[1], 'Thème : <a href=') === 0,
  'lineage: each line is introduced as a theme');
bd_assert(strpos(file_get_contents($root . '/include/themecontroller.php'), "'theme'      => l10n('bd_about_theme_line')") !== false,
  'lineage: the page passes the translated theme pattern');

// --- 6. Component families ---
$groups = About::normalizeCredits(array(
  array('title' => 'Core', 'items' => array(
    array('name' => 'Lib', 'url' => 'javascript:x', 'author' => 'A', 'license' => 'MIT'),
    array('url' => 'https://example.org'),
  )),
  array('title' => 'Empty', 'items' => array()),
  'junk',
));
bd_assert(count($groups) === 1 && count($groups[0]['items']) === 1, 'credits: empty families and nameless items dropped');
bd_assert($groups[0]['items'][0]['url'] === '', 'credits: an unsafe URL is dropped');
$credits = include $root . '/include/credits.php';
$names = array();
foreach ($credits as $family) {
  foreach ($family as $item) {
    $names[] = $item['name'];
  }
}
bd_assert(!in_array('Bootstrap Darkroom', $names, true), 'credits: the upstream project is no longer a component (it is in the lineage)');

// --- 7. Fallback without the sanitizer (clone without a build) ---
if (!is_file($root . '/include/jm-html-sanitizer.php')) {
  // A script file, not php -r: shell quoting differs between platforms.
  $child = $tmp . '/fallback.php';
  file_put_contents($child, '<?php define("PHPWG_ROOT_PATH", "x/"); require ' . var_export($root . '/include/about.php', true) . ';'
    . ' echo \BootstrapDarkroom\About::renderNotice("<b>Hi</b>\nthere<script>alert(1)</script>");');
  $render = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($child));
  bd_assert($render === "Hi<br />\ntherealert(1)", 'fallback: text only, escaped, line breaks kept (' . var_export($render, true) . ')');
} else {
  echo "skip: fallback (an injected sanitizer is present in include/)\n";
}

// --- 8. Templates and strings ---
$tpl = file_get_contents($root . '/template/about.tpl');
bd_assert(strpos($tpl, 'THEME_ABOUT') === false, 'about.tpl: the theme about.html slot is gone (child themes declare instead)');
bd_assert(preg_match("/\\{if \\\$BD_ABOUT\\.piwigo == 'original'\\}\\s*<div class=\"bd-about-piwigo\">\\{\\\$ABOUT_MESSAGE\\}/", $tpl) === 1,
  "about.tpl: Piwigo's own text only in 'original' mode");
bd_assert(strpos($tpl, '$about_msgs') !== false, 'about.tpl: the plugin slot is kept');
bd_assert(preg_match('/\{\$(item|group)\.(name|title|author|license|url)\}/', $tpl) === 0, 'about.tpl: every credit value is escaped');

$body = file_get_contents($root . '/admin/template/bd-body.tpl');
bd_assert(preg_match('/<a class="jm-btn" href="\{\$BD_ABOUT_URL\|escape:\'html\'\}" target="_blank" rel="noopener">/', $body) === 1,
  'admin: the preview button opens the public page in a new tab');
bd_assert(strpos(file_get_contents($root . '/admin/admin.inc.php'), "'BD_ABOUT_URL'         => get_root_url() . 'about.php'") !== false,
  'admin: the preview URL is the public about.php');
bd_assert(strpos(file_get_contents($root . '/include/themecontroller.php'), "l10n('bd_about_default_title')") !== false,
  'public: the default title is the translated "Legal notice"');

$used = array();
foreach (array('template/about.tpl', 'include/themecontroller.php', 'admin/template/bd-body.tpl') as $rel) {
  preg_match_all("/'(bd_(?:about|section_about|label_about|desc_about|tip_about)[a-z_]*)'/", file_get_contents($root . '/' . $rel), $m);
  $used = array_merge($used, $m[1]);
}
// Event names, not strings.
$used = array_diff(array_unique($used), array('bd_about_lineage', 'bd_about_credits'));
foreach (array('en_UK', 'fr_FR') as $code) {
  $lang = array();
  include $root . '/language/' . $code . '/theme.lang.php';
  $missing = array_diff($used, array_keys($lang));
  bd_assert(count($used) > 10 && !$missing, "strings: every About key exists in $code" . ($missing ? ' (missing: ' . implode(', ', $missing) . ')' : ''));
  foreach (array('Source', 'By', 'Issue tracker', 'bd_about_theme') as $orphan) {
    bd_assert(!array_key_exists($orphan, $lang), "strings: orphan '$orphan' removed from $code");
  }
}

// Cleanup.
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) {
  $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
}
@rmdir($tmp);

exit($fail === 0 ? 0 : 1);
