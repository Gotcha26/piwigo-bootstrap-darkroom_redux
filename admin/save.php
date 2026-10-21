<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

/**
 * Settings save of the admin panel, served by jm_ws_admin_page_dispatch().
 *
 * A theme cannot rely on ws.php: there, the core only loads the themeconf of the
 * visitor's own theme, so an admin browsing with another theme would have no save
 * method at all. The admin page loads admin/admin.inc.php for any installed theme,
 * so the panel posts to it instead and gets the same {stat, result} envelope.
 *
 * The stored format is untouched: Config::fromPost() then save(), which writes
 * addslashes(json_encode(...)) under the 'bootstrap_darkroom' key.
 */

if (!function_exists('bd_admin_config_keys')) {
  /**
   * Names of the stored settings: the keys of Config::$defaults, minus conf_version.
   *
   * @return string[]
   */
  function bd_admin_config_keys() {
    $class = new ReflectionClass('\BootstrapDarkroom\Config');
    $props = $class->getDefaultProperties();
    return array_values(array_diff(
      array_keys($props['defaults']),
      array(\BootstrapDarkroom\Config::KEY_VERSION)
    ));
  }
}

if (!function_exists('bd_admin_save_methods')) {
  /**
   * Method table for jm_ws_admin_page_dispatch(). Only declared parameters reach
   * the callback: one per stored setting, each optional, since an unchecked
   * checkbox is simply absent from the POST.
   *
   * @return array
   */
  function bd_admin_save_methods() {
    $params = array();
    foreach (bd_admin_config_keys() as $key) {
      $params[$key] = array('default' => null);
    }
    return array(
      'settingsSave' => array(
        'callback'    => 'bd_admin_settings_save',
        'description' => 'Saves the Bootstrap Darkroom settings.',
        'params'      => $params,
      ),
    );
  }
}

if (!function_exists('bd_admin_settings_save')) {
  /**
   * Saves the posted settings. The CSRF token is already checked by the dispatcher.
   *
   * Values arrive slashed by Piwigo, as fromPost() expects (it strips them).
   * A non-string value (a field posted as an array) is dropped, never passed on.
   *
   * @param  array $params  Declared parameters, plus pwg_token.
   * @param  mixed $service Unused (no ws service on the admin page).
   * @return array|PwgError
   */
  function bd_admin_settings_save($params, &$service) {
    if (!is_admin()) {
      return new PwgError(403, 'forbidden');
    }
    $post = array();
    foreach (bd_admin_config_keys() as $key) {
      if (isset($params[$key]) && is_string($params[$key])) {
        $post[$key] = $params[$key];
      }
    }
    $config = new \BootstrapDarkroom\Config();
    $config->fromPost($post);
    $config->save();
    return array('saved' => true);
  }
}

if (!function_exists('bd_admin_vendor_methods')) {
  /**
   * Method table of the "third-party asset bundle not installed" alert, for
   * jm_ws_admin_page_dispatch(): bootstrap_darkroom.vendorRetry and
   * bootstrap_darkroom.vendorDismiss, built by the shared jm-vendor.php. The alert
   * is rendered by the shell; its buttons post to this admin page, like the save.
   *
   * jm-vendor.php absent (clone without a build) or an older copy elected: no
   * method, and no alert either, since the same common assigns it.
   *
   * @return array
   */
  function bd_admin_vendor_methods() {
    if (!function_exists('jm_ws_methods_vendor_retry')) {
      return array();
    }
    return jm_ws_methods_vendor_retry(array('enabled' => true));
  }
}
