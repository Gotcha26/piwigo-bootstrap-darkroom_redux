<?php
/**
 * The "third-party asset bundle not installed" alert methods are answered by the
 * theme's admin page (jm_ws_admin_page_dispatch), with the visitor on another theme.
 *
 * Why: ws.php only loads the themeconf of the visitor's theme, so a method declared
 * there is unknown to a webmaster browsing with another theme. The admin page loads
 * admin/admin.inc.php for any installed theme, and the alert buttons post to it.
 *
 * METHOD: a sandbox copy of the theme (admin/*.php, include/*.php) whose include/
 * receives the hub commons jm-ws.php, jm-vendor-pins.php and jm-vendor.php, as the
 * build would inject them. One child process per scenario includes the REAL
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
foreach (array('jm-ws.php', 'jm-vendor-pins.php', 'jm-vendor.php') as $common) {
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
foreach (array('jm-ws.php', 'jm-vendor-pins.php', 'jm-vendor.php') as $common) {
  copy($hub . '/include/' . $common, $box . '/include/' . $common);
}

/**
 * @param  array $post
 * @param  bool  $webmaster
 * @return array{raw:string, json:?array, session:?string, render:bool}
 */
function bd_run_post(array $post, $webmaster = true) {
  global $box, $core, $tmp;
  $out = $tmp . '/out_' . uniqid();
  putenv('BD_T_ROOT=' . $box);
  putenv('BD_T_CORE=' . $core);
  putenv('BD_T_OUT=' . $out);
  putenv('BD_T_POST=' . json_encode($post));
  putenv('BD_T_WEBMASTER=' . ($webmaster ? '1' : '0'));
  $lines = array();
  exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/_ws_admin_dispatch_child.php') . ' 2>&1', $lines);
  $raw = implode("\n", $lines);
  $session = is_file($out) ? file_get_contents($out) : null;
  @unlink($out);
  return array(
    'raw'     => $raw,
    'json'    => json_decode($raw, true),
    'session' => $session,
    'render'  => strpos($raw, 'RENDER_REACHED') !== false,
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

// 3. Administrator who is not the webmaster -> 403, nothing stored.
$r = bd_run_post($dismiss, false);
bd_assert(isset($r['json']['message']) && $r['json']['message'] === 'forbidden_not_webmaster' && $r['session'] === null,
  'scenario 3: vendorDismiss by a non-webmaster -> 403 forbidden_not_webmaster, nothing stored');

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

// 7. Reset by an administrator who is not the webmaster -> 403, before any write
// (the callback refuses before building the Config, which this harness cannot load).
$r = bd_run_post(array('method' => 'bootstrap_darkroom.settingsReset', 'pwg_token' => 'TOKEN_OK'), false);
bd_assert(!$r['render'] && isset($r['json']['message']) && $r['json']['message'] === 'forbidden_not_webmaster',
  'scenario 7: settingsReset by a non-webmaster -> 403 forbidden_not_webmaster (output: ' . substr($r['raw'], 0, 160) . ')');

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
