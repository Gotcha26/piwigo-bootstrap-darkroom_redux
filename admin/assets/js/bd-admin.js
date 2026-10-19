/**
 * bd-admin.js: Bootstrap Darkroom admin panel, on the shared jm-* shell.
 * Strings: window.BDAdmin.strings (no interface text hard-coded here).
 *
 * Auto-inclusion mentions (minify.js only scans THIS file, and does not follow
 * dependencies): jm.createTabs, jm.createAccordion, jm.sectionColumns, jm.tooltip,
 * jm.Credits, jm.modal, jm.helpWip, jm.flash, jm.flashServerMessages,
 * jm.createDirtyTracker, jm.createFormSave, jm.formSaveButtonState, jm.ws,
 * jm.createDataActionRouter, jm.createCodeEditor, jm.createAccordionModeControl,
 * jm.createUiPref, jm.createSegmentedControl, jm.exportImport.
 *
 * Saving posts to the theme's own admin page (BDAdmin.saveUrl), which
 * jm_ws_admin_page_dispatch() answers: a theme has no reliable ws.php method.
 */
(function (window, document) {
  'use strict';

  var A = window.BDAdmin = window.BDAdmin || {};
  var jm = window.jm || {};
  var TABS = ['appearance', 'components', 'social'];

  function field(form, name) {
    return form.elements.namedItem(name);
  }

  function valueOf(form, name) {
    var el = field(form, name);
    if (!el) return null;
    if (el.type === 'checkbox') return el.checked;
    return el.value; // a RadioNodeList or a select gives the selected value
  }

  /* Dependent fields: data-bd-when="<checkbox>" or "<field>=<value>". Hidden while
     their parent is off (#32), with the hidden attribute, never disabled: a disabled
     field would be left out of the POST, and its stored value wiped; a hidden one is
     still posted. The server renders the same initial state. */
  function syncDependents(form) {
    var rows = form.querySelectorAll('[data-bd-when]');
    for (var i = 0; i < rows.length; i++) {
      var rule = rows[i].getAttribute('data-bd-when').split('=');
      var current = valueOf(form, rule[0]);
      var on = rule.length > 1 ? current === rule[1] : current === true;
      rows[i].hidden = !on;
    }
  }

  /* Navbar style and background follow the colour variant (hidden fields), as in
     the former panel. */
  function navbarFor(variant) {
    var nav = {
      navbar_main_style: 'navbar-dark',
      navbar_main_bg: 'bg-dark',
      navbar_contextual_style: 'navbar-dark',
      navbar_contextual_bg: 'bg-light'
    };
    if (variant === 'bootstrap-default') {
      nav.navbar_contextual_style = 'navbar-light';
    } else if (/^material-(amber|lime)/.test(variant)) {
      nav.navbar_contextual_style = 'navbar-light';
      nav.navbar_contextual_bg = 'bg-primary';
    } else if (/^material/.test(variant)) {
      nav.navbar_contextual_bg = 'bg-primary';
    } else if (/^bootswatch-(litera|simplex)/.test(variant)) {
      nav.navbar_main_style = 'navbar-light';
      nav.navbar_main_bg = 'bg-light';
      nav.navbar_contextual_style = 'navbar-light';
      nav.navbar_contextual_bg = 'bg-light';
    } else if (/^bootswatch-(cyborg|solar)/.test(variant)) {
      nav.navbar_main_bg = 'bg-dark';
      nav.navbar_contextual_bg = 'bg-dark';
    } else if (/^bootswatch/.test(variant)) {
      nav.navbar_main_bg = 'bg-primary';
      nav.navbar_contextual_bg = 'bg-primary';
    }
    return nav;
  }

  function applyNavbar(form) {
    var nav = navbarFor(valueOf(form, 'bootstrap_theme') || '');
    for (var name in nav) {
      var el = field(form, name);
      if (el) el.value = nav[name];
    }
  }

  /* Thumbnails can only open PhotoSwipe while PhotoSwipe is enabled. */
  function syncLinkTo(form) {
    var enabled = valueOf(form, 'photoswipe') === true;
    var select = field(form, 'thumbnail_linkto');
    if (!select) return;
    for (var i = 0; i < select.options.length; i++) {
      if (select.options[i].value !== 'picture') select.options[i].disabled = !enabled;
    }
    if (!enabled && select.value !== 'picture') select.value = 'picture';
  }

  function initForm(form) {
    syncDependents(form);
    syncLinkTo(form);
    form.addEventListener('change', function (e) {
      var name = e.target && e.target.name;
      if (name === 'bootstrap_theme') applyNavbar(form);
      if (name === 'photoswipe') syncLinkTo(form);
      syncDependents(form);
    });
  }

  function saveButton() {
    return document.querySelector('#bd-admin [data-action="config-save"]');
  }

  function initSave(form) {
    if (!jm.createFormSave) return null;
    var dirty = jm.createDirtyTracker ? jm.createDirtyTracker({
      capture: function () { return new URLSearchParams(new FormData(form)).toString(); },
      target: saveButton,
      triggers: function (h) {
        form.addEventListener('input', h.onChange);
        form.addEventListener('change', h.onChange);
        return function () {
          form.removeEventListener('input', h.onChange);
          form.removeEventListener('change', h.onChange);
        };
      }
    }) : null;
    return jm.createFormSave({
      form: form,
      btn: saveButton(),
      wsMethod: 'bootstrap_darkroom.settingsSave',
      wsUrl: A.saveUrl,
      getToken: function () { return A.pwgToken; },
      strings: A.strings || {},
      dirty: dirty
    });
  }

  /* Settings export / import (#22), served like the save by the theme's admin page.
     The messages come from BDAdmin.strings.expimp: the shared catalogue is published
     too late for a theme's admin page. */
  function initExportImport() {
    if (!jm.exportImport || !document.getElementById('bd-import-feedback')) return null;
    return jm.exportImport.create({
      wsExportMethod: 'bootstrap_darkroom.configExport',
      wsImportMethod: 'bootstrap_darkroom.configImport',
      wsUrl: A.saveUrl,
      filename: 'bootstrap_darkroom-config.json',
      feedbackId: 'bd-import-feedback',
      token: function () { return A.pwgToken; },
      strings: function () { return (A.strings && A.strings.expimp) || {}; }
    });
  }

  function init() {
    if (!document.getElementById('bd-admin')) return;
    jm = window.jm || {};
    if (jm.createTabs) {
      jm.createTabs({ namespace: 'bootstrap_darkroom', validTabs: TABS, idPrefix: 'bd', rootSelector: '#bd-admin' }).init();
    }
    // Accordion mode, a per-browser preference set in the Maintenance section (#22).
    // "Expand all" opens every section, hidden tabs included: they are open when
    // the admin switches to them.
    var mode = jm.createAccordionModeControl ? jm.createAccordionModeControl({
      key: 'bootstrap_darkroom-accordion-mode',
      expandAllScope: function () { return document.querySelectorAll('#bd-admin .jm-section'); }
    }) : null;
    if (jm.createAccordion) {
      jm.createAccordion(mode ? { isSingleMode: function () { return mode.get() === 'single'; } } : {});
    }
    if (mode) mode.restoreOnLoad();
    if (jm.sectionColumns) jm.sectionColumns();
    if (jm.tooltip) jm.tooltip();
    if (jm.Credits) jm.Credits.init();
    if (jm.helpWip) jm.helpWip();
    if (jm.flashServerMessages) jm.flashServerMessages();

    var form = document.getElementById('bd-settings-form');
    if (!form) return;
    // Before the dirty tracker takes its baseline: the derived values are part of it.
    initForm(form);
    // Custom CSS editor (#21). It writes back to the textarea and fires `input`,
    // which the dirty tracker already listens to. No-op without CodeMirror.
    var css = document.getElementById('bd-f-custom_css');
    if (css && jm.createCodeEditor) jm.createCodeEditor(css, { mode: 'text/css', theme: 'bd-jm' });
    var save = initSave(form);
    var expimp = initExportImport();
    if (jm.createDataActionRouter) {
      jm.createDataActionRouter({
        click: {
          'config-save': function () { if (save) save.save(); },
          'config-export': function () { if (expimp) expimp.exportConfig(); }
        },
        change: {
          'config-import': function (el) { if (expimp) expimp.importConfig(el); }
        }
      });
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})(window, document);
