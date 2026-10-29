<?php
/**
 * Child process of test_lineage.php (leading "_": not a suite).
 *
 * Replays ONE request through the real include/lineage.php. IN_ADMIN is a
 * constant, so every scenario runs in its own process.
 *
 * Environment (set by the suite):
 *   BD_L_OUT         file where the events seen by the doubles are recorded (JSON)
 *   BD_L_IN_ADMIN    "1" defines IN_ADMIN (the script is admin.php), "0" leaves it undefined
 *   BD_L_ADMIN       "1" for an administrator
 *   BD_L_WEBMASTER   "1" for the webmaster
 *   BD_L_CONF        the $conf rows, as JSON
 *   BD_L_DB_VERSION  version column of the themes row ("" = no row)
 *   BD_L_FS_VERSION  Version: header of the theme files
 *   BD_L_PURGE_FAILS "1": the purge throws
 *   BD_L_MODE        "apply" (default: the admin page), "notify" (a public page) or "activate"
 *
 * Doubles: only the Piwigo boundary (rights, config write, SQL read, template
 * purge, language, page). The guard, the classification and the actions are the
 * REAL ones. is_admin() / is_webmaster() default to fail-closed.
 */

$events = array('writes' => array(), 'queries' => 0, 'purges' => 0, 'infos' => array());
register_shutdown_function(function () use (&$events) {
  file_put_contents(getenv('BD_L_OUT'), json_encode($events));
});

if (getenv('BD_L_IN_ADMIN') === '1') {
  define('IN_ADMIN', true);
}
define('PHPWG_ROOT_PATH', sys_get_temp_dir() . '/');
define('THEMES_TABLE', 'piwigo_themes');

function is_admin() {
  return getenv('BD_L_ADMIN') === '1';
}
function is_webmaster() {
  return getenv('BD_L_WEBMASTER') === '1';
}
function l10n($key) {
  return $key === 'bd_lineage_pending' ? $key . ' %s' : $key;
}
function get_root_url() {
  return '/gallery/';
}
function conf_update_param($param, $value) {
  global $events;
  $events['writes'][$param] = stripslashes($value);
}
function pwg_query($sql) {
  global $events;
  $events['queries']++;
  $v = getenv('BD_L_DB_VERSION');
  return $v === '' ? array() : array(array('version' => $v));
}
function pwg_db_fetch_assoc(&$rows) {
  return array_shift($rows);
}
function pwg_db_real_escape_string($s) {
  return addslashes($s);
}

class BdFakeTemplate {
  public function delete_compiled_templates() {
    global $events;
    if (getenv('BD_L_PURGE_FAILS') === '1') {
      throw new RuntimeException('purge failed');
    }
    $events['purges']++;
  }
}
$template = new BdFakeTemplate();
$page = array('infos' => array());
$conf = json_decode(getenv('BD_L_CONF'), true);

$themeconf = sys_get_temp_dir() . '/bd_lineage_themeconf_' . getmypid() . '.php';
file_put_contents($themeconf, "<?php\n/*\nTheme Name: X\nVersion: " . getenv('BD_L_FS_VERSION') . "\n*/\n");

require dirname(__DIR__) . '/include/lineage.php';
switch (getenv('BD_L_MODE')) {
  case 'notify':
    \BootstrapDarkroom\Lineage::notifyPending($themeconf);
    break;
  case 'activate':
    \BootstrapDarkroom\Lineage::recordActivation($themeconf);
    break;
  default:
    \BootstrapDarkroom\Lineage::apply($themeconf);
}

$events['infos'] = $page['infos'];
@unlink($themeconf);
