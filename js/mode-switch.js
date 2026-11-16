/**
 * Light / dark switch (#81), template/_mode_switch.tpl.
 *
 * Writes the core 'mode' cookie, the one themes/standard_pages/js/standard_pages.js
 * writes too (path /, 30 days), then reloads: the server picks the skin from it
 * (include/themecontroller.php, resolveColorScheme). No jQuery needed.
 */
(function (document, window) {
  'use strict';

  function bind() {
    var buttons = document.querySelectorAll('.bd-mode-switch__btn');
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].addEventListener('click', function () {
        var mode = this.getAttribute('data-mode') === 'dark' ? 'dark' : 'light';
        document.cookie = 'mode=' + mode + ';max-age=2592000;path=/;SameSite=Lax';
        window.location.reload();
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bind);
  } else {
    bind();
  }
})(document, window);
