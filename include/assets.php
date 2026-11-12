<?php
/**
 * Cache-busting of the theme's own stylesheets (#80).
 *
 * Piwigo loads `themes/<id>/theme.css` itself, from the core header template,
 * without any `version` key. The core then falls back to `?v<PHPWG_VERSION>`: a
 * URL that looks versioned but only moves with Piwigo, never with a theme
 * deployment or upgrade. A browser that already holds the file keeps serving the
 * old copy, so a CSS fix is invisible until the cache expires.
 *
 * The `combined_css` event receives the URL the core built and returns the one
 * it will write. Only this theme's files are rewritten, with the file's mtime.
 * Core assets, other themes and merged files (`_data/combined/*.css`, whose name
 * already carries the mtime of every source) come out untouched.
 */

if (!function_exists('bd_cache_bust_combined_css')) {
  /**
   * @param  string $href  URL built by the core, `?v<version>` included.
   * @param  object $combi Combinable; ->path is relative to the Piwigo root.
   * @return string The URL to write.
   */
  function bd_cache_bust_combined_css($href, $combi) {
    if (!is_object($combi) || !isset($combi->path)) {
      return $href;
    }
    $root = dirname(dirname(__FILE__));
    $prefix = 'themes/' . basename($root) . '/';
    if (strpos($combi->path, $prefix) !== 0) {
      return $href;
    }
    $file = $root . '/' . substr($combi->path, strlen($prefix));
    if (!is_readable($file)) {
      return $href;
    }
    // The core writes `?v<version>` without `=` and nothing else: replace the
    // whole query rather than stacking a second one.
    $query = strpos($href, '?');
    return ($query === false ? $href : substr($href, 0, $query)) . '?v' . filemtime($file);
  }
}
