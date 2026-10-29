<?php
namespace BootstrapDarkroom;

/**
 * Lineage: tells which build of the theme the installation last ran, and reacts
 * when the files were swapped in place under it (redux issue #59).
 *
 * The core records a theme's version only at activation and has no update hook
 * for themes, so replacing the files of the original theme (or of an older
 * redux) leaves the themes row stale and the compiled templates of the previous
 * build in place: the gallery keeps serving them, with assets that no longer
 * exist. The settings need nothing: both builds share the 'bootstrap_darkroom'
 * row and its format, so they are inherited as they are and never rewritten here.
 *
 * Where the code can run. In the admin zone the core builds only the admin
 * template, so themeconf.inc.php (and any `init` handler it registers) is never
 * evaluated there. The theme's own code reaches the admin zone through its
 * settings page (admin/admin.inc.php) alone.
 *
 * - classify() is pure: a diagnosis from evidence, no core call.
 * - apply(), from the theme's admin page: purge, stamp, notice. WEBMASTER only,
 *   behind the two-halves guard of the fleet (BOOT-WRITE-GUARD): IN_ADMIN
 *   qualifies the script, is_admin() the caller, is_webmaster() the right to
 *   decide. Purging is what the core's maintenance offers an administrator, but
 *   running it unasked on a swap is the webmaster's call.
 * - notifyPending(), from a public page: read-only notice for the webmaster who
 *   browses the gallery, with a link to the admin page. Never a write.
 * - recordActivation(), from the core's activate(): stamps the files version.
 *
 * Known limit: a rebuild shipped under the SAME version number is not seen.
 */
class Lineage {
  const CONF_PARAM = 'bootstrap_darkroom_lineage';
  const SETTINGS_PARAM = 'bootstrap_darkroom';
  const THEME_ID = 'bootstrap_darkroom';

  /** Version: header, read the way the core reads it ([\w.-]+, so no "+"). */
  public static function readVersion($source) {
    return preg_match('/^\s*Version:\s*([\w.-]+)/mi', (string) $source, $m) ? $m[1] : '';
  }

  /**
   * @param array $e stamp (array|null), conf_present (bool), db_version (string|null), fs_version (string)
   * @return array state (string), purge (bool: compiled templates are stale), stamp (bool: record the files version)
   */
  public static function classify(array $e) {
    $fs = $e['fs_version'];
    $db = $e['db_version'];

    if (is_array($e['stamp']) && isset($e['stamp']['version'])) {
      return $e['stamp']['version'] === $fs ? self::verdict('current', false, false) : self::verdict('upgraded', true, true);
    }
    if ($db === $fs) {
      return self::verdict('current', false, true);
    }
    if ($db === null) {
      return $e['conf_present'] ? self::verdict('adopted_unknown', true, true) : self::verdict('fresh', false, true);
    }
    if (preg_match('/-redux\./', $db)) {
      return self::verdict('adopted_redux_legacy', true, true);
    }
    if (preg_match('/^\d+\.[a-z]$/i', $db)) {
      return self::verdict('adopted_original', true, true);
    }
    if (preg_match('/^\d+\.\d+\.\d+$/', $db)) {
      return self::verdict('upgraded', true, true);
    }
    return self::verdict('adopted_unknown', true, true);
  }

  private static function verdict($state, $purge, $stamp) {
    return array('state' => $state, 'purge' => $purge, 'stamp' => $stamp);
  }

  /**
   * The theme's admin page: purge, stamp, notice. $themeconf: path of this theme's
   * themeconf.inc.php. Webmaster in the admin zone only.
   */
  public static function apply($themeconf) {
    if (!defined('IN_ADMIN') || !IN_ADMIN) {
      return;
    }
    if (!function_exists('is_admin') || !is_admin()) {
      return;
    }
    if (!function_exists('is_webmaster') || !is_webmaster()) {
      return;
    }

    global $template, $page;

    $verdict = self::diagnose($themeconf);
    if ($verdict === null || (!$verdict['purge'] && !$verdict['stamp'])) {
      return;
    }

    // Purge first: the stamp is what stops the retries, so it only follows a purge that worked.
    if ($verdict['purge']) {
      if (!is_object($template) || !method_exists($template, 'delete_compiled_templates')) {
        return;
      }
      try {
        $template->delete_compiled_templates();
      } catch (\Throwable $error) {
        return;
      }
    }
    if ($verdict['stamp']) {
      self::writeStamp($verdict['fs_version'], $verdict['state']);
    }
    if ($verdict['purge']) {
      $page['infos'][] = l10n($verdict['state'] === 'upgraded' ? 'bd_lineage_upgraded' : 'bd_lineage_adopted');
    }
  }

  /** A public page: tell the webmaster, read-only, that a swap waits for their decision. */
  public static function notifyPending($themeconf) {
    if (!function_exists('is_webmaster') || !is_webmaster()) {
      return;
    }
    $verdict = self::diagnose($themeconf);
    if ($verdict === null || !$verdict['purge']) {
      return;
    }
    global $page;
    $page['infos'][] = sprintf(l10n('bd_lineage_pending'), get_root_url() . 'admin.php?page=theme&theme=' . self::THEME_ID);
  }

  /** The core's activate(): that path is already controlled by the core. */
  public static function recordActivation($themeconf) {
    $fs = self::readVersion(@file_get_contents($themeconf, false, null, 0, 2048));
    if ($fs !== '') {
      self::writeStamp($fs, 'activated');
    }
  }

  /**
   * Verdict plus the files version, or null when nothing needs deciding. The
   * steady state (stamp == files version) costs a conf lookup and a 2 KB read, no SQL.
   */
  private static function diagnose($themeconf) {
    global $conf;

    $fs = self::readVersion(@file_get_contents($themeconf, false, null, 0, 2048));
    if ($fs === '') {
      return null;
    }
    $stamp = isset($conf[self::CONF_PARAM]) ? json_decode($conf[self::CONF_PARAM], true) : null;
    if (is_array($stamp) && isset($stamp['version']) && $stamp['version'] === $fs) {
      return null;
    }
    $verdict = self::classify(array(
      'stamp'        => $stamp,
      'conf_present' => array_key_exists(self::SETTINGS_PARAM, $conf),
      'db_version'   => self::readDbVersion(),
      'fs_version'   => $fs,
    ));
    $verdict['fs_version'] = $fs;
    return $verdict;
  }

  private static function writeStamp($version, $origin) {
    conf_update_param(self::CONF_PARAM, addslashes(json_encode(array(
      'version' => $version,
      'origin'  => $origin,
      'at'      => date('c'),
    ))));
  }

  /** Version column of this theme's row, or null (read-only, bounded to one row). */
  private static function readDbVersion() {
    if (!defined('THEMES_TABLE') || !function_exists('pwg_query')) {
      return null;
    }
    $res = pwg_query('SELECT version FROM ' . THEMES_TABLE . " WHERE id = '" . pwg_db_real_escape_string(self::THEME_ID) . "';");
    if (!$res) {
      return null;
    }
    $row = pwg_db_fetch_assoc($res);
    return ($row && isset($row['version'])) ? (string) $row['version'] : null;
  }
}
