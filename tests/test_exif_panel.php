<?php
/**
 * EXIF panel of the lightbox (#90): the server side.
 *
 * Rules under test:
 * - Exif::rows() mirrors the core include/picture_metadata.inc.php: rows in the
 *   order of $conf['show_exif_fields'], label from $lang['exif_field_*'] (the part
 *   after ';' for compound fields), absent fields and array values skipped.
 * - Exif::forImage() returns 404 for an image the visitor cannot see, an empty
 *   list when EXIF display is off or the file is missing (no warning), the rows
 *   otherwise, with the identity mapping the core passes to get_exif_data().
 * - ThemeController::registerWsMethods() declares bd.images.getExif.
 *
 * METHOD: the real include/exif.php and include/themecontroller.php, with the
 * Piwigo boundary doubled (query, visibility condition, SrcImage, get_exif_data).
 *
 * Run: php tests/test_exif_panel.php
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

$bd_root = sys_get_temp_dir() . '/bd_exif_' . getmypid() . '_' . uniqid();
@mkdir($bd_root, 0777, true);
define('PHPWG_ROOT_PATH', $bd_root . '/');
// ws.php never loads the core file that defines get_exif_data(): forImage() must
// include it itself. This stand-in only records that it was included.
@mkdir($bd_root . '/include', 0777, true);
file_put_contents($bd_root . '/include/functions_metadata.inc.php', '<?php $GLOBALS[\'bd_metadata_included\'] = true;');
$bd_metadata_included = false;
define('PWG_LOCAL_DIR', 'local/');
define('IMAGES_TABLE', 'piwigo_images');
define('WS_TYPE_ID', 5);

$conf = array();
$lang = array();

// --- Piwigo boundary doubles ---
$bd_rows = array();
$bd_queries = array();
$bd_exif = array();
$bd_exif_calls = array();
$bd_path = '';

function conf_update_param($param, $value) {
  global $conf;
  $conf[$param] = stripslashes($value);
}
function l10n($s) {
  return $s;
}
function get_sql_condition_FandF($filters, $prefix) {
  return $prefix . ' visible';
}
function pwg_query($q) {
  global $bd_queries, $bd_rows;
  $bd_queries[] = $q;
  return $bd_rows;
}
function pwg_db_num_rows($r) {
  return count($r);
}
function pwg_db_fetch_assoc($r) {
  return isset($r[0]) ? $r[0] : null;
}
function get_exif_data($path, $map) {
  global $bd_exif, $bd_exif_calls;
  $bd_exif_calls[] = array($path, $map);
  return $bd_exif;
}
$bd_mime = false;
$bd_triggers = array();
$bd_trigger_override = null;
function trigger_change($event, $value) {
  global $bd_triggers, $bd_trigger_override;
  $args = func_get_args();
  $bd_triggers[] = $args;
  if ($bd_trigger_override !== null && isset($bd_trigger_override[$event])) {
    return $bd_trigger_override[$event];
  }
  return $value;
}
class SrcImage {
  public function __construct($row) {
  }
  public function get_path() {
    global $bd_path;
    return $bd_path;
  }
  public function is_mimetype() {
    global $bd_mime;
    return $bd_mime;
  }
}
class PwgError {
  public $code;
  public function __construct($code, $msg) {
    $this->code = $code;
  }
}

require dirname(__DIR__) . '/include/config.php';
require dirname(__DIR__) . '/include/themecontroller.php';
require dirname(__DIR__) . '/include/exif.php';

use BootstrapDarkroom\Exif;
use BootstrapDarkroom\ThemeController;

// --- 1. rows() ---
$r = Exif::rows(
  array('Make' => 'Canon', 'Model' => 'EOS', 'FNumber' => '13'),
  array('Make', 'Model', 'FNumber'),
  array('exif_field_Make' => 'Marque')
);
bd_assert(array_column($r, 'key') === array('Make', 'Model', 'FNumber'), 'rows_order: keys in show_exif_fields order');
bd_assert(array_column($r, 'label') === array('Marque', 'Model', 'FNumber'), 'rows_labels: translated or raw field');
bd_assert($r[2]['value'] === '13', 'rows_value: value kept as string');

$r = Exif::rows(array('FILE;FileSize' => '3 Mo'), array('FILE;FileSize'), array('exif_field_FileSize' => 'Poids'));
bd_assert(count($r) === 1 && $r[0]['key'] === 'FILE;FileSize' && $r[0]['label'] === 'Poids', 'rows_compound_field: label from the part after ;');

$r = Exif::rows(array('SubjectArea' => array(1, 2), 'Make' => 'X'), array('ISOSpeedRatings', 'SubjectArea', 'Make'), array());
bd_assert(array_column($r, 'key') === array('Make'), 'rows_skip_missing_and_arrays');

$r = Exif::rows(array('ExposureBiasValue' => 0), array('ExposureBiasValue'), array());
bd_assert($r[0]['value'] === '0', 'rows_cast: numeric value cast to string');

// --- 2. forImage() ---
set_error_handler(function ($no, $str) {
  bd_assert(false, "no PHP warning (got: $str)");
  return true;
});

$tmp = tempnam(sys_get_temp_dir(), 'bdexif');

$conf = array('show_exif' => true, 'show_exif_fields' => array('Make', 'Model'));
$lang = array('exif_field_Make' => 'Marque');

$bd_rows = array();
$bd_exif_calls = array();
$res = Exif::forImage(42);
bd_assert($res instanceof PwgError && $res->code === 404, 'forimage_not_visible: 404');
bd_assert(count($bd_exif_calls) === 0, 'forimage_not_visible: file not read');
bd_assert(strpos(end($bd_queries), 'visible') !== false && strpos(end($bd_queries), '42') !== false, 'forimage_not_visible: query has the visibility condition and the id');

$bd_rows = array(array('id' => 42, 'path' => './x.jpg'));
$bd_path = $tmp;
$conf['show_exif'] = false;
$bd_exif_calls = array();
$res = Exif::forImage(42);
bd_assert(is_array($res) && $res['fields'] === array(), 'forimage_show_exif_off: no fields');
bd_assert(count($bd_exif_calls) === 0, 'forimage_show_exif_off: file not read');

$conf['show_exif'] = true;
$bd_path = $tmp . '.missing';
$bd_exif_calls = array();
$res = Exif::forImage(42);
bd_assert(is_array($res) && $res['fields'] === array(), 'forimage_missing_file: no fields');
bd_assert(count($bd_exif_calls) === 0, 'forimage_missing_file: get_exif_data not called');

$bd_path = $tmp;
$bd_exif = array('Make' => 'Canon', 'Model' => 'EOS');
$bd_exif_calls = array();
$res = Exif::forImage(42);
if (function_exists('exif_read_data')) {
  bd_assert($res['image_id'] === 42 && $res['title'] === 'EXIF Metadata', 'forimage_ok: id and title');
  bd_assert($res['fields'] === Exif::rows($bd_exif, $conf['show_exif_fields'], $lang), 'forimage_ok: fields as rows()');
  bd_assert($bd_exif_calls[0] === array($tmp, array('Make' => 'Make', 'Model' => 'Model')), 'forimage_ok: identity mapping, real path');
  bd_assert($bd_metadata_included === true, 'forimage_ok: includes include/functions_metadata.inc.php (not loaded on ws.php)');
} else {
  bd_assert($res['fields'] === array(), 'forimage without exif extension: no fields');
}

// Parity with picture.php: a media shown through its MIME-type icon (video
// without representative) has no metadata, unless a plugin says otherwise
// through get_element_metadata_available.
$bd_mime = true;
$bd_exif_calls = array();
$bd_triggers = array();
$res = Exif::forImage(42);
bd_assert(is_array($res) && $res['fields'] === array(), 'forimage_mimetype: no fields');
bd_assert(count($bd_exif_calls) === 0, 'forimage_mimetype: MIME icon not read');
$evt = array_values(array_filter($bd_triggers, function ($t) {
  return $t[0] === 'get_element_metadata_available';
}));
bd_assert(count($evt) === 1 && $evt[0][1] === false && $evt[0][2] === $bd_rows[0], 'forimage_mimetype: get_element_metadata_available(false, row) triggered');

$bd_mime = false;
$bd_trigger_override = array('get_element_metadata_available' => false);
$bd_exif_calls = array();
$res = Exif::forImage(42);
bd_assert(is_array($res) && $res['fields'] === array() && count($bd_exif_calls) === 0, 'forimage_plugin_veto: a plugin answering false hides the data');
$bd_trigger_override = null;

restore_error_handler();
@unlink($tmp);

// --- 3. Web-service registration ---
class BdFakeService {
  public $calls = array();
  public function addMethod() {
    $this->calls[] = func_get_args();
  }
}
$fake = new BdFakeService();
$conf = array();
$controller = new ThemeController();
$controller->registerWsMethods(array($fake));
$call = isset($fake->calls[0]) ? $fake->calls[0] : array(null, null, array());
bd_assert($call[0] === 'bd.images.getExif', 'registers_method: name');
bd_assert($call[1] === array('\BootstrapDarkroom\Exif', 'ws'), 'registers_method: callback');
bd_assert(array_keys($call[2]) === array('image_id') && $call[2]['image_id']['type'] === WS_TYPE_ID, 'registers_method: image_id param');

$src = (string) file_get_contents(dirname(__DIR__) . '/include/themecontroller.php');
bd_assert(strpos($src, "add_event_handler('ws_add_methods', array(\$this, 'registerWsMethods'))") !== false, 'init() hooks ws_add_methods');
$tc = (string) file_get_contents(dirname(__DIR__) . '/themeconf.inc.php');
bd_assert(strpos($tc, "bootstrap_darkroom/include/exif.php") !== false, 'themeconf.inc.php requires include/exif.php');

exit($fail ? 1 : 0);
