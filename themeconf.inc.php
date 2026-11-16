<?php
/*
Theme Name: Bootstrap Darkroom (redux)
Version: 0.2.0
Description: A mobile-ready & feature-rich theme based on Bootstrap 4, with PhotoSwipe full-screen slideshow, Slick carousel, over 30 color styles and lots of configuration options. Redux: independent fork of Bootstrap Darkroom by Thomas Kuther, developed on its own and distributed through its own page in the Piwigo extension repository (fork).
Theme URI: https://piwigo.org/ext/extension_view.php?eid=1122
GitHub URI : https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux
Author: Gotcha, Thomas Kuther
Author URI: https://julien-moreau.fr
*/
require_once(PHPWG_THEMES_PATH . 'bootstrap_darkroom/include/themecontroller.php');
require_once(PHPWG_THEMES_PATH . 'bootstrap_darkroom/include/config.php');
require_once(PHPWG_THEMES_PATH . 'bootstrap_darkroom/include/lineage.php');
require_once(PHPWG_THEMES_PATH . 'bootstrap_darkroom/include/assets.php');
add_event_handler('combined_css', 'bd_cache_bust_combined_css');

$themeconf = array(
    'name' => 'bootstrap_darkroom',
    'parent' => 'default',
    'load_parent_css' => false,
    'load_parent_local_head' => true,
    'local_head' => 'local_head.tpl',
    'url' => 'https://kuther.net/',
    'colorscheme' => 'dark',
    'use_standard_pages' => false,
);

//debug
//$conf['template_combine_files'] = false;

// always show metadata initially
pwg_set_session_var('show_metadata', true);

// register video files
$video_ext = array('mp4','m4v');
$conf['file_ext'] = array_merge ($conf['file_ext'], $video_ext, array_map('strtoupper', $video_ext));

$controller = new \BootstrapDarkroom\ThemeController();


// Skin of this request (single skin, or the visitor's light / dark choice, #81)
// and its colour scheme. The light / dark split lives in include/skins.php.
$themeconf['colorscheme'] = $controller->resolveColorScheme();

$controller->init();