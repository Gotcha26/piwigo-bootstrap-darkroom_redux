<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

/**
 * Lifecycle descriptor of the theme, loaded by the core
 * (admin/include/themes.class.php) with include_once, expecting the class
 * "<theme_id>_maintain": bootstrap_darkroom_maintain.
 *
 * No install(), no update(): ThemeMaintain only declares activate(),
 * deactivate() and delete(), and there is no autoupdate_theme(). Nothing runs
 * when a theme's version changes; activate() (a manual reactivation) is the
 * only hook.
 *
 * This file boots its own commons: the core includes it alone, before
 * admin/admin.inc.php or themeconf.inc.php have run. The jm-* commons are
 * injected at build time and absent from a fresh clone, hence the defensive boot.
 *
 * During its own activation the theme is not active yet, and jm-boot only elects
 * among active extensions: the elected copies are other extensions' ones, and the
 * local copy is the fallback when none ships the common.
 */

$bd_maintain_boot = dirname(dirname(__FILE__)) . '/include/jm-boot.php';
if (!function_exists('jm_boot_require_one') && is_readable($bd_maintain_boot)) {
  require_once $bd_maintain_boot;
}
unset($bd_maintain_boot);

if (!function_exists('jm_boot_require_one')) {
  // Last resort: jm-boot.php absent (clone without a build), never an election.
  function jm_boot_require_one($n, $d, $c = array()) {
    $p = rtrim($d, '/\\') . '/' . $n;
    if (is_readable($p)) {
      require_once $p;
      return true;
    }
    return false;
  }
}

class bootstrap_darkroom_maintain extends ThemeMaintain {
  /** Folder of the injected commons: themes/bootstrap_darkroom/include. */
  private function inc() {
    return dirname(dirname(__FILE__)) . '/include';
  }

  public function activate($theme_version, &$errors = array()) {
    $this->ensureVendorStore();
  }

  public function deactivate() {
    // Nothing to undo. Never purge local/jm-commons/ here: the asset store is
    // shared with the fleet plugins.
  }

  public function delete() {
    // The 'bootstrap_darkroom' configuration is kept, as it always was: the theme
    // is a drop-in replacement, and a reinstall must find its settings back.
  }

  /**
   * Shared third-party asset store: installs, repairs or refreshes
   * local/jm-commons/ (fonts served by the admin panel).
   *
   * Fail-soft: jm_vendor_ensure() never throws, it logs and returns false, so an
   * activation never dies here. Loaded through jm_boot_require_one(), never a
   * bare require: an extension left behind would otherwise impose its stale copy.
   * Without an update() hook, a new revision of the store reaches a gallery when
   * the theme is reactivated, or through any fleet plugin's own update().
   */
  private function ensureVendorStore() {
    $inc = $this->inc();
    if (jm_boot_require_one('jm-http.php', $inc)
        && jm_boot_require_one('jm-vendor-pins.php', $inc)
        && jm_boot_require_one('jm-vendor.php', $inc)
        && function_exists('jm_vendor_ensure')) {
      jm_vendor_ensure();
    }
  }
}
