<?php
/**
 * Child process of test_ws_admin_dispatch.php (leading "_": not a suite).
 *
 * Replays in CLI the request the browser sends to the theme's admin page: it
 * INCLUDES the real admin/admin.inc.php of a sandbox copy of the theme, under a
 * simulated POST, while the VISITOR's theme is another one. One process per
 * scenario: the dispatcher ends with exit.
 *
 * Environment (set by the suite):
 *   BD_T_ROOT       sandbox root (its include/ holds the hub commons)
 *   BD_T_CORE       fake Piwigo core (include/ws_core.inc.php defines PwgError)
 *   BD_T_OUT        file where pwg_set_session_var() records what it is asked to store
 *   BD_T_POST       the $_POST, as JSON
 *   BD_T_WEBMASTER  "0" for an administrator who is not the webmaster
 *
 * Doubles: only the Piwigo boundary (token, rights, language, URL, session).
 * admin.inc.php loads the commons itself; the dispatcher, its CSRF guard, the
 * method table and the callbacks are the REAL ones.
 */

$root = getenv('BD_T_ROOT');
define('PHPWG_ROOT_PATH', rtrim(getenv('BD_T_CORE'), '/') . '/');

function get_pwg_token() {
  return 'TOKEN_OK';
}
function is_webmaster() {
  return getenv('BD_T_WEBMASTER') !== '0';
}
function is_admin() {
  return true;
}
function load_language() {
}
function get_root_url() {
  return '/';
}
function l10n($k) {
  return $k;
}
function pwg_set_session_var($var, $value) {
  file_put_contents(getenv('BD_T_OUT'), $var . '=' . $value);
  return true;
}
function pwg_get_session_var($var, $default = null) {
  return $default;
}

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = json_decode(getenv('BD_T_POST'), true);

$template = new class {
  public function __call($name, $args) {
  }
};
// Visitor's theme: NOT bootstrap_darkroom. That is the whole point of the test.
$user   = array('theme' => 'bcbg', 'language' => 'en_UK');
$themes = (object) array('fs_themes' => array('bootstrap_darkroom' => array('version' => '0.0.0-test')));

include $root . '/admin/admin.inc.php';

// Reached only if the dispatcher did not answer and exit: the page renders.
echo "\nRENDER_REACHED\n";
