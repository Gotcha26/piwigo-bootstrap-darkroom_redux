<?php
/**
 * The "third-party asset bundle not installed" alert methods are answered by the
 * theme's admin page (jm_ws_admin_page_dispatch), with the visitor on another theme.
 *
 * Why: ws.php only loads the themeconf of the visitor's theme, so a method declared
 * there is unknown to a webmaster browsing with another theme. The admin page loads
 * admin/admin.inc.php for any installed theme, and the alert buttons post to it.
 *
 * The theme has ONE access level, 'admin' (piwigo_local#1433): jm_access_gate() at
 * the top of admin.inc.php refuses a non-administrator before the dispatcher, and
 * an administrator who is not the webmaster may save, reset AND dismiss the alert.
 * Only vendorRetry stays the webmaster's: installing an extension is reserved to
 * the webmaster by the Piwigo core (jm_ws_extension_install_denied()).
 *
 * METHOD: a sandbox copy of the theme (admin/*.php, include/*.php) whose include/
 * receives the hub commons jm-access.php, jm-ws.php, jm-vendor-pins.php and
 * jm-vendor.php, as the build would inject them (read from the hub, never written
 * into the working tree). One child process per scenario includes the REAL
 * admin.inc.php under a simulated POST. admin.inc.php loads the commons ITSELF: if
 * jm-vendor.php were loaded after the dispatcher, or the alert block missing from
 * the table passed to it, the page would render (RENDER_REACHED) instead of answering.
 *
 * Run: php tests/test_ws_admin_dispatch.php (needs the workspace hub ../.local).
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

$root = dirname(__DIR__);
require_once __DIR__ . '/_hub.php';
$hub  = jm_test_hub_root();
foreach (array('jm-access.php', 'jm-ws.php', 'jm-vendor-pins.php', 'jm-vendor.php') as $common) {
  if (!is_readable($hub . '/include/' . $common)) {
    fwrite(STDERR, "FAIL: hub common not found ($hub/include/$common)\n");
    exit(1);
  }
}

$tmp = sys_get_temp_dir() . '/bd_ws_' . getmypid() . '_' . uniqid();

// Fake core: PwgError only exists in include/ws_core.inc.php, which the dispatcher
// loads itself (admin.php?page=theme does not).
$core = $tmp . '/core';
@mkdir($core . '/include', 0777, true);
file_put_contents($core . '/include/ws_core.inc.php', '<?php
class PwgError {
  private $c;
  private $m;
  public function __construct($code, $message) {
    $this->c = $code;
    $this->m = $message;
  }
  public function code() {
    return $this->c;
  }
  public function message() {
    return $this->m;
  }
}
');

// Sandbox: the theme's PHP, plus the commons the build injects.
$box = $tmp . '/theme';
foreach (array('admin', 'include') as $dir) {
  @mkdir($box . '/' . $dir, 0777, true);
  foreach (glob($root . '/' . $dir . '/*.php') as $file) {
    copy($file, $box . '/' . $dir . '/' . basename($file));
  }
}
foreach (array('jm-access.php', 'jm-ws.php', 'jm-vendor-pins.php', 'jm-vendor.php') as $common) {
  copy($hub . '/include/' . $common, $box . '/include/' . $common);
}

/**
 * @param  array $post
 * @param  bool  $webmaster
 * @param  bool  $admin     False: a user who is not an administrator at all.
 * @return array{raw:string, json:?array, session:?string, conf:?string, render:bool, denied:bool}
 */
function bd_run_post(array $post, $webmaster = true, $admin = true) {
  global $box, $core, $tmp;
  $out = $tmp . '/out_' . uniqid();
  $conf = $tmp . '/conf_' . uniqid();
  putenv('BD_T_ROOT=' . $box);
  putenv('BD_T_CORE=' . $core);
  putenv('BD_T_OUT=' . $out);
  putenv('BD_T_CONF=' . $conf);
  putenv('BD_T_POST=' . json_encode($post));
  putenv('BD_T_WEBMASTER=' . ($webmaster ? '1' : '0'));
  putenv('BD_T_ADMIN=' . ($admin ? '1' : '0'));
  $lines = array();
  exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/_ws_admin_dispatch_child.php') . ' 2>&1', $lines);
  $raw = implode("\n", $lines);
  $session = is_file($out) ? file_get_contents($out) : null;
  $written = is_file($conf) ? file_get_contents($conf) : null;
  @unlink($out);
  @unlink($conf);
  return array(
    'raw'     => $raw,
    'json'    => json_decode($raw, true),
    'session' => $session,
    'conf'    => $written,
    'render'  => strpos($raw, 'RENDER_REACHED') !== false,
    'denied'  => strpos($raw, 'ACCESS_DENIED') !== false,
  );
}

$dismiss = array('method' => 'bootstrap_darkroom.vendorDismiss', 'pwg_token' => 'TOKEN_OK', 'cause' => 'unreachable');

// 1. Dismiss through the dispatcher, visitor on another theme.
$r = bd_run_post($dismiss);
// The JSON is required too: past an unanswered dispatch, the render path of this
// harness may die before RENDER_REACHED (no Piwigo $conf), which proves nothing.
bd_assert($r['json'] !== null && !$r['render'], 'scenario 1: vendorDismiss answered by the dispatcher in pure JSON, the page does not render (output: ' . substr($r['raw'], 0, 160) . ')');
bd_assert(isset($r['json']['stat']) && $r['json']['stat'] === 'ok' && isset($r['json']['result']['success']) && $r['json']['result']['success'] === true,
  'scenario 1: vendorDismiss -> stat ok, success = true');
bd_assert($r['session'] === 'jm_vendor_alert_dismissed=unreachable', 'scenario 1: the dismissed cause is stored in the session');

// 2. Wrong CSRF token -> 403, nothing stored.
$r = bd_run_post(array_merge($dismiss, array('pwg_token' => 'WRONG')));
bd_assert(isset($r['json']['err']) && $r['json']['err'] === 403 && $r['session'] === null && !$r['render'],
  'scenario 2: vendorDismiss with a wrong token -> 403, nothing stored');

// 3. Administrator who is not the webmaster -> dismissed (the theme's level is
// 'admin'). The hub common sets no level of its own (piwigo_local#1441): the
// theme's gate, jm_access_gate('admin'), decides; dismissing only touches the session.
$r = bd_run_post($dismiss, false);
bd_assert(!$r['denied'] && !$r['render'] && isset($r['json']['stat']) && $r['json']['stat'] === 'ok'
  && isset($r['json']['result']['success']) && $r['json']['result']['success'] === true
  && $r['session'] === 'jm_vendor_alert_dismissed=unreachable',
  'scenario 3: vendorDismiss by an administrator who is not the webmaster -> ok, cause stored in the session (output: ' . substr($r['raw'], 0, 160) . ')');

// 3b. vendorRetry by that same administrator -> 403 forbidden_not_webmaster. A CORE
// exception, not the theme's level: Piwigo reserves installing extensions to the
// webmaster (jm_ws_extension_install_denied()). The gate let it through (no
// ACCESS_DENIED): the callback refuses, before any network access.
$r = bd_run_post(array('method' => 'bootstrap_darkroom.vendorRetry', 'pwg_token' => 'TOKEN_OK'), false);
bd_assert(!$r['denied'] && !$r['render'] && isset($r['json']['err']) && $r['json']['err'] === 403
  && $r['json']['message'] === 'forbidden_not_webmaster',
  'scenario 3b: vendorRetry by an administrator who is not the webmaster -> 403 forbidden_not_webmaster (output: ' . substr($r['raw'], 0, 160) . ')');

// 3c. A user who is not an administrator: both alert methods refused by the access
// gate, BEFORE the dispatcher, even with a valid token; nothing stored.
foreach (array('vendorDismiss' => $dismiss, 'vendorRetry' => array('method' => 'bootstrap_darkroom.vendorRetry', 'pwg_token' => 'TOKEN_OK')) as $bd_m => $bd_post) {
  $r = bd_run_post($bd_post, false, false);
  bd_assert($r['denied'] && $r['json'] === null && $r['session'] === null && !$r['render'],
    "scenario 3c: $bd_m by a non-administrator -> access_denied() before the dispatcher, nothing stored");
}

// 4. vendorRetry is DECLARED (wrong token: refused before any network access).
// Undeclared, the dispatcher would ignore it and the page would render.
$r = bd_run_post(array('method' => 'bootstrap_darkroom.vendorRetry', 'pwg_token' => 'WRONG'));
bd_assert(!$r['render'] && isset($r['json']['err']) && $r['json']['err'] === 403 && $r['json']['message'] === 'invalid_token',
  'scenario 4: vendorRetry is served by the dispatcher (wrong token -> 403 invalid_token)');

// 5. The settings save is still in the same table (wrong token: nothing written).
$r = bd_run_post(array('method' => 'bootstrap_darkroom.settingsSave', 'pwg_token' => 'WRONG'));
bd_assert(!$r['render'] && isset($r['json']['err']) && $r['json']['err'] === 403,
  'scenario 5: settingsSave is still served by the same dispatcher');

// 6. The reset is DECLARED in the same table (wrong token: nothing written).
// Undeclared, the dispatcher would ignore it and the page would render.
$r = bd_run_post(array('method' => 'bootstrap_darkroom.settingsReset', 'pwg_token' => 'WRONG'));
bd_assert(!$r['render'] && isset($r['json']['err']) && $r['json']['err'] === 403 && $r['json']['message'] === 'invalid_token',
  'scenario 6: settingsReset is served by the dispatcher (wrong token -> 403 invalid_token)');

// 7. Reset by an administrator who is not the webmaster -> allowed, settings written
// (the theme's level is 'admin', with no exception: whoever may save may reset).
$r = bd_run_post(array('method' => 'bootstrap_darkroom.settingsReset', 'pwg_token' => 'TOKEN_OK'), false);
bd_assert(!$r['render'] && !$r['denied'] && isset($r['json']['stat']) && $r['json']['stat'] === 'ok'
  && isset($r['json']['result']['reset']) && $r['json']['result']['reset'] === true
  && $r['conf'] !== null && strpos($r['conf'], 'bootstrap_darkroom=') === 0,
  'scenario 7: settingsReset by an administrator who is not the webmaster -> ok, settings written (output: ' . substr($r['raw'], 0, 160) . ')');

// 8. Save by an administrator who is not the webmaster -> allowed, settings written.
$r = bd_run_post(array('method' => 'bootstrap_darkroom.settingsSave', 'pwg_token' => 'TOKEN_OK'), false);
bd_assert(!$r['render'] && !$r['denied'] && isset($r['json']['stat']) && $r['json']['stat'] === 'ok'
  && isset($r['json']['result']['saved']) && $r['json']['result']['saved'] === true
  && $r['conf'] !== null && strpos($r['conf'], 'bootstrap_darkroom=') === 0,
  'scenario 8: settingsSave by an administrator who is not the webmaster -> ok, settings written (output: ' . substr($r['raw'], 0, 160) . ')');

// 9. Wrong CSRF token on a reset by that same administrator -> still refused.
$r = bd_run_post(array('method' => 'bootstrap_darkroom.settingsReset', 'pwg_token' => 'WRONG'), false);
bd_assert(!$r['render'] && isset($r['json']['err']) && $r['json']['err'] === 403 && $r['json']['message'] === 'invalid_token'
  && $r['conf'] === null,
  'scenario 9: settingsReset with a wrong token by a non-webmaster administrator -> 403 invalid_token, nothing written');

// 10. A user who is not an administrator: refused by the access gate, BEFORE the
// dispatcher (no JSON, nothing written), even with a valid token.
foreach (array('settingsSave', 'settingsReset') as $bd_m) {
  $r = bd_run_post(array('method' => 'bootstrap_darkroom.' . $bd_m, 'pwg_token' => 'TOKEN_OK'), false, false);
  bd_assert($r['denied'] && $r['json'] === null && $r['conf'] === null && !$r['render'],
    "scenario 10: $bd_m by a non-administrator -> access_denied() before the dispatcher, nothing written (output: " . substr($r['raw'], 0, 160) . ')');
}

// 11. The gate is the entry's first statement, before the dispatcher, never defensive.
$bd_entry = php_strip_whitespace($root . '/admin/admin.inc.php');
$bd_gate = strpos($bd_entry, "jm_access_gate('admin')");
$bd_disp = strpos($bd_entry, 'jm_ws_admin_page_dispatch(');
bd_assert($bd_gate !== false && $bd_disp !== false && $bd_gate < $bd_disp
  && preg_match("/function_exists\\s*\\(\\s*'jm_access_/", $bd_entry) === 0,
  "scenario 11: admin.inc.php calls jm_access_gate('admin') before the dispatcher, unguarded");

// --- Client side: the reset goes through the admin page, never a form POST ---
$js_all = file_get_contents($root . '/admin/assets/js/bd-admin.js');
$js_code = preg_replace('#/\*.*?\*/#s', '', $js_all);
bd_assert(strpos($js_code, "jm.registerResetConfirmVariant('bd-admin-page', resetViaAdminPage)") !== false
  && strpos($js_code, "jm.confirmReset(button)") !== false,
  'bd-admin.js registers the bd-admin-page variant, then binds jm.confirmReset');
bd_assert(preg_match("/jm\\.ws\\('bootstrap_darkroom\\.settingsReset',[^\\n]*wsUrl:\\s*A\\.saveUrl/", $js_code) === 1,
  'bd-admin.js posts settingsReset to the admin page (wsUrl: A.saveUrl)');
$admin_src = file_get_contents($root . '/admin/admin.inc.php');
bd_assert(preg_match("/'brick' => 'reset'.*'form_id' => 'bd-settings-form'.*'confirm_variant' => 'bd-admin-page'/s", $admin_src) === 1
  && strpos(file_get_contents($root . '/admin/template/bd-body.tpl'), '<form id="bd-settings-form"') !== false,
  'admin.inc.php declares the reset brick on the real form id, with the bd-admin-page variant');

// --- Client side: the alert posts to the admin page, with the save's token ---
$js = file_get_contents($root . '/admin/assets/js/bd-admin.js');
$code = preg_replace('#/\*.*?\*/#s', '', $js);
bd_assert(strpos($code, "wsMethod: 'bootstrap_darkroom.vendorRetry'") !== false
  && strpos($code, "wsDismissMethod: 'bootstrap_darkroom.vendorDismiss'") !== false,
  'bd-admin.js passes the two method names to jm.vendorAlert');
bd_assert(preg_match('/window\.jm\.vendorAlert\(\{[^}]*wsUrl:\s*A\.saveUrl/s', $code) === 1,
  'bd-admin.js passes wsUrl: A.saveUrl (the admin page) to jm.vendorAlert');
bd_assert(preg_match('/jm = window\.jm \|\| \{\};\s*initVendorAlert\(\);/', $code) === 1,
  'bd-admin.js calls initVendorAlert() in init(), before the form guard');

// Cleanup.
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) {
  $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
}
@rmdir($tmp);

exit($fail === 0 ? 0 : 1);
