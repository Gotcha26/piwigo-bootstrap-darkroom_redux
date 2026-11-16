<?php
namespace BootstrapDarkroom;

/**
 * Colour schemes of the skins (css/<skin>/), and the visitor's light / dark mode (#81).
 *
 * One place for the light / dark split, read by the public side (themeconf
 * colorscheme, mode resolution) and by the admin panel (one picker per scheme).
 *
 * The visitor's mode lives in the core's own 'mode' cookie ('light' or 'dark'),
 * the one themes/standard_pages/js/standard_pages.js writes: the gallery and the
 * core standard pages (identification, register, password, profile) share it.
 */
class Skins {

  const LIGHT = 'light';
  const DARK = 'dark';

  /** Name of the core cookie, and its lifetime in the core (30 days). */
  const COOKIE = 'mode';
  const COOKIE_DAYS = 30;

  /** Skins with a light background. Every other skin is dark. */
  private static $light = array(
    'bootstrap-default',
    'material-amber',
    'material-blue',
    'material-brown',
    'material-deep-orange',
    'material-deep-purple',
    'material-indigo',
    'material-light-green',
    'material-lime',
    'material-purple',
    'material-red',
    'material-teal',
    'bootswatch-cerulean',
    'bootswatch-cosmo',
    'bootswatch-flatly',
    'bootswatch-journal',
    'bootswatch-litera',
    'bootswatch-lumen',
    'bootswatch-lux',
    'bootswatch-materia',
    'bootswatch-minty',
    'bootswatch-pulse',
    'bootswatch-sandstone',
    'bootswatch-simplex',
    'bootswatch-sketchy',
    'bootswatch-spacelab',
    'bootswatch-united',
    'bootswatch-yeti',
  );

  /**
   * Colour scheme of a skin.
   *
   * @param  string|null $skin Skin folder name (bootstrap_theme value).
   * @return string self::LIGHT or self::DARK.
   */
  public static function scheme($skin) {
    return in_array($skin, self::$light, true) ? self::LIGHT : self::DARK;
  }

  /**
   * A mode value, or null when it is not one: cookies and headers are visitor input.
   *
   * @param  mixed $value Raw value ('light', 'dark', or anything else).
   * @return string|null
   */
  public static function normalizeMode($value) {
    if (!is_string($value)) {
      return null;
    }
    $value = strtolower(trim($value, " \t\""));
    return ($value === self::LIGHT || $value === self::DARK) ? $value : null;
  }

  /**
   * The mode a visitor asks for, in a light / dark gallery.
   *
   * 1. The 'mode' cookie: the visitor's switch, or the core standard pages.
   * 2. The Sec-CH-Prefers-Color-Scheme client hint: the OS preference, sent by the
   *    browsers that support it once the gallery has asked for it (Accept-CH).
   * 3. Nothing known (first request, no hint): null. The page is served light,
   *    the default of the core standard pages for a browser without a dark
   *    preference, and a script in <head> corrects it from matchMedia (header.tpl).
   *
   * @param  mixed $cookie Raw 'mode' cookie, null when absent.
   * @param  mixed $hint   Raw Sec-CH-Prefers-Color-Scheme header, null when absent.
   * @return array {mode: string|null, source: 'cookie'|'hint'|null}
   */
  public static function requested($cookie, $hint) {
    $mode = self::normalizeMode($cookie);
    if ($mode !== null) {
      return array('mode' => $mode, 'source' => 'cookie');
    }
    $mode = self::normalizeMode($hint);
    if ($mode !== null) {
      return array('mode' => $mode, 'source' => 'hint');
    }
    return array('mode' => null, 'source' => null);
  }
}
