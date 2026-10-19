<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

/**
 * Data of the admin panel: shared-shell descriptor, colour variants, JS strings.
 */

if (!function_exists('bd_admin_descriptor')) {
  /**
   * Descriptor read by jm_help_support_boot() and jm_credits_boot().
   *
   * @param  string $version Theme version, from the themeconf docblock.
   * @return array
   */
  function bd_admin_descriptor($version) {
    $theme_dir = dirname(dirname(__FILE__));
    $theme_url = get_root_url() . 'themes/bootstrap_darkroom/';
    return array(
      'header' => array(
        // Required: jm_help_support_boot() derives its template folder from it.
        'partial'  => $theme_dir . '/admin/template/jm-header.tpl',
        // No glyph yet: the 'bd' slug is not in the toolkit's icon catalogue, and
        // an unknown slug would render an empty box. Empty means no icon at all.
        'icon'     => '',
        'title'    => 'Bootstrap Darkroom',
        'version'  => $version,
        'subtitle' => l10n('bd_subtitle'),
      ),
      'support' => array(
        'repo' => 'https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux',
      ),
      'credits' => array(
        'kind'         => 'theme',
        'license_url'  => $theme_url . 'LICENSE.txt',
        'product_page' => false,
        'plugin_url'   => $theme_url,
        'technologies' => array('php', 'js', 'scss', 'smarty'),
        // Shared-shell fonts and icons first (served by jm-fonts.tpl and
        // bd-common.css), then the list generated from tools/vendor.json.
        'components'   => array_merge(
          array('piwigo', 'source-sans-3', 'outfit', 'simple-icons'),
          include $theme_dir . '/include/credits.php'
        ),
        'notes'        => array(
          l10n('bd_credits_note_privacy'),
          l10n('bd_credits_note_jquery'),
        ),
      ),
    );
  }
}

if (!function_exists('bd_admin_variants')) {
  /**
   * Colour variants, grouped by family, for the thumbnail grid.
   *
   * A stored value that is in no group (a variant dropped from the list) gets an
   * "Other" group of its own, so that it stays checked and is saved unchanged.
   *
   * @param  string|null $current Stored bootstrap_theme value.
   * @return array List of {title, items: [{value, label, img}]}.
   */
  function bd_admin_variants($current) {
    $families = array(
      'Bootstrap' => array(
        'bootstrap-darkroom' => 'Bootstrap Darkroom',
        'bootstrap-default'  => 'Bootstrap Default',
      ),
      'Material' => array(
        'material-darkroom'    => 'Material Darkroom',
        'material-amber'       => 'Material Amber',
        'material-blue'        => 'Material Blue',
        'material-brown'       => 'Material Brown',
        'material-deep-orange' => 'Material Deep Orange',
        'material-deep-purple' => 'Material Deep Purple',
        'material-indigo'      => 'Material Indigo',
        'material-light-green' => 'Material Light Green',
        'material-lime'        => 'Material Lime',
        'material-purple'      => 'Material Purple',
        'material-red'         => 'Material Red',
        'material-teal'        => 'Material Teal',
      ),
      'Bootswatch' => array(),
    );
    foreach (array('cerulean', 'cosmo', 'cyborg', 'darkly', 'flatly', 'journal', 'litera', 'lumen',
      'lux', 'materia', 'minty', 'pulse', 'sandstone', 'simplex', 'sketchy', 'slate', 'solar',
      'spacelab', 'superhero', 'united', 'yeti') as $name) {
      $families['Bootswatch']['bootswatch-' . $name] = 'Bootswatch ' . ucfirst($name);
    }

    $img_dir = dirname(__FILE__) . '/img/';
    $img_url = get_root_url() . 'themes/bootstrap_darkroom/admin/img/';
    $groups = array();
    $known = false;
    foreach ($families as $title => $variants) {
      $items = array();
      foreach ($variants as $value => $label) {
        $items[] = array(
          'value' => $value,
          'label' => $label,
          'img'   => is_file($img_dir . $value . '.png') ? $img_url . $value . '.png' : '',
        );
        $known = $known || $value === $current;
      }
      $groups[] = array('title' => $title, 'items' => $items);
    }
    if (!$known && is_string($current) && $current !== '') {
      $groups[] = array('title' => l10n('bd_variant_other'), 'items' => array(
        array('value' => $current, 'label' => $current, 'img' => ''),
      ));
    }
    return $groups;
  }
}

if (!function_exists('bd_admin_js_strings')) {
  /**
   * Strings bag of the admin script (window.BDAdmin.strings).
   *
   * @return array
   */
  function bd_admin_js_strings() {
    return array(
      'saved' => l10n('bd_msg_saved'),
      'error' => l10n('bd_msg_error'),
    );
  }
}
