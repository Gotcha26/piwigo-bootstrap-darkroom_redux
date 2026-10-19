/*
 * Assertions for js/plugin-buttons.js, run by tools/tests/test_plugin_buttons.py.
 * Each fixture page holds a subset of the cases; a test whose case is absent is skipped.
 * Results land in <pre id="results"> as JSON: [{name, ok, detail}].
 */
(function () {
  var results = [];

  function check(name, cond, detail) {
    results.push({name: name, ok: !!cond, detail: detail || ''});
  }

  function has(sel) {
    return document.querySelector(sel) !== null;
  }

  function txt(el) {
    return el ? (el.textContent || '').replace(/^\s+|\s+$/g, '') : null;
  }

  function run() {
    if (!window.bdPluginButtons) {
      check('normaliser loaded', false, 'window.bdPluginButtons is not defined');
      return;
    }
    // Second pass on purpose: the normaliser runs at ready and load, a third run must change nothing.
    window.bdPluginButtons.normalize();

    if (has('#osm')) {
      var osm = document.getElementById('osm');
      var osmA = osm.querySelector('a');
      check('osm icon',
        has('#osm a.nav-link > i.fas.fa-globe.fa-fw') && !osm.querySelector('.pwg-icon') &&
        !/pwg-button|pwg-state-default/.test(osmA.className) && /\bnav-item\b/.test(osm.className),
        osm.outerHTML);
      var osmLabel = osm.querySelector('span.d-lg-none.ml-2');
      check('osm label collapsed',
        txt(osmLabel) === 'Carte' && !osm.querySelector('.pwg-button-text'), osm.outerHTML);
    }

    if (has('#map-bare')) {
      var map = document.getElementById('map-bare');
      var li = map.parentNode;
      check('map wrapped',
        li.tagName === 'LI' && /\bnav-item\b/.test(li.className) && li.parentNode.tagName === 'UL' &&
        has('#map-bare i.fa-globe'), li.outerHTML);
    }

    if (has('#bd')) {
      var icon = document.querySelector('#bd .batch-downloader-icon');
      var bdLabel = document.querySelector('#bd span.d-lg-none');
      check('fa icon kept',
        icon && /\bfas\b/.test(icon.className) && /fa-cloud-download-alt/.test(icon.className) &&
        !/\bpwg-icon\b/.test(icon.className) && !icon.hasAttribute('style') &&
        txt(bdLabel) === 'Télécharger',
        document.getElementById('bd').outerHTML);
    }

    if (has('#unknown')) {
      var unknown = document.getElementById('unknown');
      var spans = $(unknown).find('a span').filter(function () {
        return txt(this) === 'Truc';
      });
      check('unknown shows label',
        !unknown.querySelector('.pwg-icon') && !unknown.querySelector('i') && spans.length === 1 &&
        !spans.hasClass('d-lg-none') && spans.is(':visible'),
        unknown.outerHTML);
    }

    if (has('#urlshackicon')) {
      var img = document.getElementById('urlshackicon');
      var wrap = img.parentNode;
      window.__shack = 0;
      $(img).trigger('click');
      check('bare img wrapped',
        wrap.tagName === 'SPAN' && /\bnav-link\b/.test(wrap.className) &&
        wrap.parentNode.tagName === 'LI' && /\bnav-item\b/.test(wrap.parentNode.className) &&
        window.__shack === 1,
        wrap.parentNode.outerHTML);
    }

    if (has('#xss')) {
      var xss = document.getElementById('xss');
      check('label is text',
        txt(xss.querySelector('a span')) === '<b>x</b>' && !xss.querySelector('b'), xss.outerHTML);
    }

    if (has('#nolabel')) {
      check('no label kept', has('#nolabel a.nav-link'), document.getElementById('nolabel').outerHTML);
    }

    function checkDropdown(prefix, linkId, boxId) {
      var link = document.getElementById(linkId);
      var box = document.getElementById(boxId);
      var li = link.parentNode;
      check(prefix,
        /\bdropdown-toggle\b/.test(link.className) && link.getAttribute('data-toggle') === 'dropdown' &&
        /\bdropdown\b/.test(li.className) && box.parentNode === li &&
        /\bdropdown-menu\b/.test(box.className) && /\bdropdown-menu-right\b/.test(box.className) &&
        !/\bswitchBox\b/.test(box.className) && !box.hasAttribute('style') &&
        $(box).children('a').length === $(box).children('a.dropdown-item').length &&
        $(box).children('.dropdown-header').length === 1 && !$(box).find('.switchBoxTitle').length &&
        !box.querySelector('br'),
        li.outerHTML);
      $(link).trigger('click');
      check(prefix + ' opens', /\bshow\b/.test(box.className), li.outerHTML);
      $(link).trigger('click');
    }

    if (has('#bdmenu')) {
      checkDropdown('switchbox registry', 'batchDownloadLink2', 'batchDownloadBox2');
    }

    if (has('#sbsibling')) {
      checkDropdown('switchbox sibling fallback', 'sbLink', 'sbBox');
    }

    if (has('#theme')) {
      var after = document.getElementById('theme').outerHTML;
      check('theme button untouched', after === window.__themeBefore, after);
    }

    var lis = $('.navbar-contextual ul.navbar-nav > li[data-bd-plugin-button]');
    var ok = lis.length > 0;
    var bad = [];
    lis.each(function () {
      var $li = $(this);
      var labels = $li.find('a > span, span.nav-link > span').not('.fa, .fas, .far, .fab').length;
      var icons = $li.find('i, .fa, .fas, .far, .fab, img').length;
      if ($li.find('li').length || labels > 1 || icons > 1) {
        ok = false;
        bad.push(this.outerHTML);
      }
    });
    check('idempotent', ok, lis.length + ' marked items; ' + bad.join(' | '));
  }

  $(window).on('load', function () {
    setTimeout(function () {
      try {
        run();
      } catch (e) {
        check('no exception', false, String(e));
      }
      $('#results').text(JSON.stringify(results));
    }, 0);
  });
})();
