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
    // Credits generated from tools/vendor.json, one list per family (credits_groups).
    $credits = include $theme_dir . '/include/credits.php';
    $family = function ($id) use ($credits) {
      return isset($credits[$id]) ? $credits[$id] : array();
    };
    return array(
      'header' => array(
        // Required: jm_help_support_boot() derives its template folder from it.
        'partial'  => $theme_dir . '/admin/template/jm-header.tpl',
        // Slug of the toolkit's piwigo-logo catalogue (PLUGIN-ICON): header and credits
        // modal. Its rule lives in admin/assets/css/bd-admin.scss.
        'icon'     => 'bd',
        'title'    => 'Bootstrap Darkroom',
        'version'  => $version,
        'subtitle' => l10n('bd_subtitle'),
      ),
      'support' => array(
        'repo'            => 'https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux',
        // Rendered in the Appearance tab: joins its exclusive accordion group, like the
        // Maintenance section above it.
        'accordion_group' => 'bd-appearance',
      ),
      'credits' => array(
        'kind'         => 'theme',
        'license_url'  => $theme_url . 'LICENSE.txt',
        'license_label' => l10n('bd_credits_license'),
        'product_page' => false,
        // The forked project, credited under the author and in the thanks.
        'upstream'     => array(
          'name'   => 'Bootstrap Darkroom',
          'url'    => 'https://github.com/Piwigo/piwigo-bootstrap-darkroom',
          'author' => 'Thomas Kuther',
        ),
        'plugin_url'   => $theme_url,
        'technologies' => array('php', 'js', 'scss', 'smarty'),
        // Grouped by family, most important first (#28): a group marker opens each
        // family. The generated families come from tools/vendor.json; the shared
        // catalogue slugs stay literal here, where the toolkit's credits guard reads
        // them. Admin panel: shell fonts and icons (jm-fonts.tpl, bd-common.css) and
        // CodeMirror (Custom CSS editor), served by the shared asset store.
        'components'   => array_merge(
          array(array('group' => l10n('bd_credits_group_core')), 'piwigo'),
          $family('core'),
          array(array('group' => l10n('bd_credits_group_viewers'))),
          $family('viewers'),
          array(array('group' => l10n('bd_credits_group_scripts'))),
          $family('scripts'),
          array(array('group' => l10n('bd_credits_group_fonts'))),
          $family('fonts'),
          array(array('group' => l10n('bd_credits_group_admin')), 'source-sans-3', 'outfit', 'simple-icons', 'codemirror')
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
      'saved'        => l10n('bd_msg_saved'),
      'error'        => l10n('bd_msg_error'),
      'resetTitle'   => l10n('bd_msg_reset_title'),
      'resetConfirm' => l10n('bd_msg_reset_confirm'),
      'resetCancel'  => l10n('bd_msg_reset_cancel'),
      'resetDone'    => l10n('bd_msg_reset_done'),
      'resetError'   => l10n('bd_msg_reset_error'),
    );
  }
}
