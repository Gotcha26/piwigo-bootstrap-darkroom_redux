<?php
/**
 * Lineage: what the theme does when its files were swapped in place under an
 * installation that ran another build (piwigo_local#1446 / redux issue #59).
 *
 * The core records a theme's version only at activation and has no update hook
 * for themes, so replacing the files of the ORIGINAL (or of an older redux)
 * leaves the themes row stale and the compiled templates of the previous build in
 * place. Observed on the lab (piwigo.julien-moreau.fr): the home page kept serving
 * the original's templates and four assets answered 404 until the compiled
 * templates were purged.
 *
 * Part 1 - classify(): the pure diagnosis, from evidence only.
 * Part 2 - apply(): the guarded action (purge, stamp, notice), one process per
 *          scenario. Only the WEBMASTER, in the admin zone, ever acts. The two
 *          halves of the guard (IN_ADMIN: the script; is_admin()/is_webmaster():
 *          the caller) are proven separately, and a visitor request emits no SQL.
 *          apply() is called from admin/admin.inc.php: in the admin zone the core
 *          loads only the admin template, so themeconf.inc.php (and any `init`
 *          handler it registers) never runs there.
 * Part 3 - notifyPending(): on a PUBLIC page, a read-only notice for the webmaster
 *          alone, pointing to the theme's admin page. Never a write, never a purge.
 * Part 4 - recordActivation(): the core's own activation stamps the files version.
 *
 * Run: php tests/test_lineage.php
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

require dirname(__DIR__) . '/include/lineage.php';

use BootstrapDarkroom\Lineage;

// ---------------------------------------------------------------------------
// Part 1 - the diagnosis
// ---------------------------------------------------------------------------
function bd_ev($stamp, $conf, $db, $fs) {
  return array('stamp' => $stamp, 'conf_present' => $conf, 'db_version' => $db, 'fs_version' => $fs);
}

$cases = array(
  // name                                   evidence                                        state                  purge  stamp
  array('stamp matches the files',          bd_ev(array('version' => '0.1.0'), true, '16.e', '0.1.0'), 'current',             false, false),
  array('stamp older than the files',       bd_ev(array('version' => '0.1.0'), true, '16.e', '0.2.0'), 'upgraded',            true,  true),
  array('nothing at all (fresh install)',   bd_ev(null, false, null, '0.1.0'),                         'fresh',               false, true),
  array('original swapped in place',        bd_ev(null, true, '16.e', '0.1.0'),                        'adopted_original',    true,  true),
  array('original, never configured',       bd_ev(null, false, '16.e', '0.1.0'),                       'adopted_original',    true,  true),
  array('previous redux format',            bd_ev(null, true, '16.d-redux.1', '0.1.0'),                'adopted_redux_legacy', true, true),
  array('row already at the files version', bd_ev(null, true, '0.1.0', '0.1.0'),                       'current',             false, true),
  array('unstamped redux, newer files',     bd_ev(null, true, '0.1.0', '0.2.0'),                       'upgraded',            true,  true),
  array('settings left, no themes row',     bd_ev(null, true, null, '0.1.0'),                          'adopted_unknown',     true,  true),
  array('unrecognised version format',      bd_ev(null, true, 'weird', '0.1.0'),                       'adopted_unknown',     true,  true),
);
foreach ($cases as $c) {
  $r = Lineage::classify($c[1]);
  bd_assert($r['state'] === $c[2], "classify: {$c[0]} -> {$c[2]} (got {$r['state']})");
  bd_assert($r['purge'] === $c[3], "classify: {$c[0]} -> purge " . var_export($c[3], true));
  bd_assert($r['stamp'] === $c[4], "classify: {$c[0]} -> stamp " . var_export($c[4], true));
}

bd_assert(Lineage::readVersion("<?php\n/*\nTheme Name: X\nVersion: 16.d-redux.1\nDescription: y\n*/") === '16.d-redux.1',
  'readVersion: keeps the lettered / dashed formats the core accepts ([\w.-]+)');
bd_assert(Lineage::readVersion("<?php\n/*\nVersion: 0.1.0+build\n*/") === '0.1.0',
  'readVersion: stops where the core stops (no "+" in a version)');
bd_assert(Lineage::readVersion('<?php // nothing') === '', 'readVersion: no header -> empty');

// ---------------------------------------------------------------------------
// Part 2 - the action, one process per scenario
// ---------------------------------------------------------------------------
$conf_original = array('bootstrap_darkroom' => '{"conf_version":20,"fluid_width":false}');

function bd_run(array $env) {
  $out = sys_get_temp_dir() . '/bd_lineage_out_' . getmypid() . '_' . uniqid();
  $env += array(
    'BD_L_IN_ADMIN' => '1', 'BD_L_ADMIN' => '1', 'BD_L_WEBMASTER' => '1',
    'BD_L_CONF' => '{}', 'BD_L_DB_VERSION' => '', 'BD_L_FS_VERSION' => '0.1.0', 'BD_L_PURGE_FAILS' => '0',
    'BD_L_MODE' => 'apply',
  );
  foreach ($env as $k => $v) {
    putenv($k . '=' . $v);
  }
  putenv('BD_L_OUT=' . $out);
  $lines = array();
  exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/_lineage_child.php') . ' 2>&1', $lines);
  $ev = is_file($out) ? json_decode(file_get_contents($out), true) : null;
  @unlink($out);
  // A fatal error still lets the shutdown handler record empty events: never read that as "did nothing".
  if (preg_match('/Fatal error|Parse error|Warning:|Notice:|Deprecated:/', implode("\n", $lines))) {
    $ev = null;
  }
  return array('ev' => $ev, 'raw' => implode("\n", $lines));
}

$orig = array('BD_L_CONF' => json_encode($conf_original), 'BD_L_DB_VERSION' => '16.e');

// 2.1 The webmaster, in the admin zone, over a swapped original
$r = bd_run($orig);
bd_assert($r['ev'] !== null, 'webmaster: the child ran (' . substr($r['raw'], 0, 120) . ')');
bd_assert($r['ev']['purges'] === 1, 'webmaster: compiled templates purged once');
$stamp = isset($r['ev']['writes']['bootstrap_darkroom_lineage']) ? json_decode($r['ev']['writes']['bootstrap_darkroom_lineage'], true) : null;
bd_assert(is_array($stamp) && $stamp['version'] === '0.1.0' && $stamp['origin'] === 'adopted_original',
  'webmaster: stamp written with the files version and the diagnosed origin');
bd_assert(!isset($r['ev']['writes']['bootstrap_darkroom']),
  'webmaster: the original\'s settings row is NEVER rewritten (settings are inherited as they are)');
bd_assert(count($r['ev']['infos']) === 1, 'webmaster: one notice shown');

// 2.2 An administrator who is not the webmaster: nothing, not even SQL
$r = bd_run($orig + array('BD_L_WEBMASTER' => '0'));
bd_assert($r['ev'] !== null && $r['ev']['purges'] === 0 && !$r['ev']['writes'] && !$r['ev']['infos'],
  'administrator (not webmaster): no purge, no write, no notice');
bd_assert($r['ev']['queries'] === 0, 'administrator (not webmaster): no SQL emitted');

// 2.3 Anonymous on /admin.php: IN_ADMIN is true, the caller is nobody (the "is_admin()" half)
$r = bd_run($orig + array('BD_L_ADMIN' => '0', 'BD_L_WEBMASTER' => '0'));
bd_assert($r['ev'] !== null && $r['ev']['purges'] === 0 && !$r['ev']['writes'] && $r['ev']['queries'] === 0,
  'anonymous on admin.php: nothing, no SQL (is_admin() half of the guard)');

// 2.3b Each predicate must close on its own: a caller the core calls webmaster but not admin (a double
//      here; unreachable in practice) is still refused, so is_admin() is not a decoration.
$r = bd_run($orig + array('BD_L_ADMIN' => '0', 'BD_L_WEBMASTER' => '1'));
bd_assert($r['ev'] !== null && $r['ev']['purges'] === 0 && !$r['ev']['writes'] && $r['ev']['queries'] === 0,
  'is_admin() closes by itself, even when is_webmaster() would say yes');

// 2.4 The webmaster browsing the gallery: IN_ADMIN absent (the "IN_ADMIN" half)
$r = bd_run($orig + array('BD_L_IN_ADMIN' => '0'));
bd_assert($r['ev'] !== null && $r['ev']['purges'] === 0 && !$r['ev']['writes'] && $r['ev']['queries'] === 0,
  'webmaster on a front page: nothing, no SQL (IN_ADMIN half of the guard)');

// 2.5 Already stamped at the files version: the steady state costs no SQL and no write
$stamped = array('bootstrap_darkroom' => '{"conf_version":20}', 'bootstrap_darkroom_lineage' => '{"version":"0.1.0","origin":"adopted_original"}');
$r = bd_run(array('BD_L_CONF' => json_encode($stamped), 'BD_L_DB_VERSION' => '16.e'));
bd_assert($r['ev'] !== null && $r['ev']['purges'] === 0 && !$r['ev']['writes'] && !$r['ev']['infos'] && $r['ev']['queries'] === 0,
  'stamped at the files version: no purge, no write, no notice, no SQL');

// 2.6 A purge that fails is retried: the stamp is NOT written
$r = bd_run($orig + array('BD_L_PURGE_FAILS' => '1'));
bd_assert($r['ev'] !== null && !isset($r['ev']['writes']['bootstrap_darkroom_lineage']) && !$r['ev']['infos'],
  'failed purge: no stamp, no notice (the next admin page retries)');

// 2.7 A newer redux dropped over a stamped one
$r = bd_run(array('BD_L_CONF' => json_encode($stamped), 'BD_L_FS_VERSION' => '0.2.0'));
$stamp = isset($r['ev']['writes']['bootstrap_darkroom_lineage']) ? json_decode($r['ev']['writes']['bootstrap_darkroom_lineage'], true) : null;
bd_assert($r['ev'] !== null && $r['ev']['purges'] === 1 && is_array($stamp) && $stamp['version'] === '0.2.0' && $stamp['origin'] === 'upgraded',
  'in-place upgrade of a stamped redux: purged, restamped as "upgraded"');

// ---------------------------------------------------------------------------
// Part 3 - the public notice (read-only, webmaster only)
// ---------------------------------------------------------------------------
$pub = $orig + array('BD_L_MODE' => 'notify', 'BD_L_IN_ADMIN' => '0');

$r = bd_run($pub);
bd_assert($r['ev'] !== null && count($r['ev']['infos']) === 1
  && strpos($r['ev']['infos'][0], '/gallery/admin.php?page=theme&theme=bootstrap_darkroom') !== false,
  'public page, webmaster, swap pending: one notice linking to the theme admin page');
bd_assert($r['ev']['purges'] === 0 && !$r['ev']['writes'],
  'public page, webmaster: the notice never purges and never writes (BOOT-WRITE-GUARD)');

$r = bd_run($pub + array('BD_L_WEBMASTER' => '0'));
bd_assert($r['ev'] !== null && !$r['ev']['infos'] && $r['ev']['queries'] === 0,
  'public page, administrator who is not the webmaster: no notice, no SQL');

$r = bd_run($pub + array('BD_L_ADMIN' => '0', 'BD_L_WEBMASTER' => '0'));
bd_assert($r['ev'] !== null && !$r['ev']['infos'] && $r['ev']['queries'] === 0,
  'public page, visitor: no notice, no SQL');

$r = bd_run(array('BD_L_MODE' => 'notify', 'BD_L_IN_ADMIN' => '0', 'BD_L_CONF' => json_encode($stamped), 'BD_L_DB_VERSION' => '16.e'));
bd_assert($r['ev'] !== null && !$r['ev']['infos'] && $r['ev']['queries'] === 0,
  'public page, webmaster, already stamped: no notice, no SQL (steady state)');

$r = bd_run(array('BD_L_MODE' => 'notify', 'BD_L_IN_ADMIN' => '0'));
bd_assert($r['ev'] !== null && !$r['ev']['infos'],
  'public page, webmaster, fresh install (no settings, no themes row): no notice, nothing to take over');

// ---------------------------------------------------------------------------
// Part 4 - activation
// ---------------------------------------------------------------------------
$r = bd_run(array('BD_L_MODE' => 'activate', 'BD_L_IN_ADMIN' => '0', 'BD_L_ADMIN' => '0', 'BD_L_WEBMASTER' => '0', 'BD_L_FS_VERSION' => '0.3.0'));
$stamp = isset($r['ev']['writes']['bootstrap_darkroom_lineage']) ? json_decode($r['ev']['writes']['bootstrap_darkroom_lineage'], true) : null;
bd_assert($r['ev'] !== null && is_array($stamp) && $stamp['version'] === '0.3.0' && $stamp['origin'] === 'activated'
  && $r['ev']['purges'] === 0 && $r['ev']['queries'] === 0,
  'activation: stamps the files version as "activated" (the core controls that path), no purge, no SQL');

// ---------------------------------------------------------------------------
// Part 5 - wiring of the admin page (found on the lab: the notice came out as a raw key)
// ---------------------------------------------------------------------------
$admin_src = file_get_contents(dirname(__DIR__) . '/admin/admin.inc.php');
$pos_gate = strpos($admin_src, "jm_access_gate('admin')");
// The CALL, not the mentions in the header comment.
$pos_disp = preg_match('/^\s+jm_ws_admin_page_dispatch\(/m', $admin_src, $m_disp, PREG_OFFSET_CAPTURE) ? $m_disp[0][1] : false;
$pos_lang = strpos($admin_src, "load_language('theme.lang'");
$pos_apply = strpos($admin_src, 'Lineage::apply(');
bd_assert($pos_apply !== false && $pos_lang !== false && $pos_apply > $pos_lang,
  'admin.inc.php: Lineage::apply() runs after load_language(theme.lang), else the notice is a raw key');
bd_assert($pos_apply !== false && $pos_disp !== false && $pos_apply > $pos_disp,
  'admin.inc.php: Lineage::apply() runs after the AJAX dispatcher, which answers and exits (no notice for a save)');
bd_assert($pos_gate !== false && $pos_apply !== false && $pos_apply > $pos_gate,
  'admin.inc.php: Lineage::apply() runs behind the theme\'s access gate');
// Found by the webmaster on the lab: the confirmation vanished before it could be read. The admin
// page turns every server-rendered box into a 3 s toast (jm.flashServerMessages, SERVER-MESSAGES
// Voie B). The common now computes the duration from the text length (jm.flash, piwigo_local#1479):
// the page must not pin a fixed successDuration, which would switch that computation off.
$admin_js = file_get_contents(dirname(__DIR__) . '/admin/assets/js/bd-admin.js');
bd_assert(strpos($admin_js, 'jm.flashServerMessages()') !== false,
  'bd-admin.js: server-rendered notices are flashed without a fixed duration (the common computes it)');
bd_assert(!preg_match('/flashServerMessages\(\s*\{[^}]*successDuration/', $admin_js),
  'bd-admin.js: no fixed successDuration left on flashServerMessages()');

foreach (array('en_UK', 'fr_FR') as $lang_dir) {
  $lang_src = file_get_contents(dirname(__DIR__) . '/language/' . $lang_dir . '/theme.lang.php');
  // Found on the lab: the link inherited the page grey on the pale blue box. Bootstrap's own class fixes it.
  bd_assert(preg_match("/bd_lineage_pending'\\].*<a class=\\\\?\"alert-link\\\\?\"/", $lang_src) === 1,
    "language $lang_dir: the pending notice's link carries class alert-link (readable on the alert box)");
  foreach (array('bd_lineage_adopted', 'bd_lineage_upgraded', 'bd_lineage_pending') as $key) {
    bd_assert(strpos($lang_src, "\$lang['$key']") !== false, "language $lang_dir carries $key");
    // These sentences reach the webmaster of a small gallery, not a developer: reassure, never explain the mechanism.
    $text = preg_match("/\\\$lang\\['$key'\\] = '(.*)';/", $lang_src, $m_text) ? $m_text[1] : '';
    bd_assert($text !== '' && !preg_match('/gabarit|compil|template|cache|purg|smarty|\bSQL\b/i', $text),
      "language $lang_dir: $key carries no technical jargon");
    bd_assert($text !== '' && preg_match('/r[ée]glages|settings/iu', $text) === 1,
      "language $lang_dir: $key reassures about the settings");
  }
}

echo $fail === 0 ? "\nAll good.\n" : "\n$fail failure(s).\n";
exit($fail === 0 ? 0 : 1);
