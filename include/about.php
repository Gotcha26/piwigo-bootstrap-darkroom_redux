<?php
namespace BootstrapDarkroom;

/**
 * The About page (#75): its title, the webmaster's legal notice, the mention of
 * Piwigo, and the lineage of the theme in use.
 *
 * Pure functions, no Piwigo call except l10n() in the default lineage: the page
 * itself is assembled by ThemeController::assignAbout().
 *
 * --- For child themes (BC-BG) ---------------------------------------------------
 *
 * A child theme never overrides about.tpl. It DECLARES what it brings, and the
 * parent renders it, from the child's themeconf.inc.php:
 *
 *   // Lineage: one entry per theme, the active one first.
 *   add_event_handler('bd_about_lineage', function ($entries) {
 *     array_unshift($entries, array(
 *       'name'       => 'BC-BG',                         // required
 *       'url'        => 'https://github.com/owner/repo', // optional, http(s) only
 *       'author'     => 'Jane Doe',                      // optional
 *       'author_url' => 'https://example.org',           // optional, http(s) only
 *       'license'    => 'GPL-2.0',                       // optional
 *       'origin'     => array('name' => '…', 'url' => '…', 'author' => '…'), // optional: "fork of"
 *     ));
 *     return $entries;
 *   });
 *
 *   // Components: extra families, same shape as include/credits.php.
 *   add_event_handler('bd_about_credits', function ($groups) {
 *     $groups[] = array('title' => 'BC-BG', 'items' => array(
 *       array('name' => '…', 'url' => '…', 'author' => '…', 'license' => '…'),
 *     ));
 *     return $groups;
 *   });
 *
 * Every value is plain text: the parent escapes it, and drops a link whose
 * address is not http(s). An entry without a name is ignored.
 */
class About {
  const PIWIGO_SIMPLE = 'simple';
  const PIWIGO_ORIGINAL = 'original';
  const PIWIGO_NONE = 'none';

  /** Longest custom title kept, in characters. */
  const TITLE_MAX = 80;

  /** The fork itself: name, repository, author. */
  const FORK_URL = 'https://github.com/Gotcha26/piwigo-bootstrap-darkroom_redux';
  const FORK_AUTHOR = 'Julien Moreau';
  const FORK_AUTHOR_URL = 'https://julien-moreau.fr';
  const UPSTREAM_URL = 'https://github.com/Piwigo/piwigo-bootstrap-darkroom';
  const UPSTREAM_AUTHOR = 'Thomas Kuther';

  /**
   * Custom title as stored: plain text, one line, capped. '' means "the default".
   *
   * @param  mixed $value
   * @return string
   */
  public static function normalizeTitle($value) {
    if (!is_string($value)) {
      return '';
    }
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($value)));
    if (function_exists('mb_substr')) {
      return mb_substr($text, 0, self::TITLE_MAX, 'UTF-8');
    }
    return substr($text, 0, self::TITLE_MAX);
  }

  /**
   * What the page shows of Piwigo's own about text. Anything unknown is the default.
   *
   * @param  mixed $value
   * @return string One of the PIWIGO_* constants.
   */
  public static function normalizePiwigoMode($value) {
    $modes = array(self::PIWIGO_SIMPLE, self::PIWIGO_ORIGINAL, self::PIWIGO_NONE);
    return in_array($value, $modes, true) ? $value : self::PIWIGO_ORIGINAL;
  }

  /**
   * Loads the shared HTML sanitizer, injected at build time next to this file.
   * A copy already loaded by a plugin keeps its functions; this one fills the gaps.
   *
   * @return bool True when jm_html_sanitize() and the mail policy are available.
   */
  public static function loadSanitizer() {
    $file = __DIR__ . '/jm-html-sanitizer.php';
    if ((!function_exists('jm_html_sanitize') || !function_exists('jm_html_policy_mail')) && is_readable($file)) {
      require_once $file;
    }
    return function_exists('jm_html_sanitize') && function_exists('jm_html_policy_mail');
  }

  /**
   * True when the HTML shows nothing: no text and no image (an editor left
   * "<p>&nbsp;</p>" behind, for instance).
   *
   * @param  mixed $html
   * @return bool
   */
  public static function isBlank($html) {
    if (!is_string($html) || $html === '') {
      return true;
    }
    if (stripos($html, '<img') !== false) {
      return false;
    }
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
    return trim(str_replace("\xC2\xA0", ' ', $text)) === '';
  }

  /**
   * Legal notice, BEFORE STORAGE. No formatting limit (the editor's richest set and
   * the broadest shared policy), but active content never gets through: script,
   * iframe, form, event handlers, javascript: links. A blank notice is ''.
   *
   * Without the sanitizer (a clone without a build), only the text is kept.
   *
   * @param  mixed $html
   * @return string
   */
  public static function cleanNotice($html) {
    if (self::isBlank($html)) {
      return '';
    }
    if (self::loadSanitizer()) {
      $clean = jm_html_sanitize($html, jm_html_policy_mail());
    } else {
      $clean = strip_tags($html);
    }
    return self::isBlank($clean) ? '' : $clean;
  }

  /**
   * Legal notice, BEFORE DISPLAY: sanitized again, whatever the stored file holds
   * (it lives under local/, where anything may have been written by hand).
   *
   * Without the sanitizer, the text only, escaped, line breaks kept.
   *
   * @param  mixed $html
   * @return string HTML safe to print as is, '' when there is nothing to show.
   */
  public static function renderNotice($html) {
    if (self::isBlank($html)) {
      return '';
    }
    if (self::loadSanitizer()) {
      $clean = jm_html_sanitize($html, jm_html_policy_mail());
      return self::isBlank($clean) ? '' : $clean;
    }
    return nl2br(htmlspecialchars(trim(strip_tags($html)), ENT_QUOTES, 'UTF-8'));
  }

  /**
   * Lineage entry of this theme: the fork, its author, and where it comes from.
   *
   * @return array
   */
  public static function themeEntry() {
    return array(
      'name'       => 'Bootstrap Darkroom (redux)',
      'url'        => self::FORK_URL,
      'author'     => self::FORK_AUTHOR,
      'author_url' => self::FORK_AUTHOR_URL,
      'license'    => 'Apache 2.0',
      'origin'     => array(
        'name'   => 'Bootstrap Darkroom',
        'url'    => self::UPSTREAM_URL,
        'author' => self::UPSTREAM_AUTHOR,
      ),
    );
  }

  /**
   * An address kept as a link: absolute http(s) only.
   *
   * @param  mixed $url
   * @return string '' when it is not one.
   */
  public static function safeUrl($url) {
    if (!is_string($url)) {
      return '';
    }
    $url = trim($url);
    return preg_match('#^https?://[^\s<>"\']+$#i', $url) ? $url : '';
  }

  /**
   * Text, escaped, wrapped in a link when the address is safe.
   *
   * @param  string $text
   * @param  mixed  $url
   * @return string HTML
   */
  private static function link($text, $url) {
    $html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $url = self::safeUrl($url);
    if ($url === '') {
      return $html;
    }
    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . $html . '</a>';
  }

  /**
   * One plain-text field of a declaration, '' when absent or not a string.
   */
  private static function text(array $entry, $key) {
    return isset($entry[$key]) && is_string($entry[$key]) ? trim(strip_tags($entry[$key])) : '';
  }

  /**
   * Lineage lines, as HTML, from the declared entries (the theme's own and those a
   * child theme added). Every value is escaped here: a declaration is data.
   *
   * @param  mixed $entries List of entries (see the class docblock).
   * @param  array $strings Sentence patterns: by, fork_of, license (sprintf, %1$s / %2$s).
   * @return string[]
   */
  public static function lineageHtml($entries, array $strings) {
    $lines = array();
    if (!is_array($entries)) {
      return $lines;
    }
    foreach ($entries as $entry) {
      if (!is_array($entry)) {
        continue;
      }
      $name = self::text($entry, 'name');
      if ($name === '') {
        continue;
      }
      $line = self::link($name, isset($entry['url']) ? $entry['url'] : '');
      $author = self::text($entry, 'author');
      if ($author !== '') {
        $line = sprintf($strings['by'], $line, self::link($author, isset($entry['author_url']) ? $entry['author_url'] : ''));
      }
      if (isset($entry['origin']) && is_array($entry['origin'])) {
        $origin = self::text($entry['origin'], 'name');
        if ($origin !== '') {
          $origin_html = self::link($origin, isset($entry['origin']['url']) ? $entry['origin']['url'] : '');
          $origin_author = self::text($entry['origin'], 'author');
          $line .= ', ' . ($origin_author !== ''
            ? sprintf($strings['fork_of_by'], $origin_html, htmlspecialchars($origin_author, ENT_QUOTES, 'UTF-8'))
            : sprintf($strings['fork_of'], $origin_html));
        }
      }
      $line .= '.';
      $license = self::text($entry, 'license');
      if ($license !== '') {
        $line .= ' ' . sprintf($strings['license'], htmlspecialchars($license, ENT_QUOTES, 'UTF-8'));
      }
      $lines[] = $line;
    }
    return $lines;
  }

  /**
   * Component families, checked: a title and items with a name, plain text only.
   * The template escapes every value; this only drops malformed declarations.
   *
   * @param  mixed $groups
   * @return array List of {title, items: [{name, url, author, license}]}.
   */
  public static function normalizeCredits($groups) {
    $out = array();
    if (!is_array($groups)) {
      return $out;
    }
    foreach ($groups as $group) {
      if (!is_array($group) || !isset($group['items']) || !is_array($group['items'])) {
        continue;
      }
      $items = array();
      foreach ($group['items'] as $item) {
        if (!is_array($item) || self::text($item, 'name') === '') {
          continue;
        }
        $items[] = array(
          'name'    => self::text($item, 'name'),
          'url'     => self::safeUrl(isset($item['url']) ? $item['url'] : ''),
          'author'  => self::text($item, 'author'),
          'license' => self::text($item, 'license'),
        );
      }
      if ($items) {
        $out[] = array('title' => self::text($group, 'title'), 'items' => $items);
      }
    }
    return $out;
  }
}
