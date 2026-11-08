<?php
/**
 * Grid / list view: default view, visitor toggle, imposed view (#71).
 *
 * Rule under test: the visitor's 'view' cookie is honoured if and only if the
 * toggle is shown. Toggle hidden = the default view is imposed and the cookie is
 * ignored. Toggle shown = the cookie wins; the default only serves when there is
 * none, and a default of 'grid' with no cookie stays '' (the markup the theme has
 * always produced for a visitor who never clicked).
 *
 * METHOD: the real include/config.php, with the Piwigo boundary doubled
 * ($conf, conf_update_param()). Config::effectiveView() is what the templates read
 * through the bd_view variable.
 *
 * Run: php tests/test_view_mode.php
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

$tmp = sys_get_temp_dir() . '/bd_view_' . getmypid() . '_' . uniqid();
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

/** A Config holding the given view settings, the rest at its defaults. */
function bd_config_with($default, $toggle) {
  global $conf;
  $conf = array();
  $c = new \BootstrapDarkroom\Config();
  $c->view_default = $default;
  $c->view_toggle = $toggle;
  return $c;
}

// --- 1. Defaults reproduce today's behaviour ---
$conf = array();
$c = new \BootstrapDarkroom\Config();
bd_assert($c->view_default === 'grid', 'default view is grid');
bd_assert($c->view_toggle === true, 'toggle is shown by default');
bd_assert($c->effectiveView(null) === '', 'defaults, no cookie: nothing chosen, as before');
bd_assert($c->effectiveView('list') === 'list', 'defaults, cookie list: list, as before');
bd_assert($c->effectiveView('grid') === 'grid', 'defaults, cookie grid: grid, as before');

// --- 2. Toggle shown: the cookie wins over the default ---
$c = bd_config_with('list', true);
bd_assert($c->effectiveView(null) === 'list', 'shown, default list, no cookie: list');
bd_assert($c->effectiveView('grid') === 'grid', 'shown, default list, cookie grid: the visitor wins');
bd_assert($c->effectiveView('list') === 'list', 'shown, default list, cookie list: list');
$c = bd_config_with('grid', true);
bd_assert($c->effectiveView('list') === 'list', 'shown, default grid, cookie list: the visitor wins');
bd_assert($c->effectiveView('') === '', 'shown, default grid, empty cookie: treated as no cookie');

// --- 3. Toggle hidden: imposed, the cookie is ignored ---
$c = bd_config_with('list', false);
bd_assert($c->effectiveView(null) === 'list', 'hidden, default list, no cookie: list');
bd_assert($c->effectiveView('grid') === 'list', 'hidden, default list, cookie grid: ignored, list imposed');
$c = bd_config_with('grid', false);
bd_assert($c->effectiveView(null) === 'grid', 'hidden, default grid, no cookie: grid, explicit');
bd_assert($c->effectiveView('list') === 'grid', 'hidden, default grid, cookie list: ignored, grid imposed');

// --- 4. Garbage never breaks the rule ---
$c = bd_config_with('nonsense', true);
bd_assert($c->effectiveView(null) === '', 'unknown default view falls back to grid');
bd_assert($c->effectiveView('whatever') === 'grid', 'shown, unknown cookie value: grid, as the templates always read it');
$c = bd_config_with(null, false);
bd_assert($c->effectiveView('list') === 'grid', 'hidden, empty default: grid imposed');

// --- 5. Save round trip ---
$c = bd_config_with('list', false);
$c->save();
$again = new \BootstrapDarkroom\Config();
bd_assert($again->view_default === 'list' && $again->view_toggle === false, 'both settings survive save() and a reload');
bd_assert($again->effectiveView('grid') === 'list', 'the reloaded config still imposes the view');

// --- 6. A row stored before the options existed: defaults, no write ---
$writes = 0;
$conf = array('bootstrap_darkroom' => json_encode(array('conf_version' => 20, 'navbar_main_style' => 'navbar-light')));
$old = new \BootstrapDarkroom\Config();
bd_assert($old->view_default === 'grid' && $old->view_toggle === true, 'an older row gets the default view settings');
bd_assert($old->navbar_main_style === 'navbar-light', 'an older row keeps its stored values');
bd_assert($writes === 0, 'loading an older row writes nothing');

// --- 7. A hidden toggle posted by the admin form is stored ---
$conf = array();
$c = new \BootstrapDarkroom\Config();
$c->fromPost(array('view_default' => 'list'));
bd_assert($c->view_toggle === false, 'an unchecked toggle (absent from the POST) means hidden');
bd_assert($c->view_default === 'list', 'the posted default view is stored');

exit($fail ? 1 : 0);
