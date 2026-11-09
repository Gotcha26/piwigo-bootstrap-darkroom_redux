/**
 * bd-admin.js: Bootstrap Darkroom admin panel, on the shared jm-* shell.
 * Strings: window.BDAdmin.strings (no interface text hard-coded here).
 *
 * Auto-inclusion mentions (minify.js only scans THIS file, and does not follow
 * dependencies): jm.createTabs, jm.createAccordion, jm.sectionColumns, jm.tooltip,
 * jm.Credits, jm.modal, jm.helpWip, jm.flash, jm.flashServerMessages,
 * jm.createDirtyTracker, jm.createFormSave, jm.formSaveButtonState, jm.ws,
 * jm.createDataActionRouter, jm.createCodeEditor, jm.createAccordionModeControl,
 * jm.createUiPref, jm.createSegmentedControl, jm.exportImport, jm.vendorAlert,
 * jm.confirmReset, jm.registerResetConfirmVariant, jm.freeUpdate (confirmation card of
 * the Free update channel), jm.stubLicenseError
 * (transitive de jm.createFormSave : codes d'erreur des communs, piwigo_local#1446),
 * jm.richEditor (legal notice of the About page, #75; it depends on jm.modal, cited above).
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

  /* Legal notice of the About page (#75), edited in the shared rich editor
     (jm.richEditor: CKEditor 5 from the shared asset store), preset 'prose', its
     richest toolbar. No formatting limit here: the server strips active content
     before storage and again before display.
     The textarea stays the posted field: the validated HTML is written to it and
     `input` fired, which the dirty tracker listens to. Without the editor (no
     build, no asset config), nothing changes: the textarea stays visible. */
  var NOTICE_OVERLAY = '#bdNoticeEditorOverlay';

  /** An editor left "<p>&nbsp;</p>" behind: no text and no image is an empty notice. */
  function noticeBlank(html) {
    if (/<img\b/i.test(html || '')) return false;
    var probe = document.createElement('div');
    probe.innerHTML = html || '';
    return String(probe.textContent || '').replace(/[\s ]+/g, '') === '';
  }

  function initNotice() {
    var field = document.getElementById('bd-f-about_notice');
    var view = document.getElementById('bd-about-notice-view');
    var re = jm.richEditor;
    if (!field || !view || !re || typeof re.openModal !== 'function' || !document.querySelector(NOTICE_OVERLAY)) {
      return null;
    }
    var actions = view.parentNode.querySelector('.bd-notice-actions');
    field.hidden = true;
    view.hidden = false;
    if (actions) actions.hidden = false;

    function render(html) {
      var empty = noticeBlank(html);
      view.classList.toggle('is-empty', empty);
      // The editor's own output, in the webmaster's own session: the server
      // sanitizes it on save, and the page reloads the view from the stored value.
      if (empty) view.textContent = view.getAttribute('data-bd-empty') || '';
      else view.innerHTML = html;
    }

    function open(trigger) {
      re.openModal({
        title: view.getAttribute('aria-label') || '',
        html: field.value,
        preset: 'prose',
        trigger: trigger || view,
        overlay: NOTICE_OVERLAY,
        onValidate: function (html) {
          var value = noticeBlank(html) ? '' : html;
          field.value = value;
          field.dispatchEvent(new Event('input', { bubbles: true }));
          render(value);
        }
      });
    }

    // The view is a role="button": Enter and Space open it, like a real button.
    view.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      e.preventDefault();
      open(view);
    });
    return { open: open };
  }

  function saveButton() {
    return document.querySelector('#bd-admin [data-action="config-save"]');
  }

  function initSave(form) {
    if (!jm.createFormSave) return null;
    // Capture et déclencheurs du traqueur commun (piwigo_local#1564) : jm.formParams du
    // formulaire, la charge exacte que le save ci-dessous envoie.
    var dirty = jm.createDirtyTracker ? jm.createDirtyTracker({
      form: form,
      target: saveButton
    }) : null;
    // jm.formParams : sérialisation par défaut du formulaire dans le commun de save
    // (piwigo_local#1558). Cette mention l'embarque dans ce fichier — l'auto-inclusion
    // n'est pas transitive.
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

  /* "Reset settings" brick of the Maintenance section: 'bd-admin-page' confirmation
     variant. No form POST (the form has no action, and the admin page answers JSON
     methods only): after confirmation, settingsReset is posted to the admin page,
     like the save. The page then reloads, so the form shows the defaults. The
     handler never submits the form: the save engine is not bound to its submit
     event, and no __jmResetPending check is needed. */
  function resetViaAdminPage(e, button) {
    if (e && e.preventDefault) e.preventDefault();
    var s = A.strings || {};
    function run() {
      jm.ws('bootstrap_darkroom.settingsReset', {}, { getToken: function () { return A.pwgToken; }, wsUrl: A.saveUrl })
        .then(function () {
          if (jm.flash) jm.flash(s.resetDone || '', 'success');
          setTimeout(function () { window.location.reload(); }, 1200);
        })
        .catch(function () {
          if (jm.flash) jm.flash(s.resetError || '', 'error');
        });
    }
    var body = s.resetConfirm || (button && button.getAttribute('data-reset-confirm')) || '';
    if (jm.modal && typeof jm.modal.confirm === 'function' && s.resetTitle && s.resetCancel) {
      jm.modal.confirm({
        title: s.resetTitle,
        body: body,
        confirmLabel: (button && button.textContent.replace(/^\s+|\s+$/g, '')) || s.resetTitle,
        cancelLabel: s.resetCancel,
        level: 'danger',
        onConfirm: run
      });
    } else if (window.confirm(body)) {
      run();
    }
  }

  function initReset() {
    var button = document.getElementById('jm-maint-reset-btn');
    if (!button || !jm.confirmReset || !jm.registerResetConfirmVariant || typeof jm.ws !== 'function') return;
    jm.registerResetConfirmVariant('bd-admin-page', resetViaAdminPage);
    jm.confirmReset(button);
  }

  /* "Third-party asset bundle not installed" alert, rendered by the shared shell.
     Its two methods are answered by jm_ws_admin_page_dispatch() on this admin page
     (BDAdmin.saveUrl), never by ws.php. Bound before the form guard: the alert does
     not depend on the form. */
  function initVendorAlert() {
    if (!window.jm || typeof window.jm.vendorAlert !== 'function' || typeof window.jm.ws !== 'function') return;
    window.jm.vendorAlert({
      wsMethod: 'bootstrap_darkroom.vendorRetry',
      wsDismissMethod: 'bootstrap_darkroom.vendorDismiss',
      wsUrl: A.saveUrl,
      getToken: function () { return A.pwgToken; }
    });
  }

  function init() {
    if (!document.getElementById('bd-admin')) return;
    jm = window.jm || {};
    initVendorAlert();
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
    // Server-rendered boxes only (the AJAX saves flash on their own). The notice of a
    // build taken over in place is a full sentence: the common computes its duration from its length.
    if (jm.flashServerMessages) jm.flashServerMessages();

    var form = document.getElementById('bd-settings-form');
    if (!form) return;
    // Before the dirty tracker takes its baseline: the derived values are part of it.
    initForm(form);
    // Custom CSS editor (#21). It writes back to the textarea and fires `input`,
    // which the dirty tracker already listens to. No-op without CodeMirror.
    var css = document.getElementById('bd-f-custom_css');
    if (css && jm.createCodeEditor) jm.createCodeEditor(css, { mode: 'text/css', theme: 'bd-jm' });
    var notice = initNotice();
    var save = initSave(form);
    var expimp = initExportImport();
    initReset();
    if (jm.createDataActionRouter) {
      jm.createDataActionRouter({
        click: {
          'about-notice-edit': function (el) { if (notice) notice.open(el); },
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
