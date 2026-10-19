<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

/**
 * Entry point of the theme's admin panel, included by the core's admin/theme.php
 * (which has already built $themes) for any installed theme.
 *
 * The panel runs on the shared jm-* admin shell. Saving is an AJAX POST to this
 * very page, answered by jm_ws_admin_page_dispatch() before anything is rendered:
 * see admin/save.php for why a theme cannot use ws.php.
 *
 * The commons are injected at build time and absent from a fresh clone: every
 * one of them is loaded defensively, and the page still renders without them.
 */

global $template, $themes;

$bd_admin_dir = dirname(__FILE__);
$bd_root      = dirname($bd_admin_dir);
$bd_tpl       = $bd_admin_dir . '/template/';
$bd_url       = get_root_url() . 'themes/bootstrap_darkroom/';

// --- 1. Defensive boot of the commons ---
if (!function_exists('jm_boot_require_one') && is_readable($bd_root . '/include/jm-boot.php')) {
  require_once $bd_root . '/include/jm-boot.php';
}
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
if (!function_exists('bd_admin_require')) {
  /**
   * Loads one injected common for this panel.
   *
   * jm-boot elects the freshest copy among the ACTIVE PLUGINS only: a theme is
   * never a candidate. On a gallery running an older fleet plugin, that plugin's
   * copy would win, and the functions or keys this panel was built against
   * (admin-page save dispatcher, theme credits) would be missing. So the theme's
   * own copy goes first unless an active plugin declares a strictly fresher one.
   * Every common guards each function with function_exists(): whichever copy
   * loads second only fills the gaps, and both are loaded.
   *
   * A copy already loaded by a plugin earlier in the request (jm-ws.php is, from
   * main.inc.php) keeps its existing functions; the theme's copy adds the rest.
   *
   * @param string $name Common file name (jm-*.php).
   * @param string $dir  The theme's include/ folder.
   */
  function bd_admin_require($name, $dir) {
    $local = $dir . '/' . $name;
    $local_first = true;
    $info = is_file($dir . '/jm-build-info.php') ? include $dir . '/jm-build-info.php' : null;
    if (is_array($info) && isset($info['commons'][$name]) && is_array($info['commons'][$name])
        && function_exists('jm_boot_scan') && function_exists('jm_boot_freshness_key')) {
      $mine = jm_boot_freshness_key($info['commons'][$name]);
      foreach (jm_boot_scan() as $plugin) {
        $entry = isset($plugin['commons'][$name]) ? $plugin['commons'][$name] : null;
        if (is_array($entry) && strcmp(jm_boot_freshness_key($entry), $mine) > 0) {
          $local_first = false;
          break;
        }
      }
    }
    if ($local_first && is_readable($local)) {
      require_once $local;
    }
    jm_boot_require_one($name, $dir);
    if (is_readable($local)) {
      require_once $local;
    }
  }
}

// --- 2. Theme code ---
require_once $bd_root . '/include/config.php';
require_once $bd_admin_dir . '/save.php';
require_once $bd_admin_dir . '/export-import.php';
require_once $bd_admin_dir . '/descriptor.php';

// --- 3. Save, export and import requests: answered here, before any rendering, then exit ---
bd_admin_require('jm-ws.php', $bd_root . '/include');
bd_admin_require('jm-export-import.php', $bd_root . '/include');
if (function_exists('jm_ws_admin_page_dispatch')) {
  jm_ws_admin_page_dispatch('bootstrap_darkroom', bd_admin_save_methods() + bd_admin_expimp_methods());
}

// --- 4. Language ---
load_language('theme.lang', $bd_root . '/');

// --- 5. Settings added after 2024-12: store their default once (PHP 8 notices) ---
$bd_config = new \BootstrapDarkroom\Config();
$bd_class = new ReflectionClass('\BootstrapDarkroom\Config');
$bd_props = $bd_class->getDefaultProperties();
if (!isset($bd_config->thumbnail_nb_images)) {
  $bd_config->thumbnail_nb_images = $bd_props['defaults']['thumbnail_nb_images'];
  $bd_config->save();
}

// --- 6. Shared asset store and admin theme (light / dark) ---
// INJECT_PHP_COMMONS drops the files into include/, it does not load them.
// Plain election here: the store is fleet-wide, its freshest copy is the right
// one, and this panel needs no recent addition to it.
jm_boot_require_one('jm-vendor-pins.php', $bd_root . '/include');
jm_boot_require_one('jm-vendor.php', $bd_root . '/include');
if (function_exists('jm_vendor_assign')) {
  jm_vendor_assign($template);
}
// CodeMirror stylesheet for the Custom CSS editor (#21); its scripts are emitted by
// {jm_vendor_asset} in bd-body.tpl. Never href="": it would resolve to this page.
$bd_cm_css = function_exists('jm_vendor_url') ? jm_vendor_url('codemirror/codemirror.min.css') : null;
if ($bd_cm_css !== null && $bd_cm_css !== '') {
  $template->append('head_elements', '<link rel="stylesheet" href="' . htmlspecialchars($bd_cm_css, ENT_QUOTES) . '">');
}
$bd_theme = 'clear';
bd_admin_require('jm-theme-detector.php', $bd_root . '/include');
if (class_exists('JM_ThemeDetector')) {
  $bd_detector = new JM_ThemeDetector();
  $bd_theme = $bd_detector->getTheme();
  // data-theme on <html>/<body> too: the core's message boxes render outside
  // the .jm-plugin-admin root.
  $bd_detector->injectThemeAttribute($template);
}

// --- 7. Header, assigned by hand (jm_assign_header() lives in jm-stub.php) ---
$bd_version = isset($themes->fs_themes['bootstrap_darkroom']['version'])
  ? $themes->fs_themes['bootstrap_darkroom']['version'] : '';
$bd_descriptor = bd_admin_descriptor($bd_version);
$bd_header = $bd_descriptor['header'];
$template->assign(array(
  'JM_HEADER_PARTIAL'  => $bd_header['partial'],
  'JM_HEADER_ICON'     => $bd_header['icon'],
  'JM_HEADER_TITLE'    => $bd_header['title'],
  'JM_HEADER_VERSION'  => $bd_header['version'],
  'JM_HEADER_IS_PRO'   => false,
  'JM_HEADER_SUBTITLE' => $bd_header['subtitle'],
));

// --- 8. Accordion section header ---
$template->assign('JM_SECTION_HEADER_PARTIAL', $bd_tpl . 'jm-section-header.tpl');

// --- 9. "?" button: "documentation in progress" placeholder, no help drawer yet ---
$template->assign(array(
  'JM_HELP_WIP'             => true,
  'JM_HELP_WIP_TITLE'       => l10n('bd_help_wip_title'),
  'JM_HELP_WIP_LINE1'       => l10n('bd_help_wip_line1'),
  'JM_HELP_WIP_LINE2'       => l10n('bd_help_wip_line2'),
  'JM_HELP_WIP_CLOSE_LABEL' => l10n('bd_help_wip_close'),
  'JM_HELP_WIP_PARTIAL'     => $bd_tpl . 'jm-help-wip.tpl',
));

// --- 10. "Help & support", once, at the end of the first tab ---
// Tabbed body (shared ADMIN-SHELL § placement): detached from the shell, which would
// otherwise render it outside the tabs, under every one of them. bd-body.tpl includes
// it at the end of Appearance, after "Maintenance & advanced options".
// The else is not decorative: without it the template includes an unassigned partial.
bd_admin_require('jm-help-support.php', $bd_root . '/include');
if (function_exists('jm_help_support_boot')) {
  jm_help_support_boot($template, $bd_descriptor, array('inline' => false));
} else {
  $template->assign(array(
    'JM_HELP_SUPPORT_PARTIAL' => $bd_tpl . 'jm-help-support.tpl',
    'JM_HELP_SUPPORT_INLINE'  => false,
  ));
}

// --- 11. Credits modal ---
// The build line is read from include/jm-build-info.php, while the jm-credits-*.tpl
// partials are injected in admin/template/: the two folders are passed apart.
bd_admin_require('jm-credits.php', $bd_root . '/include');
if (function_exists('jm_credits_boot')) {
  jm_credits_boot($template, $bd_descriptor, $bd_root . '/include', $bd_tpl);
}

// --- 11b. "Maintenance & advanced options", at the end of the Appearance tab (#22) ---
// Settings export / import, then the accordion mode. The section joins the tab's
// exclusive group, like the other Appearance sections. jm_maintenance_prepare() loads
// the labels the export brick reads, so it runs first; the export messages of the
// shared script come from jm-export-import.lang.php (bd_admin_expimp_js_strings()).
bd_admin_require('jm-maintenance.php', $bd_root . '/include');
if (function_exists('jm_maintenance_prepare') && function_exists('jm_export_import_brick_assign')) {
  jm_maintenance_prepare($template, array(
    array('partial' => $bd_tpl . 'jm-brick-export-import.tpl', 'group' => 'maintenance'),
    array('brick' => 'accordion-mode', 'group' => 'advanced'),
  ), array('tpl_dir' => $bd_tpl, 'accordion_group' => 'bd-appearance'));
  jm_export_import_brick_assign($template, array(
    'label'       => l10n('jm_maint_export_import_label'),
    'tip'         => l10n('jm_maint_export_import_tip'),
    'desc'        => l10n('jm_maint_export_import_desc'),
    'export_btn'  => l10n('jm_maint_export_btn'),
    'import_btn'  => l10n('jm_maint_import_btn'),
    'feedback_id' => 'bd-import-feedback',
  ));
}
if (is_readable($bd_root . '/include/jm-export-import.lang.php')) {
  include_once $bd_root . '/include/jm-export-import.lang.php';
}

// --- 12. Stylesheets in <head>: shared design system, then the theme's delta ---
bd_admin_require('jm-asset.php', $bd_root . '/include');
$bd_asset = function ($relative) use ($bd_url, $bd_root) {
  return function_exists('jm_asset_url')
    ? jm_asset_url($bd_url, $bd_root, $relative, array('css_family' => true, 'with_version' => true))
    : $bd_url . $relative;
};
if (function_exists('jm_css_bundle')) {
  jm_css_bundle($template, $bd_asset('admin/assets/css/bd-common.css'), $bd_asset('admin/assets/css/bd-admin.css'));
}

// --- 13. Tabs ---
$bd_tabs = array('appearance', 'components', 'social');
$bd_tab = (isset($_GET['tab']) && in_array($_GET['tab'], $bd_tabs, true)) ? $_GET['tab'] : 'appearance';
$template->assign(array(
  'JM_TABS_NAV_PARTIAL' => $bd_tpl . 'jm-tabs-nav.tpl',
  'JM_TABS'             => array(
    array('id' => 'appearance', 'icon' => '&#9680;', 'label' => l10n('bd_tab_appearance')),
    array('id' => 'components', 'icon' => '&#9881;', 'label' => l10n('bd_tab_components')),
    array('id' => 'social',     'icon' => '&#8689;', 'label' => l10n('bd_tab_social')),
  ),
  'JM_TABS_ACTIVE'      => $bd_tab,
  'JM_TABS_ID_PREFIX'   => 'bd',
));

// --- 14. Savebar: one Save button, shown on the three tabs (composite key) ---
$template->assign(array(
  'JM_SHELL_SAVE_PARTIALS' => array('appearance components social' => $bd_tpl . 'bd-save.tpl'),
  'JM_SAVEBAR_ACTIVE_TAB'  => $bd_tab,
));

// --- 15. Panel data ---
$bd_json = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
// jm_asset_url() serves the .min.js variant when it exists on disk.
$bd_js = 'admin/assets/js/bd-admin.js';
$template->assign(array(
  'theme_config'     => $bd_config,
  'BD_THEME'         => $bd_theme,
  'BD_VARIANTS'      => bd_admin_variants($bd_config->bootstrap_theme),
  // Unassigned, Smarty would render an empty token and every save would be refused.
  'PWG_TOKEN'        => get_pwg_token(),
  'BD_SAVE_URL'      => get_root_url() . 'admin.php?page=theme&theme=bootstrap_darkroom',
  'BD_STRINGS_JSON'  => json_encode(bd_admin_js_strings() + array('expimp' => bd_admin_expimp_js_strings()), $bd_json),
  'BD_ADMIN_JS'      => function_exists('jm_asset_url')
    ? jm_asset_url($bd_url, $bd_root, $bd_js, array('with_version' => true))
    : $bd_url . $bd_js,
  'JM_CHOICE_GRID_PARTIAL' => $bd_tpl . 'jm-choice-grid.tpl',
));

// --- 16. Shell ---
// Not injected (clone without a build): bd-landing.tpl says so instead of a
// Smarty fatal on the missing include.
if (is_file($bd_tpl . 'jm-admin-shell.tpl')) {
  $template->assign(array(
    // Required by jm-admin-shell.tpl, which derives the savebar and fonts paths from it.
    'JM_ADMIN_SHELL_PARTIAL' => $bd_tpl . 'jm-admin-shell.tpl',
    'JM_SHELL_BODY_PARTIAL'  => $bd_tpl . 'bd-body.tpl',
  ));
}

// --- 17. Render ---
$template->set_filenames(array('bd_admin_content' => $bd_tpl . 'bd-landing.tpl'));
$template->assign_var_from_handle('ADMIN_CONTENT', 'bd_admin_content');
