<?php
/**
 * Text contrast (#83): the text_contrast setting and the override files it loads.
 *
 * Rules under test:
 * - Config::clampContrast() turns any input into an integer 0-100.
 * - Default 0: no override file, nothing for header.tpl to emit.
 * - A row stored before the setting existed (conf_version 23) reads 0.
 * - A stored value is clamped again when read: a tampered row never reaches the
 *   <style> element raw.
 * - The files are those of the skin served to the request, after the light / dark
 *   resolution (#81).
 * - Nothing is written to the conf table by these reads.
 *
 * METHOD: the real include/config.php and include/themecontroller.php, the real
 * generated files under css/, with the Piwigo boundary doubled ($conf,
 * conf_update_param()).
 *
 * Run: php tests/test_text_contrast.php
 */

ob_start();

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

$tmp = sys_get_temp_dir() . '/bd_contrast_' . getmypid() . '_' . uniqid();
@mkdir($tmp, 0777, true);
define('PHPWG_ROOT_PATH', $tmp . '/');
define('PWG_LOCAL_DIR', 'local/');

$conf = array();
$writes = 0;
function conf_update_param($param, $value) {
  global $conf, $writes;
  $conf[$param] = stripslashes($value);
  $writes++;
}

require dirname(__DIR__) . '/include/config.php';
require dirname(__DIR__) . '/include/themecontroller.php';

use BootstrapDarkroom\Config;
use BootstrapDarkroom\ThemeController;

/** Stores the given settings as a saved row, the rest at their defaults. */
function bd_store(array $settings) {
  global $conf;
  $conf = array();
  $c = new Config();
  foreach ($settings as $k => $v) {
    $c->$k = $v;
  }
  $c->save();
}

/** contrastData() of one public request, with an optional 'mode' cookie. */
function bd_contrast($cookie = null) {
  global $writes;
  $_COOKIE = array();
  if ($cookie !== null) {
    $_COOKIE['mode'] = $cookie;
  }
  $writes = 0;
  $controller = new ThemeController();
  $controller->resolveColorScheme();
  return $controller->contrastData();
}

function bd_ends_with($haystack, $needle) {
  return is_string($haystack) && substr($haystack, -strlen($needle)) === $needle;
}

// --- 1. Clamping ---
bd_assert(Config::clampContrast('250') === 100, "'250' is clamped to 100");
bd_assert(Config::clampContrast('-3') === 0, "'-3' is clamped to 0");
bd_assert(Config::clampContrast('abc') === 0, "'abc' reads 0");
bd_assert(Config::clampContrast('42.6') === 43, "'42.6' rounds to 43");
bd_assert(Config::clampContrast(null) === 0, 'null reads 0');
bd_assert(Config::clampContrast(60) === 60, '60 is kept');

// --- 2. Default: off ---
$conf = array();
$c = new Config();
bd_assert($c->text_contrast === 0, 'text_contrast is 0 by default');
$d = bd_contrast();
bd_assert($d['level'] === 0 && $d['css'] === null && $d['theme_css'] === null, 'off by default: no file');

// --- 3. A row saved before #83 ---
$conf = array('bootstrap_darkroom' => json_encode(array('conf_version' => 23, 'bootstrap_theme' => 'bootstrap-darkroom')));
$c = new Config();
bd_assert($c->text_contrast === 0, 'a conf_version 23 row reads text_contrast 0');
bd_assert(Config::CONF_VERSION === 24, 'CONF_VERSION is 24');

// --- 4. Saving goes through the clamp ---
bd_store(array('bootstrap_theme' => 'bootstrap-darkroom', 'text_contrast' => '250'));
$stored = json_decode($conf['bootstrap_darkroom'], true);
bd_assert($stored['text_contrast'] === 100, 'a posted 250 is stored as 100');

// --- 5. Single skin, contrast on ---
bd_store(array('bootstrap_theme' => 'bootstrap-darkroom', 'text_contrast' => 60));
$d = bd_contrast();
bd_assert($d['level'] === 60, 'level 60');
bd_assert($d['css'] === 'themes/bootstrap_darkroom/css/bootstrap-darkroom/contrast.css', 'skin overrides of the served skin');
bd_assert($d['theme_css'] === 'themes/bootstrap_darkroom/css/bootstrap-darkroom/contrast-theme.css', 'theme.css overrides of the served skin');
bd_assert($writes === 0, 'reading writes nothing');

// --- 6. A tampered row ---
$conf = array('bootstrap_darkroom' => json_encode(array('conf_version' => 24, 'bootstrap_theme' => 'bootstrap-darkroom', 'text_contrast' => '250</style>')));
$d = bd_contrast();
bd_assert($d['level'] === 0, 'a non-numeric stored value reads 0');
$conf = array('bootstrap_darkroom' => json_encode(array('conf_version' => 24, 'bootstrap_theme' => 'bootstrap-darkroom', 'text_contrast' => 250)));
$d = bd_contrast();
bd_assert($d['level'] === 100, 'a stored 250 reads 100');

// --- 7. Light / dark pair: the served skin's files ---
bd_store(array(
  'color_mode' => 'pair',
  'bootstrap_theme_light' => 'bootstrap-default',
  'bootstrap_theme_dark' => 'bootstrap-darkroom',
  'text_contrast' => 30,
));
$d = bd_contrast('light');
bd_assert(bd_ends_with($d['css'], 'css/bootstrap-default/contrast.css'), 'pair, light: Bootstrap Default overrides');
$d = bd_contrast('dark');
bd_assert(bd_ends_with($d['css'], 'css/bootstrap-darkroom/contrast.css'), 'pair, dark: Darkroom overrides');

// --- 8. A skin without generated files ---
bd_store(array('bootstrap_theme' => 'no-such-skin', 'text_contrast' => 50));
$d = bd_contrast();
bd_assert($d['css'] === null && $d['theme_css'] === null, 'missing files: nothing to load');

@rmdir($tmp);
$out = ob_get_clean();
echo $out;
echo $fail ? "\n$fail failure(s)\n" : "\nAll passed\n";
exit($fail ? 1 : 0);
