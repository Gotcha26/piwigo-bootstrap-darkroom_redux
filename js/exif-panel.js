/*
 * EXIF panel of the PhotoSwipe lightbox (#90).
 *
 * The `i` key and the panel's `i` tab toggle a side panel with the EXIF data of
 * the current photo, fetched from the theme's web-service method
 * bd.images.getExif (no picture.php load, no visit counted).
 *
 * Pure helpers (keyAction, summarize) are exported for Node tests
 * (tests/js/test_exif_panel.test.mjs).
 */
(function (root) {
  'use strict';

  /** Summary slots, in display order, with the icons of picture_info_cards.tpl. */
  var SUMMARY_KEYS = {
    camera: ['Make', 'Model'],
    lens: ['UndefinedTag:0xA434', 'LensModel'],
    aperture: ['FNumber'],
    focal: ['FocalLength'],
    shutter: ['ExposureTime'],
    iso: ['ISOSpeedRatings'],
    bias: ['ExposureBiasValue'],
    flash: ['Flash']
  };

  var SUMMARY_ICONS = {
    camera: 'camera-compact',
    lens: 'camera-lens-h',
    aperture: 'camera-aperture',
    focal: 'camera-focal-length',
    shutter: 'camera-shutter-speed',
    iso: 'camera-iso',
    bias: 'camera-exposure',
    flash: 'camera-flash'
  };

  /**
   * What a keydown does to the panel.
   * @return {'toggle'|'close-stop'|'close'|null}
   */
  function keyAction(e, isOpen) {
    if (e.ctrlKey || e.altKey || e.metaKey) {
      return null;
    }
    var t = e.target || {};
    var tag = (t.tagName || '').toUpperCase();
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || t.isContentEditable) {
      return null;
    }
    if (e.key === 'i' || e.key === 'I') {
      return 'toggle';
    }
    if (!isOpen) {
      return null;
    }
    if (e.key === 'Escape') {
      return 'close-stop';
    }
    if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
      return 'close';
    }
    return null;
  }

  /**
   * Icon summary of the EXIF rows returned by bd.images.getExif.
   * @param {Array<{key, label, value}>} fields
   * @return {Array<{slot, icon, text}>}
   */
  function summarize(fields) {
    var byKey = {};
    for (var i = 0; i < fields.length; i++) {
      byKey[fields[i].key] = fields[i].value;
    }
    var out = [];
    for (var slot in SUMMARY_KEYS) {
      if (!Object.prototype.hasOwnProperty.call(SUMMARY_KEYS, slot)) {
        continue;
      }
      var keys = SUMMARY_KEYS[slot];
      var values = [];
      for (var k = 0; k < keys.length; k++) {
        if (byKey[keys[k]] !== undefined && byKey[keys[k]] !== '') {
          values.push(byKey[keys[k]]);
        }
      }
      if (!values.length) {
        continue;
      }
      var text;
      if (slot === 'camera') {
        text = values.join(' ');
      } else if (slot === 'aperture') {
        text = 'f/' + values[0];
      } else {
        text = values[0];
      }
      out.push({ slot: slot, icon: SUMMARY_ICONS[slot], text: text });
    }
    return out;
  }

  var api = {
    SUMMARY_KEYS: SUMMARY_KEYS,
    keyAction: keyAction,
    summarize: summarize
  };

  root.BdExifPanel = api;
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
  }
})(typeof window !== 'undefined' ? window : this);
