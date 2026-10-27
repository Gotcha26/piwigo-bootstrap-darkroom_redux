<?php
/**
 * Config::reset() puts the WHOLE theme back to its defaults, except the typed
 * content listed in $preserve_on_reset (fleet doctrine CONFIG-MANAGER,
 * "_preserve_on_reset"; brick "reset" of the Maintenance section, piwigo_local#1429).
 *
 * METHOD: the real include/config.php, with the Piwigo boundary doubled
 * ($conf, conf_update_param(), PHPWG_ROOT_PATH / PWG_LOCAL_DIR on a temporary
 * folder). Every setting is first moved off its default, then reset() is called,
 * and the stored row is read back the way the theme reads it (json_decode).
 *
 * Run: php tests/test_config_reset.php
 */

$fail = 0;
function bd_assert($c, $m) {
  global $fail;
  if (!$c) {
    fwrite(STDERR, "FAIL: $m\n");
    $fail++;
  } else {
    echo "ok: $m\n";
  }
}

$tmp = sys_get_temp_dir() . '/bd_reset_' . getmypid() . '_' . uniqid();
@mkdir($tmp, 0777, true);
define('PHPWG_ROOT_PATH', $tmp . '/');
define('PWG_LOCAL_DIR', 'local/');

$conf = array();
$writes = 0;
function conf_update_param($param, $value) {
  global $conf, $writes;
  // The theme slashes its JSON for the INSERT; the row read back is unslashed.
  $conf[$param] = stripslashes($value);
  $writes++;
}

require dirname(__DIR__) . '/include/config.php';

$class = new ReflectionClass('\BootstrapDarkroom\Config');
$props = $class->getDefaultProperties();
$defaults = $props['defaults'];
$types = $props['types'];
$preserved = $props['preserve_on_reset'];
$css_file = $tmp . '/local/bootstrap_darkroom/custom.css';

// --- 1. Every setting off its default, typed content filled in ---
$config = new \BootstrapDarkroom\Config();
$post = array();
foreach ($types as $key => $type) {
  switch ($type) {
    case 'bool':
      if (!$defaults[$key]) {
        $post[$key] = '1';
      }
      break;
    case 'numeric':
      $post[$key] = '9999';
      break;
    default:
      $post[$key] = 'changed-' . $key;
  }
}
$post['custom_css'] = 'body { color: red; }';
$post['logo_image_path'] = 'i.php?/upload/logo.png';
$post['page_header_image'] = 'https://example.org/header.jpg';
$post['comments_disqus_shortname'] = 'mygallery';
$config->fromPost($post);
$config->save();
bd_assert(is_file($css_file) && file_get_contents($css_file) === 'body { color: red; }', 'setup: custom.css written');

// --- 2. Reset ---
$writes = 0;
$config = new \BootstrapDarkroom\Config();
$config->reset();
bd_assert($writes === 1, 'reset persists in ONE conf_update_param() (' . $writes . ')');

$stored = json_decode($conf['bootstrap_darkroom'], true);
bd_assert(is_array($stored) && $stored['conf_version'] === \BootstrapDarkroom\Config::CONF_VERSION, 'the stored row is valid JSON, current conf_version');
bd_assert(!array_key_exists('preserve_on_reset', $stored) && !array_key_exists('_preserve_on_reset', $stored),
  'the preserve list is never stored');

foreach ($defaults as $key => $default) {
  if (in_array($key, $preserved, true) || $types[$key] === 'file') {
    continue;
  }
  bd_assert(array_key_exists($key, $stored) && $stored[$key] === $default,
    "setting '$key' back to its default (" . var_export(isset($stored[$key]) ? $stored[$key] : null, true) . ')');
}

bd_assert($stored['logo_image_path'] === 'i.php?/upload/logo.png', 'content kept: logo_image_path');
bd_assert($stored['page_header_image'] === 'https://example.org/header.jpg', 'content kept: page_header_image');
bd_assert($stored['comments_disqus_shortname'] === 'mygallery', 'content kept: comments_disqus_shortname');
bd_assert(is_file($css_file) && file_get_contents($css_file) === 'body { color: red; }', 'content kept: custom.css untouched');

// The switches of the kept content are settings: they are reset.
bd_assert($stored['logo_image_enabled'] === false && $stored['comments_type'] === 'piwigo',
  'the switches of the kept content are reset (logo_image_enabled, comments_type)');

// --- 3. The theme reads the reset row back as is ---
$reread = new \BootstrapDarkroom\Config();
bd_assert($reread->bootstrap_theme === 'material-darkroom' && $reread->custom_css === 'body { color: red; }',
  'a fresh Config reads the reset row (defaults + kept content)');

// --- 4. The preserve list names real keys, all of them typed content ---
foreach ($preserved as $key) {
  bd_assert(array_key_exists($key, $defaults) && in_array($types[$key], array('string', 'file'), true),
    "preserved '$key' is a real key holding free text");
}

// Cleanup.
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
foreach ($it as $f) {
  $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
}
@rmdir($tmp);

exit($fail === 0 ? 0 : 1);
