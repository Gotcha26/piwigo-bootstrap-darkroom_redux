<?php
/**
 * Light / dark mode (#81): single skin or light + dark pair, the visitor's mode,
 * and the core 'mode' cookie shared with the standard pages.
 *
 * Rules under test:
 * - Skins::scheme() splits the skins as themeconf.inc.php used to ($clear_skins).
 * - Single mode (the default): the admin's skin, whatever the cookie says; with
 *   the core standard pages on, the 'mode' cookie is aligned with that skin.
 * - Pair mode: the cookie picks the skin and its navbar set; without a cookie the
 *   OS preference client hint does, and becomes the cookie; with neither, the page
 *   is served light and flagged unknown (header.tpl then asks matchMedia).
 * - Visitor input (cookie, header) never selects anything but light or dark.
 * - Nothing is stored: the public side resolves in memory only.
 *
 * METHOD: the real include/config.php, include/skins.php and
 * include/themecontroller.php, with the Piwigo boundary doubled ($conf,
 * conf_update_param()). Cookie writes are observed through $_COOKIE, which the
 * controller updates with every setcookie().
 *
 * Run: php tests/test_color_mode.php
 */

// No output may reach the SAPI before the controller's headers: buffer it all.
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

$tmp = sys_get_temp_dir() . '/bd_color_' . getmypid() . '_' . uniqid();
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
use BootstrapDarkroom\Skins;
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

/** The controller's Config, as the templates will read it. */
function bd_config_of(ThemeController $controller) {
  $p = new ReflectionProperty(ThemeController::class, 'config');
  if (PHP_VERSION_ID < 80100) {
    $p->setAccessible(true);
  }
  return $p->getValue($controller);
}

/** One public request: cookie and client hint in, scheme and resolved config out. */
function bd_request($cookie, $hint = null) {
  $_COOKIE = array();
  unset($_SERVER['HTTP_SEC_CH_PREFERS_COLOR_SCHEME']);
  if ($cookie !== null) {
    $_COOKIE['mode'] = $cookie;
  }
  if ($hint !== null) {
    $_SERVER['HTTP_SEC_CH_PREFERS_COLOR_SCHEME'] = $hint;
  }
  $controller = new ThemeController();
  $scheme = $controller->resolveColorScheme();
  $m = new ReflectionMethod(ThemeController::class, 'colorSwitchData');
  if (PHP_VERSION_ID < 80100) {
    $m->setAccessible(true);
  }
  return array(
    'scheme' => $scheme,
    'config' => bd_config_of($controller),
    'switch' => $m->invoke($controller),
    'cookie' => isset($_COOKIE['mode']) ? $_COOKIE['mode'] : null,
  );
}

// --- 1. Skins: the light / dark split ---
bd_assert(Skins::scheme('bootstrap-default') === 'light', 'bootstrap-default is light');
bd_assert(Skins::scheme('bootstrap-darkroom') === 'dark', 'bootstrap-darkroom is dark');
bd_assert(Skins::scheme('material-darkroom') === 'dark', 'material-darkroom is dark');
bd_assert(Skins::scheme('material-teal') === 'light', 'material-teal is light');
bd_assert(Skins::scheme('bootswatch-cyborg') === 'dark', 'bootswatch-cyborg is dark');
bd_assert(Skins::scheme('bootswatch-yeti') === 'light', 'bootswatch-yeti is light');
bd_assert(Skins::scheme(null) === 'dark', 'no skin: dark, the former themeconf default');
$skins = array_map('basename', glob(dirname(__DIR__) . '/css/*', GLOB_ONLYDIR));
$light = array_filter($skins, function ($s) {
  return Skins::scheme($s) === 'light';
});
bd_assert(count($light) >= 20 && count($skins) - count($light) >= 6, 'both schemes have skins on disk');

// --- 2. Visitor input ---
bd_assert(Skins::normalizeMode('dark') === 'dark', 'dark is a mode');
bd_assert(Skins::normalizeMode(' "Light" ') === 'light', 'a quoted, padded hint value is read');
bd_assert(Skins::normalizeMode('auto') === null, 'auto is not a mode');
bd_assert(Skins::normalizeMode(array('dark')) === null, 'an array is not a mode');
$r = Skins::requested('dark', 'light');
bd_assert($r['mode'] === 'dark' && $r['source'] === 'cookie', 'the cookie wins over the hint');
$r = Skins::requested('garbage', 'light');
bd_assert($r['mode'] === 'light' && $r['source'] === 'hint', 'a garbage cookie falls back to the hint');
$r = Skins::requested(null, null);
bd_assert($r['mode'] === null && $r['source'] === null, 'nothing known: no mode');

// --- 3. Config defaults: single mode, today's behaviour ---
$conf = array();
$c = new Config();
bd_assert($c->color_mode === 'single', 'single mode by default');
bd_assert(!$c->isPairMode(), 'not pair mode by default');
bd_assert($c->bootstrap_theme_light === 'bootstrap-default' && $c->bootstrap_theme_dark === 'bootstrap-darkroom', 'default pair: Bootstrap Default / Darkroom');
bd_assert(!$c->isStored('color_mode'), 'nothing stored: color_mode is not stored');
$c->applyColorMode('light');
bd_assert($c->bootstrap_theme === 'material-darkroom', 'single mode: applyColorMode() changes nothing');

// --- 4. An install saved before #81 ---
$conf = array('bootstrap_darkroom' => json_encode(array('conf_version' => 22, 'bootstrap_theme' => 'bootswatch-darkly')));
$c = new Config();
bd_assert($c->bootstrap_theme === 'bootswatch-darkly' && !$c->isPairMode(), 'a row saved at 22 keeps its skin, single mode');
bd_assert($c->isStored('bootstrap_theme') && !$c->isStored('color_mode'), 'isStored() tells the stored keys from the defaults');

// --- 5. Single mode: the admin's skin, the cookie aligned for the standard pages ---
$writes = 0;
bd_store(array('bootstrap_theme' => 'bootstrap-darkroom'));
$writes = 0;
$r = bd_request('light');
bd_assert($r['scheme'] === 'dark', 'single dark skin: dark colorscheme, whatever the cookie');
bd_assert($r['config']->bootstrap_theme === 'bootstrap-darkroom', 'single: the cookie never picks the skin');
bd_assert($r['cookie'] === 'light', 'single, no standard pages: the cookie is left alone');
$r = bd_request(null);
bd_assert($r['cookie'] === null, 'single, no standard pages: no cookie is created');
$conf['use_standard_pages'] = true;
$r = bd_request('light');
bd_assert($r['cookie'] === 'dark', 'single dark skin, standard pages: the core mode cookie is set to dark (login page follows)');
bd_assert($r['switch']['toggle'] === false, 'single: no switch in the navbar');
bd_store(array('bootstrap_theme' => 'bootstrap-default'));
$conf['use_standard_pages'] = true;
$writes = 0;
$r = bd_request(null);
bd_assert($r['scheme'] === 'clear' && $r['cookie'] === 'light', 'single light skin: clear colorscheme, cookie light');

// --- 6. Pair mode: the visitor's mode picks the skin and its navbar set ---
bd_store(array(
  'color_mode' => 'pair',
  'bootstrap_theme' => 'material-teal',
  'bootstrap_theme_light' => 'bootswatch-litera',
  'bootstrap_theme_dark' => 'bootswatch-cyborg',
  'navbar_main_style_light' => 'navbar-light',
  'navbar_main_bg_light' => 'bg-light',
  'navbar_contextual_style_light' => 'navbar-light',
  'navbar_contextual_bg_light' => 'bg-light',
  'navbar_main_style_dark' => 'navbar-dark',
  'navbar_main_bg_dark' => 'bg-dark',
  'navbar_contextual_style_dark' => 'navbar-dark',
  'navbar_contextual_bg_dark' => 'bg-dark',
));
$writes = 0;
$r = bd_request('dark');
bd_assert($r['scheme'] === 'dark' && $r['config']->bootstrap_theme === 'bootswatch-cyborg', 'pair, cookie dark: the dark skin');
bd_assert($r['config']->navbar_contextual_bg === 'bg-dark', 'pair, cookie dark: the dark navbar set');
bd_assert($r['switch']['toggle'] && $r['switch']['mode'] === 'dark' && $r['switch']['next'] === 'light', 'pair, dark: the switch offers light');
bd_assert($r['switch']['known'], 'pair, cookie: mode known, no first-visit script');
$r = bd_request('light');
bd_assert($r['scheme'] === 'clear' && $r['config']->bootstrap_theme === 'bootswatch-litera', 'pair, cookie light: the light skin');
bd_assert($r['config']->navbar_main_style === 'navbar-light', 'pair, cookie light: the light navbar set');
bd_assert($r['switch']['next'] === 'dark', 'pair, light: the switch offers dark');
$r = bd_request(null, 'dark');
bd_assert($r['config']->bootstrap_theme === 'bootswatch-cyborg', 'pair, no cookie, hint dark: the dark skin');
bd_assert($r['cookie'] === 'dark', 'pair, hint: becomes the cookie');
$r = bd_request(null);
bd_assert($r['config']->bootstrap_theme === 'bootswatch-litera' && $r['switch']['known'] === false, 'pair, nothing known: served light, flagged for the first-visit script');
bd_assert($r['cookie'] === null, 'pair, nothing known: no cookie written by the server');
$r = bd_request('<script>');
bd_assert($r['config']->bootstrap_theme === 'bootswatch-litera', 'pair, garbage cookie: ignored');
bd_assert($writes === 0, 'public requests never write the configuration');

// --- 7. Pair mode with an empty slot: the single skin stays ---
bd_store(array('color_mode' => 'pair', 'bootstrap_theme' => 'material-teal', 'bootstrap_theme_dark' => ''));
$r = bd_request('dark');
bd_assert($r['config']->bootstrap_theme === 'material-teal', 'pair, empty dark slot: the single skin is kept');
bd_assert($r['switch']['mode'] === 'dark' && $r['switch']['scheme'] === 'light', 'pair, empty dark slot: data-bd-scheme tells the skin served (light), not the request (dark)');

// --- 8. data-bd-scheme, the child themes' contract: the scheme of the skin served ---
bd_store(array('bootstrap_theme' => 'bootswatch-darkly'));
$r = bd_request('light');
bd_assert($r['switch']['scheme'] === 'dark', 'single dark skin: data-bd-scheme is dark, whatever the cookie');
bd_store(array('bootstrap_theme' => 'material-teal'));
$r = bd_request(null);
bd_assert($r['switch']['scheme'] === 'light', 'single light skin: data-bd-scheme is light');
bd_store(array('color_mode' => 'pair'));
$r = bd_request('dark');
bd_assert($r['switch']['scheme'] === 'dark', 'pair, dark: data-bd-scheme is dark');
$r = bd_request('light');
bd_assert($r['switch']['scheme'] === 'light', 'pair, light: data-bd-scheme is light');
$header = file_get_contents(dirname(__DIR__) . '/template/header.tpl');
bd_assert(strpos($header, 'data-bd-scheme="{$bd_color.scheme}"') !== false, 'header.tpl writes data-bd-scheme on <html> from bd_color.scheme');

ob_end_flush();
if (is_dir($tmp)) {
  @rmdir($tmp);
}
echo $fail ? "\n$fail failure(s)\n" : "\nall passed\n";
exit($fail ? 1 : 0);
