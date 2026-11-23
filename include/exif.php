<?php
namespace BootstrapDarkroom;

/**
 * EXIF data of one image for the lightbox EXIF panel (#90).
 *
 * Served by the web-service method bd.images.getExif (registered by
 * ThemeController::registerWsMethods()): the lightbox fetches it when its panel
 * opens, so that no picture.php page is loaded and no visit is counted.
 *
 * rows() mirrors the core include/picture_metadata.inc.php, so the panel shows
 * the same fields, with the same labels, as the photo page. Value formatting
 * (exif_view plugin, bootstrap_darkroom_ps_exif_replacements) happens inside
 * get_exif_data() through the format_exif_data event, as on the photo page.
 */
class Exif {
  /**
   * @param array $exif       values returned by get_exif_data(), keyed by field
   * @param array $showFields $conf['show_exif_fields']
   * @param array $lang       the global $lang (exif_field_* labels)
   * @return array list of ['key' => field, 'label' => label, 'value' => string]
   */
  public static function rows(array $exif, array $showFields, array $lang) {
    $rows = array();
    foreach ($showFields as $field) {
      if (!isset($exif[$field]) || is_array($exif[$field])) {
        continue;
      }
      $name = $field;
      if (strpos($field, ';') !== false) {
        $tokens = explode(';', $field);
        $name = $tokens[1];
      }
      $label = isset($lang['exif_field_' . $name]) ? $lang['exif_field_' . $name] : $name;
      $rows[] = array(
        'key' => $field,
        'label' => (string) $label,
        'value' => (string) $exif[$field],
      );
    }
    return $rows;
  }

  /**
   * @param int $imageId
   * @return array|\PwgError ['image_id', 'title', 'fields'], or a 404 error when
   *                         the visitor cannot see the image
   */
  public static function forImage($imageId) {
    global $conf, $lang;

    $imageId = (int) $imageId;
    $query = '
SELECT *
  FROM ' . IMAGES_TABLE . '
  WHERE id = ' . $imageId
      . get_sql_condition_FandF(array('visible_images' => 'id'), ' AND') . '
  LIMIT 1
;';
    $result = pwg_query($query);
    if (pwg_db_num_rows($result) == 0) {
      return new \PwgError(404, 'image_id not found');
    }
    $row = pwg_db_fetch_assoc($result);

    $data = array(
      'image_id' => $imageId,
      'title' => l10n('EXIF Metadata'),
      'fields' => array(),
    );

    // Same gate as picture.php: no metadata for a media shown through its
    // MIME-type icon, unless a plugin says otherwise.
    $src = new \SrcImage($row);
    $showable = trigger_change(
      'get_element_metadata_available',
      !empty($conf['show_exif']) && !$src->is_mimetype(),
      $row
    );
    if (!$showable || !function_exists('exif_read_data')) {
      return $data;
    }

    $path = $src->get_path();
    if (!is_file($path)) {
      return $data;
    }

    $fields = isset($conf['show_exif_fields']) ? (array) $conf['show_exif_fields'] : array();
    $mapping = array();
    foreach ($fields as $field) {
      $mapping[$field] = $field;
    }

    // get_exif_data() lives in a core file ws.php never loads (picture.php gets it
    // through include/picture_metadata.inc.php).
    include_once(PHPWG_ROOT_PATH . 'include/functions_metadata.inc.php');
    $exif = get_exif_data($path, $mapping);
    $data['fields'] = self::rows(is_array($exif) ? $exif : array(), $fields, is_array($lang) ? $lang : array());
    return $data;
  }

  /** Callback of bd.images.getExif. */
  public static function ws($params, $service) {
    return self::forImage($params['image_id']);
  }
}
