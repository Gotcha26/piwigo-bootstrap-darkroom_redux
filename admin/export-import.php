<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

/**
 * Settings export / import of the admin panel (#22), on the shared export_import
 * block (jm-export-import.php, doctrine CONFIG-EXPORT-IMPORT).
 *
 * A theme has no ws.php method: the shared configExport / configImport methods are
 * merged into the table that jm_ws_admin_page_dispatch() serves on the admin page,
 * next to settingsSave.
 *
 * The exported file is flat: every stored setting, with the Custom CSS as the text
 * of local/bootstrap_darkroom/custom.css (it is stored as a file, not in the conf).
 * The import goes through Config::fromPost() then save(), so the stored format
 * stays exactly the one a form save writes.
 */

if (!function_exists('bd_admin_config_types')) {
  /**
   * Type of each stored setting (Config::$types), keyed like bd_admin_config_keys().
   *
   * @return array<string, string>
   */
  function bd_admin_config_types() {
    $class = new ReflectionClass('\BootstrapDarkroom\Config');
    $props = $class->getDefaultProperties();
    return $props['types'];
  }
}

if (!function_exists('bd_admin_config_snapshot')) {
  /**
   * Current settings, as exported: the loaded configuration as stored (a stored
   * null stays null), and the Custom CSS read from its file. Only a key missing
   * from an old stored row falls back to its default, so the file always carries
   * every setting.
   *
   * @return array
   */
  function bd_admin_config_snapshot() {
    $class = new ReflectionClass('\BootstrapDarkroom\Config');
    $props = $class->getDefaultProperties();
    $config = new \BootstrapDarkroom\Config();
    $stored = $config->toArray();
    $types = bd_admin_config_types();
    $snapshot = array();
    foreach (bd_admin_config_keys() as $key) {
      if ($types[$key] === \BootstrapDarkroom\Config::TYPE_FILE) {
        $snapshot[$key] = $config->$key;
      } else {
        $snapshot[$key] = array_key_exists($key, $stored) ? $stored[$key] : $props['defaults'][$key];
      }
    }
    return $snapshot;
  }
}

if (!function_exists('bd_admin_import_post')) {
  /**
   * Turns an imported file into the POST that Config::fromPost() expects.
   *
   * - A key missing from the file keeps its current value: fromPost() reads an
   *   absent boolean as an unchecked box (false), and an absent Custom CSS as an
   *   empty one, which would delete custom.css.
   * - A non-scalar value (array, object) is ignored the same way.
   * - Booleans become '1' or are left out, as a checkbox would be posted. A string
   *   "false" or "0" stays false, where PHP would read it as true.
   * - Strings are slashed: fromPost() strips slashes, as Piwigo slashes its POST.
   *   Without it, a backslash in the Custom CSS (content: "\f101") would be lost.
   *
   * @param  array $data    Imported settings, markers already removed.
   * @param  array $current Current settings (bd_admin_config_snapshot()).
   * @return array<string, string>
   */
  function bd_admin_import_post(array $data, array $current) {
    $types = bd_admin_config_types();
    $post = array();
    foreach (bd_admin_config_keys() as $key) {
      $value = array_key_exists($key, $data) && (is_scalar($data[$key]) || $data[$key] === null)
        ? $data[$key]
        : $current[$key];
      if ($types[$key] === \BootstrapDarkroom\Config::TYPE_BOOL) {
        if ($value === true || $value === 1 || $value === '1' || $value === 'true') {
          $post[$key] = '1';
        }
        continue;
      }
      if ($value === null || $value === false) {
        continue;
      }
      $post[$key] = addslashes((string) $value);
    }
    return $post;
  }
}

if (!function_exists('bd_admin_config_import')) {
  /**
   * 'save' hook of the shared import: persists the validated settings.
   *
   * @param  array $data Imported settings, markers already removed.
   * @return bool True on success (the shared block answers save_failed otherwise).
   */
  function bd_admin_config_import(array $data) {
    if (!is_admin()) {
      return false;
    }
    $config = new \BootstrapDarkroom\Config();
    $config->fromPost(bd_admin_import_post($data, bd_admin_config_snapshot()));
    $config->save();
    return true;
  }
}

if (!function_exists('bd_admin_expimp_methods')) {
  /**
   * Shared configExport / configImport methods, for jm_ws_admin_page_dispatch().
   * Empty when the shared block is not shipped (clone without a build).
   *
   * @return array
   */
  function bd_admin_expimp_methods() {
    if (!function_exists('jm_ws_methods_export_import')) {
      return array();
    }
    return jm_ws_methods_export_import(array(
      'config' => array(
        'plugin_id'      => 'bootstrap_darkroom',
        'schema_version' => \BootstrapDarkroom\Config::CONF_VERSION,
        'snapshot'       => 'bd_admin_config_snapshot',
        // No 'licensed' hook: its refusal reads "Pro licence required". The admin
        // page is admin-only, and the save hook checks is_admin() again.
        'save'           => 'bd_admin_config_import',
      ),
    ));
  }
}

if (!function_exists('bd_admin_expimp_js_strings')) {
  /**
   * Messages of the shared export / import script. The shared catalogue
   * (window.jm.strings) is published on loc_begin_admin_page, which a theme's admin
   * page reaches too late: the theme hands the same jm-export-import.lang.php
   * strings to the script itself, under the keys it looks up (import_err_wrong_plugin
   * becomes importErrWrongPlugin), with the theme name in the %s ones.
   *
   * @return array<string, string>
   */
  function bd_admin_expimp_js_strings() {
    global $lang;
    $strings = array();
    foreach ((array) $lang as $key => $text) {
      if (strpos($key, 'jm_ei_') !== 0 || !is_string($text)) {
        continue;
      }
      $name = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', substr($key, 6)))));
      $strings[$name] = strpos($text, '%s') !== false ? sprintf($text, 'Bootstrap Darkroom') : $text;
    }
    return $strings;
  }
}
