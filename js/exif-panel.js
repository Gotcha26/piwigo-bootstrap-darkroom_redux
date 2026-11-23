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

  /** Responses per image id, for the page's lifetime. Failed requests are evicted. */
  var cache = {};

  function fetchExif($, ws, id) {
    if (!cache[id]) {
      cache[id] = $.ajax({
        url: ws,
        data: { format: 'json', method: 'bd.images.getExif', image_id: id },
        dataType: 'json'
      }).then(function (r) {
        if (!r || r.stat !== 'ok' || !r.result) {
          return $.Deferred().reject().promise();
        }
        return r.result;
      });
      cache[id].fail(function () {
        delete cache[id];
      });
    }
    return cache[id];
  }

  /**
   * Binds the panel (#pswp-exif, _photoswipe_div.tpl) to one PhotoSwipe
   * instance, right after its init(). Cleans itself up on the instance's destroy.
   */
  function attach(pswp) {
    var $ = root.jQuery;
    var panel = root.document && root.document.getElementById('pswp-exif');
    if (!$ || !panel) {
      return;
    }
    var $panel = $(panel);
    var $toggle = $panel.find('.pswp-exif__toggle');
    var $title = $panel.find('.pswp-exif__title');
    var $summary = $panel.find('.pswp-exif__summary');
    var $table = $panel.find('.pswp-exif__table');
    var $status = $panel.find('.pswp-exif__status');
    var ws = $panel.attr('data-ws');

    // Items built from templates without data-image-id (a child theme's own
    // copies) have no id: the panel then stays hidden.
    function currentId() {
      var item = pswp.currItem;
      return item && item.imageId ? String(item.imageId) : null;
    }

    function isOpen() {
      return $panel.hasClass('open');
    }

    function clear() {
      $title.text('');
      $summary.empty();
      $table.empty();
      $status.text('');
    }

    function render(result) {
      clear();
      $title.text(result.title || '');
      var fields = result.fields || [];
      if (!fields.length) {
        $status.text($panel.attr('data-empty') || '');
        return;
      }
      var summary = summarize(fields);
      for (var i = 0; i < summary.length; i++) {
        $summary.append(
          $('<div></div>').addClass(summary[i].slot).append(
            $('<span aria-hidden="true"></span>').addClass(summary[i].icon),
            $('<span></span>').text(summary[i].text)
          )
        );
      }
      for (var j = 0; j < fields.length; j++) {
        $table.append(
          $('<div class="pswp-exif__row"></div>').append(
            $('<dt></dt>').text(fields[j].label),
            $('<dd></dd>').text(fields[j].value)
          )
        );
      }
    }

    function load() {
      var id = currentId();
      if (!id) {
        return;
      }
      clear();
      fetchExif($, ws, id).then(function (result) {
        // A late answer for a photo the visitor has already left, or for a
        // panel closed in the meantime, is dropped (reopening renders it).
        if (isOpen() && currentId() === id && String(result.image_id) === id) {
          render(result);
        }
      }, function () {
        if (isOpen() && currentId() === id) {
          clear();
          $status.text($panel.attr('data-error') || '');
        }
      });
    }

    function setOpen(open) {
      $panel.toggleClass('open', open);
      $toggle.attr('aria-expanded', open ? 'true' : 'false');
      if (open) {
        load();
      }
    }

    function sync() {
      if (currentId()) {
        $panel.removeClass('d-none');
        if (isOpen()) {
          load();
        }
      } else {
        setOpen(false);
        $panel.addClass('d-none');
      }
    }

    // Capture phase on window: runs before PhotoSwipe's and the #89 sidebar's
    // keydown handlers (both on document), so Esc can be kept from them.
    function onKey(e) {
      var action = keyAction(e, isOpen());
      if (action === 'toggle') {
        if (currentId()) {
          e.preventDefault();
          setOpen(!isOpen());
        }
      } else if (action === 'close-stop') {
        e.preventDefault();
        e.stopPropagation();
        setOpen(false);
      } else if (action === 'close') {
        setOpen(false);
      }
    }

    $toggle.on('click.bdexif', function (e) {
      e.preventDefault();
      setOpen(!isOpen());
    });
    root.addEventListener('keydown', onKey, true);
    pswp.listen('afterChange', sync);
    // 'destroy' only fires once the closing animation is over: close the panel
    // as soon as the lightbox starts closing.
    pswp.listen('close', function () {
      setOpen(false);
    });
    pswp.listen('destroy', function () {
      setOpen(false);
      clear();
      $panel.addClass('d-none');
      root.removeEventListener('keydown', onKey, true);
      $toggle.off('click.bdexif');
    });
    sync();
  }

  var api = {
    SUMMARY_KEYS: SUMMARY_KEYS,
    keyAction: keyAction,
    summarize: summarize,
    attach: attach
  };

  root.BdExifPanel = api;
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api;
  }
})(typeof window !== 'undefined' ? window : this);
