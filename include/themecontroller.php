<?php
namespace BootstrapDarkroom;

class ThemeController {
  private $config;

  public function __construct() {
    $this->config = new Config();
  }

  public function init() {
    load_language('theme.lang', PHPWG_THEMES_PATH.'bootstrap_darkroom/', array('force_fallback' => 'en_UK'));
    load_language('lang', PHPWG_ROOT_PATH.PWG_LOCAL_DIR, array('no_fallback'=>true, 'local'=>true) );

    add_event_handler('init', array($this, 'assignConfig'));
    add_event_handler('init', array($this, 'setInitValues'));
    add_event_handler('init', array($this, 'checkLineage'));

    if ($this->config->bootstrap_theme === 'darkroom' || $this->config->bootstrap_theme === 'material' || $this->config->bootstrap_theme === 'bootswatch') {
      // In memory only: init runs on every request. The stored value is
      // rewritten at the next explicit save (#61).
      $this->config->bootstrap_theme = 'bootstrap-darkroom';
      add_event_handler('loc_begin_page_header', array($this, 'showUpgradeWarning'));
    }

    $shortname = $this->config->comments_disqus_shortname;                                                                                                                 
    if ($this->config->comments_type == 'disqus' && !empty($shortname)) {                                                                                                  
      add_event_handler('blockmanager_apply', array($this, 'hideMenus'));                                                                                                
    }

    add_event_handler('loc_begin_about', array($this, 'assignAbout'));
    add_event_handler('blockmanager_apply', array($this, 'renameAboutLink'));
    add_event_handler('loc_begin_page_header', array($this, 'checkIfHomepage'));
    add_event_handler('loc_after_page_header', array($this, 'stripBreadcrumbs'));
    add_event_handler('format_exif_data', array($this, 'exifReplacements'));
    add_event_handler('loc_end_picture', array($this, 'registerPictureTemplates'), 1000);
    add_event_handler('loc_begin_index_thumbnails', array($this, 'returnPageStart'));
    add_event_handler('loc_end_index', array($this, 'trimDescription'));

    if ($this->config->slick_enabled === true || $this->config->photoswipe === true) {
      add_event_handler('loc_end_picture', array($this, 'getAllThumbnailsInCategory'));
      // also needed on index.tpl for compatibility with GThumb+/GDThumb
      add_event_handler('loc_end_index', array($this, 'getAllThumbnailsInCategory'));
    }
  }

  /** Files swapped in place: tells the webmaster who browses the gallery (read-only, see Lineage). */
  public function checkLineage() {
    Lineage::notifyPending(PHPWG_THEMES_PATH . 'bootstrap_darkroom/themeconf.inc.php');
  }

  public function assignConfig() {
    global $template, $conf;

    if (array_key_exists('bootstrap_darkroom_navbar_main_style', $conf) && !empty($conf['bootstrap_darkroom_navbar_main_style'])) {
      $this->config->navbar_main_style = $conf['bootstrap_darkroom_navbar_main_style'];
    }
    if (array_key_exists('bootstrap_darkroom_navbar_main_bg', $conf) && !empty($conf['bootstrap_darkroom_navbar_main_bg'])) {
      $this->config->navbar_main_bg = $conf['bootstrap_darkroom_navbar_main_bg'];
    }
    if (array_key_exists('bootstrap_darkroom_navbar_contextual_style', $conf) && !empty($conf['bootstrap_darkroom_navbar_contextual_style'])) {
      $this->config->navbar_contextual_style = $conf['bootstrap_darkroom_navbar_contextual_style'];
    }
    if (array_key_exists('bootstrap_darkroom_navbar_contextual_bg', $conf) && !empty($conf['bootstrap_darkroom_navbar_contextual_bg'])) {
      $this->config->navbar_contextual_bg = $conf['bootstrap_darkroom_navbar_contextual_bg'];
    }

    $template->assign('theme_config', $this->config);
    // The templates never read the 'view' cookie themselves: a hidden toggle
    // means an imposed view, and only the config knows that.
    $template->assign('bd_view', $this->config->effectiveView(isset($_COOKIE['view']) ? $_COOKIE['view'] : null));
  }

  /** Name of the About page: the webmaster's title, or the default one. */
  private function aboutTitle() {
    $title = About::normalizeTitle($this->config->about_title);
    return $title !== '' ? $title : l10n('bd_about_default_title');
  }

  /**
   * The About page (#75), assembled here so that about.tpl only lays it out:
   * title (breadcrumb, header and browser tab), legal notice, lineage of the theme
   * in use, mention of Piwigo, and the component list.
   *
   * A child theme adds its own lineage and components through the
   * bd_about_lineage and bd_about_credits events, never by overriding about.tpl:
   * see include/about.php.
   */
  public function assignAbout() {
    global $template, $title;

    $page_title = $this->aboutTitle();
    // about.php set $title ('About Piwigo') before this event; page_header.php
    // reads it afterwards for the <title> of the page.
    $title = $page_title;

    // Component list, generated from tools/vendor.json (the admin credits modal
    // reads the same file, plus its admin-only families).
    $credits = include PHPWG_THEMES_PATH . 'bootstrap_darkroom/include/credits.php';
    $groups = array();
    foreach (array('core', 'viewers', 'scripts', 'fonts') as $id) {
      if (!empty($credits[$id])) {
        $groups[] = array('title' => l10n('bd_credits_group_' . $id), 'items' => $credits[$id]);
      }
    }

    $lineage = trigger_change('bd_about_lineage', array(About::themeEntry()));
    $groups = trigger_change('bd_about_credits', $groups);

    $template->assign('BD_ABOUT', array(
      'title'   => $page_title,
      'notice'  => About::renderNotice($this->config->about_notice),
      'piwigo'  => About::normalizePiwigoMode($this->config->about_piwigo),
      'lineage' => About::lineageHtml($lineage, array(
        'by'         => l10n('bd_about_by'),
        'fork_of'    => l10n('bd_about_fork_of'),
        'fork_of_by' => l10n('bd_about_fork_of_by'),
        'license'    => l10n('bd_about_license'),
        'theme'      => l10n('bd_about_theme_line'),
      )),
      'credits' => About::normalizeCredits($groups),
    ));
  }

  /**
   * The menu link to the About page carries the page's name. The core builds it
   * with 'About' and 'About Piwigo'; the menu template prints both unescaped.
   */
  public function renameAboutLink($menus) {
    $menu = &$menus[0];
    $block = $menu->get_block('mbMenu');
    if ($block === null || !isset($block->data['about']) || !is_array($block->data['about'])) {
      return;
    }
    $name = htmlspecialchars($this->aboutTitle(), ENT_QUOTES, 'UTF-8');
    $block->data['about']['NAME'] = $name;
    $block->data['about']['TITLE'] = $name;
  }

  public function showUpgradeWarning() {
    global $page;
    $page['errors'][] = l10n('Your selected color style has been reset to "bootstrap-darkroom". You can select a different color style in the admin section.');
  }

  public function hideMenus($menus) {
    $menu = &$menus[0];

    $mbMenu = $menu->get_block('mbMenu');
    unset($mbMenu->data['comments']);
  }

  public function returnPageStart() {
    global $page, $template;

    $template->assign('START_ID', $page['start']);
  }

  public function checkIfHomepage() {
    global $template, $page;

    if (isset($page['is_homepage']) and $page['is_homepage']) {
      $template->assign('is_homepage', true);
    } else {
      $template->assign('is_homepage', false);
    }
  }

  public function setInitValues() {
    global $template, $pwg_loaded_plugins, $conf;

    $template->assign(array(
                           'loaded_plugins' => $GLOBALS['pwg_loaded_plugins'],
                           'meta_ref_enabled' => $conf['meta_ref']
                           ));
    if (array_key_exists('bootstrap_darkroom_core_js_in_header', $conf)) {
      $template->assign('bootstrap_darkroom_core_js_in_header', $conf['bootstrap_darkroom_core_js_in_header']);
    } else {
      $template->assign('bootstrap_darkroom_core_js_in_header', false);
    }

    if (isset($pwg_loaded_plugins['language_switch'])) {
      add_event_handler('loc_end_search', 'language_controler_flags', 95 );
      add_event_handler('loc_end_identification', 'language_controler_flags', 95 );
      add_event_handler('loc_end_tags', 'language_controler_flags', 95 );
      add_event_handler('loc_begin_about', 'language_controler_flags', 95 );
      add_event_handler('loc_end_register', 'language_controler_flags', 95 );
      add_event_handler('loc_end_password', 'language_controler_flags', 95 );
    }

    if (isset($pwg_loaded_plugins['exif_view'])) {
      load_language('lang.exif', PHPWG_PLUGINS_PATH.'exif_view/', array('force_fallback' => 'en_UK'));
    }
  }

  public function exifReplacements($exif) {
    global $conf;

    if (array_key_exists('bootstrap_darkroom_ps_exif_replacements', $conf)) {
      foreach ($conf['bootstrap_darkroom_ps_exif_replacements'] as $tag => $replacement) {
        if (is_array($exif) && array_key_exists($tag, $exif)) {
          $exif[$tag] = str_replace($replacement[0], $replacement[1], $exif[$tag]);
        }
      }
    }
    return $exif;
  }

  // register additional template files
  public function registerPictureTemplates() {
    global $template;

    $template->set_filenames(array('picture_nav'=>'picture_nav.tpl'));
    $template->parse_picture_buttons();
    $template->parse_index_buttons();
    $template->assign_var_from_handle('PICTURE_NAV', 'picture_nav');
  }

  /**
   * Drops empty paragraphs at the start and end of the album description.
   *
   * A description saved by a rich editor often ends with a blank line
   * (<p>&nbsp;</p>, <p><br></p>…): invisible on its own, it still takes a line
   * plus its margins, and unbalances the block as soon as it is framed (a
   * child theme painting a background behind it shows far more room below the
   * text than above). Display only: the stored description is untouched.
   *
   * loc_end_index runs after the core assigns CONTENT_DESCRIPTION and before
   * index.tpl is parsed. A description made only of blank lines ends up empty,
   * so the template's {if !empty($CONTENT_DESCRIPTION)} skips the block.
   */
  public function trimDescription() {
    global $template;
    $description = $template->get_template_vars('CONTENT_DESCRIPTION');
    if (is_string($description) && $description !== '') {
      $template->assign('CONTENT_DESCRIPTION', self::trimEmptyParagraphs($description));
    }
  }

  /**
   * Removes leading and trailing empty <p> / <div> blocks from an HTML fragment.
   *
   * "Empty" means nothing but whitespace, non-breaking spaces (raw, &nbsp; or
   * &#160;) and <br> tags. Blank blocks BETWEEN paragraphs are left alone: they
   * may be a deliberate spacing choice of the author.
   *
   * A block carrying an `id` or a `data-*` attribute is never treated as blank:
   * it may be a container that a plugin appended to the description and that
   * its script fills later (a map, a widget).
   *
   * @param  string $html
   * @return string The fragment, trimmed; unchanged if it is not valid UTF-8.
   */
  public static function trimEmptyParagraphs($html) {
    $blank = '(?:\s|\x{00A0}|&nbsp;|&#160;|&#xa0;|<br\s*\/?>)*';
    $attrs = '(?:\s(?![^>]*\b(?:id|data-[\w-]+)\s*=)[^>]*)?';
    $block = '<(p|div)' . $attrs . '>' . $blank . '<\/\1\s*>';
    $out = preg_replace(
      array('/^(?:\s*' . $block . ')+\s*/iu', '/\s*(?:' . $block . '\s*)+$/iu'),
      '',
      $html
    );
    return is_string($out) ? $out : $html;
  }

  public function stripBreadcrumbs() {
    global $page, $template;

    $l_sep = $template->get_template_vars('LEVEL_SEPARATOR');
    $title = $template->get_template_vars('TITLE');
    $section_title = $template->get_template_vars('SECTION_TITLE');
    if (empty($title)) {
      $title = $section_title;
    }
    if (!empty($title)) {
      $splt = strpos($title, "[");
      if ($splt) {
        $title_links = substr($title, 0, $splt);
        $title = $title_links;
      }

      $title = str_replace('<a href', '<a class="nav-breadcrumb-item" href', $title);
      $title = str_replace($l_sep, '', $title);
      if ($page['section'] == 'recent_cats' or $page['section'] == 'favorites') {
        $title = preg_replace('/<\/a>([a-zA-Z0-9]+)/', '</a><a class="nav-breadcrumb-item" href="' . make_index_url(array('section' => $page['section'])) . '">${1}', $title) . '</a>';
      }
      if (empty($section_title)) {
        $template->assign('TITLE', $title);
      } else {
        $template->assign('SECTION_TITLE', $title);
      }
    }
  }

  public function getAllThumbnailsInCategory() {
    global $template, $conf, $user, $page;

    if (!$page['items'] || ($page['section'] == 'categories' && !isset($page['category']) && !isset($page['chronology_field']) && !isset($page['flat']))) {
      return;
    }

    if (count($page['items']) > 1000) {
      $this->config->slick_enabled = false; 
      $this->config->photoswipe = false; 

      return;
    }

    // select all pictures for this category
    $query = '
            SELECT 
              id,
              file,
              representative_ext,
              name,
              comment,
              width,
              height,
              date_creation,
              path,
              rotation
            FROM '.IMAGES_TABLE.'
            WHERE id IN ('.implode(',', $page['items']).')
            ORDER BY FIELD(id, '.implode(',', $page['items']).')
            ;';

    $result = pwg_query($query);

    $pictures = array();
    while ($row = pwg_db_fetch_assoc($result)) {
      $pictures[] = $row;
    }

    $tpl_thumbnails_var = array();

    $theme_config = $template->get_template_vars('theme_config');

    foreach ($pictures as $row) {
      $url = duplicate_picture_url(
        array(
            'image_id' => $row['id'],
            'image_file' => $row['file'],
           ),
        array('start')
      );

      $name = render_element_name($row);
      $desc = render_element_description($row, 'main_page_element_description');

      $tpl_var = array_merge($row, array(
          'NAME' => $name,
          'TN_ALT' => htmlspecialchars(strip_tags($name)),
          'URL' => $url,
          'DESCRIPTION' => htmlspecialchars(strip_tags($desc)),
          'src_image' => new \SrcImage($row),
          'SIZE' => $row['width'].'x'.$row['height'],
          'PATH' => $row['path'],
          'DATE_CREATED' => $row['date_creation'],
          'file_ext' => strtolower(get_extension($row['file'])),
          'path_ext' => strtolower(get_extension($row['path'])),
      ));

      $tpl_thumbnails_var[] = $tpl_var;
    }

    $template->assign('thumbnails', $tpl_thumbnails_var);

    $template->assign(array(
        'derivative_params_square' => trigger_change('get_index_derivative_params', \ImageStdParams::get_by_type( IMG_SQUARE ) ),
        'derivative_params_medium' => trigger_change('get_index_derivative_params', \ImageStdParams::get_by_type( IMG_MEDIUM ) ),
        'derivative_params_large' => trigger_change('get_index_derivative_params', \ImageStdParams::get_by_type( IMG_LARGE ) ),
        'derivative_params_xxlarge' => trigger_change('get_index_derivative_params', \ImageStdParams::get_by_type( IMG_XXLARGE ) ),
    ));

    unset($tpl_thumbnails_var, $pictures);
  }

}

?>
