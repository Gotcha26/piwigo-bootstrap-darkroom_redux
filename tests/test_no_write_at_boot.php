<?php
/**
 * Serving a public page never writes the theme's settings row (#61).
 *
 * Config::__construct() and ThemeController::init() run on every request, the
 * first visitor's included (anonymous, crawler). Construction is a read: an
 * older conf_version or a legacy bootstrap_theme value is normalised IN MEMORY,
 * and the row is rewritten at the next explicit save().
 *
 * METHOD: the real include/config.php and include/themecontroller.php, with the
 * Piwigo boundary doubled ($conf, conf_update_param(), load_language(),
 * add_event_handler()). Every conf_update_param() call is counted.
 *
 * Run: php tests/test_no_write_at_boot.php
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

$tmp = sys_get_temp_dir() . '/bd_boot_' . getmypid() . '_' . uniqid();
@mkdir($tmp, 0777, true);
define('PHPWG_ROOT_PATH', $tmp . '/');
define('PWG_LOCAL_DIR', 'local/');
define('PHPWG_THEMES_PATH', $tmp . '/themes/');

$conf = array();
$writes = 0;
$handlers = array();
function conf_update_param($param, $value) {
  global $conf, $writes;
  // The theme slashes its JSON for the INSERT; the row read back is unslashed.
  $conf[$param] = stripslashes($value);
  $writes++;
}
function load_language() {
}
function add_event_handler($event, $callback) {
  global $handlers;
  $handlers[] = array($event, $callback);
}

require dirname(__DIR__) . '/include/config.php';
require dirname(__DIR__) . '/include/themecontroller.php';

$class = new ReflectionClass('\BootstrapDarkroom\Config');
$props = $class->getDefaultProperties();
$defaults = $props['defaults'];
$types = $props['types'];
$current = \BootstrapDarkroom\Config::CONF_VERSION;

// A row stored by an older build: one setting moved off its default, one
// holding an apostrophe, one key missing altogether (added since).
$old = array('conf_version' => $current - 1, 'logo_image_path' => "it's a logo");
foreach ($defaults as $key => $default) {
  if (!in_array($key, array('logo_image_path', 'comments_disqus_shortname'), true) && $types[$key] !== 'file') {
    $old[$key] = $default;
  }
}
$old['navbar_main_style'] = 'navbar-light';
$stored_row = json_encode($old);

// --- 1. Older conf_version, loaded by an anonymous visitor ---
$conf['bootstrap_darkroom'] = $stored_row;
$writes = 0;
$config = new \BootstrapDarkroom\Config();
bd_assert($writes === 0, 'older conf_version: zero conf_update_param() at construction (' . $writes . ')');
bd_assert($conf['bootstrap_darkroom'] === $stored_row, 'older conf_version: the stored row is untouched');
bd_assert($config->navbar_main_style === 'navbar-light', 'older conf_version: the front reads the stored value');
bd_assert($config->logo_image_path === "it's a logo", 'older conf_version: the front reads the apostrophe value');
bd_assert($config->comments_type === $defaults['comments_type'], 'older conf_version: a key missing from the row gets its default');

// --- 2. Then an explicit save() by the webmaster ---
$writes = 0;
$config->save();
$saved = json_decode($conf['bootstrap_darkroom'], true);
bd_assert($writes === 1, 'save(): one write');
bd_assert(is_array($saved) && $saved['conf_version'] === $current, 'save(): the row is rewritten at the current conf_version');
bd_assert($saved['logo_image_path'] === "it's a logo", 'save(): the apostrophe survives the round trip');
bd_assert($saved['navbar_main_style'] === 'navbar-light', 'save(): the stored value survives');
$writes = 0;
new \BootstrapDarkroom\Config();
bd_assert($writes === 0, 'current conf_version: no write at construction either');

// --- 3. Legacy bootstrap_theme value: normalised in memory, no write at boot ---
foreach (array('darkroom', 'material', 'bootswatch') as $legacy) {
  $row = $saved;
  $row['bootstrap_theme'] = $legacy;
  $conf['bootstrap_darkroom'] = json_encode($row);
  $before = $conf['bootstrap_darkroom'];
  $writes = 0;
  $handlers = array();
  $controller = new \BootstrapDarkroom\ThemeController();
  $controller->init();
  bd_assert($writes === 0, "legacy '$legacy': zero conf_update_param() in init() (" . $writes . ')');
  bd_assert($conf['bootstrap_darkroom'] === $before, "legacy '$legacy': the stored row is untouched");

  $warns = false;
  foreach ($handlers as $h) {
    if ($h[1][1] === 'showUpgradeWarning') {
      $warns = true;
    }
  }
  $cfg = (new ReflectionProperty($controller, 'config'))->getValue($controller);
  bd_assert($cfg->bootstrap_theme === 'bootstrap-darkroom', "legacy '$legacy': normalised in memory");
  bd_assert($warns, "legacy '$legacy': the upgrade notice is still registered");
}

// Cleanup.
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) {
  $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
}
@rmdir($tmp);

exit($fail === 0 ? 0 : 1);
