/*
 * Generic fallback for plugin buttons in the contextual navbars (issue #45).
 *
 * Plugins write their buttons for the core "default" theme:
 *   <a class="pwg-state-default pwg-button"><span class="pwg-icon pwg-icon-NAME"></span>
 *   <span class="pwg-button-text">Label</span></a>
 * This theme draws no pwg-icon-* and hides .pwg-button-text, so such a button would be
 * invisible. Every button of that shape gets the navbar structure (li.nav-item > a.nav-link),
 * a Font Awesome icon when its icon is a core one or already Font Awesome, and its label as
 * visible text otherwise. No plugin is known by name here: keep it that way.
 */
(function ($) {
  var MARK = 'data-bd-plugin-button';
  var FA_CLASSES = '.fa, .fas, .far, .fab';

  // Core icon names (themes/default/iconset.css) -> Font Awesome 5 Free Solid.
  var CORE_ICONS = {
    'arrow-e': 'fa-arrow-right',
    'arrow-n': 'fa-arrow-up',
    'arrow-s': 'fa-arrow-down',
    'arrow-w': 'fa-arrow-left',
    'arrowstop-e': 'fa-step-forward',
    'arrowstop-n': 'fa-angle-double-up',
    'arrowstop-s': 'fa-angle-double-down',
    'arrowstop-w': 'fa-step-backward',
    'caddie': 'fa-bookmark',
    'caddie-add': 'fa-bookmark',
    'caddie-del': 'fa-times',
    'calendar': 'fa-calendar-alt',
    'camera': 'fa-camera',
    'camera-calendar': 'fa-calendar-alt',
    'camera-info': 'fa-info-circle',
    'category-edit': 'fa-pencil-alt',
    'category-view-flat': 'fa-th',
    'category-view-normal': 'fa-folder',
    'clock-minus': 'fa-minus',
    'clock-plus': 'fa-plus',
    'close': 'fa-times',
    'cloud': 'fa-cloud',
    'edit': 'fa-pencil-alt',
    'favorite': 'fa-heart',
    'favorite-add': 'fa-heart',
    'favorite-del': 'fa-heart-broken',
    'filter': 'fa-filter',
    'filter-del': 'fa-times',
    'globe': 'fa-globe',
    'home': 'fa-home',
    'letters': 'fa-font',
    'lost-password': 'fa-key',
    'map': 'fa-globe',
    'menu': 'fa-bars',
    'pause': 'fa-pause',
    'play': 'fa-play',
    'register': 'fa-user-plus',
    'repeat-play': 'fa-redo',
    'repeat-stop': 'fa-stop',
    'representative': 'fa-image',
    'save': 'fa-save',
    'sizes': 'fa-expand',
    'slideshow': 'fa-play',
    'sort': 'fa-sort',
    'stop': 'fa-stop'
  };

  function coreIconName(el) {
    var match = /(?:^|\s)pwg-icon-([a-z0-9-]+)/.exec(el.className || '');
    return match ? match[1] : null;
  }

  function findLabel($button, $link) {
    var label = $.trim($link.find('.pwg-button-text').first().text());
    if (!label) {
      label = $.trim($link.attr('title') || '');
    }
    if (!label) {
      var $img = $button.is('img') ? $button : $link.find('img').first();
      label = $.trim($img.attr('title') || $img.attr('alt') || '');
    }
    return label;
  }

  // Rewrites the icons of a button; returns true when a visible icon is left.
  function normalizeIcons($link) {
    $link.find('.pwg-icon').each(function () {
      var $icon = $(this);
      var name = coreIconName(this);
      if ($icon.is(FA_CLASSES)) {
        $icon.removeClass('pwg-icon').removeAttr('style');
      } else if (name && CORE_ICONS.hasOwnProperty(name)) {
        $icon.replaceWith('<i class="fas ' + CORE_ICONS[name] + ' fa-fw" aria-hidden="true"></i>');
      } else {
        $icon.remove();
      }
    });
    return $link.is('img') || $link.find('i, svg, img, ' + FA_CLASSES).length > 0;
  }

  // The core switchBox menu of a link: a pair registered in window.SwitchBox while it is still
  // an array (core switchbox.js not run yet), otherwise the .switchBox right after the link.
  // A pair found in the array is taken out of it, so that switchbox.js, loaded async by some
  // plugins, never binds its own click handler on the converted menu.
  function findSwitchBox($link) {
    var registry = window.SwitchBox;
    if ($.isArray(registry)) {
      for (var i = 0; i + 1 < registry.length; i += 2) {
        if ($link.is(registry[i])) {
          var box = registry[i + 1];
          registry.splice(i, 2);
          return $(box).first();
        }
      }
    }
    return $link.next('.switchBox');
  }

  function toDropdown($link, $box) {
    var $li = $link.parent();
    $li.addClass('dropdown');
    $link.addClass('dropdown-toggle').attr('data-toggle', 'dropdown');
    $box.appendTo($li)
      .removeClass('switchBox').removeAttr('style')
      .addClass('dropdown-menu dropdown-menu-right').attr('role', 'menu');
    $box.find('a').addClass('dropdown-item');
    $box.find('.switchBoxTitle').addClass('dropdown-header').removeClass('switchBoxTitle');
    $box.find('br').remove();
  }

  function normalizeButton(button) {
    var $button = $(button);
    if ($button.is('a.nav-link')) {
      return;
    }
    var $link = $button;
    var $box = $button.is('a') ? findSwitchBox($button) : $();
    var label = findLabel($button, $link);
    var hasIcon = normalizeIcons($link);

    if ($button.is('img')) {
      $button.wrap('<span class="nav-link"></span>');
      $link = $button.parent();
    } else {
      $link.find('.pwg-button-text').remove();
      $link.addClass('nav-link').removeClass('pwg-state-default pwg-button');
    }

    if (label && !$.trim($link.text())) {
      var $label = $('<span></span>').text(label);
      if (hasIcon) {
        $label.addClass('d-lg-none ml-2');
      }
      $link.append($label);
    }
    // Neither icon nor label: a neutral icon, so that the button never disappears.
    if (!hasIcon && !$.trim($link.text())) {
      $link.append('<i class="fas fa-puzzle-piece fa-fw" aria-hidden="true"></i>');
    }

    if (!$link.parent().is('li')) {
      $link.wrap('<li></li>');
    }
    $link.parent().addClass('nav-item').attr(MARK, '1');
    if ($box.length) {
      toDropdown($link, $box);
    }
  }

  // Drops the click handlers the core switchbox.js (or the plugin) bound to a converted menu,
  // and the inline display its toggle may have left, so that only Bootstrap's dropdown answers.
  function bindSwitchBoxes(scope) {
    $(scope || document).find('li[' + MARK + '].dropdown').each(function () {
      var $li = $(this);
      $li.children('a.dropdown-toggle').off();
      $li.children('.dropdown-menu').off().removeAttr('style');
    });
  }

  function normalize(scope) {
    var $scope = $(scope || document);
    var $bars = $scope.find('.navbar-contextual ul.navbar-nav').add($scope.filter('.navbar-contextual ul.navbar-nav'));
    $bars.children().each(function () {
      var $child = $(this);
      if ($child.is('li')) {
        if (!$child.attr(MARK)) {
          $child.children('a, img').each(function () {
            normalizeButton(this);
          });
        }
      } else if ($child.is('a, img')) {
        normalizeButton(this);
      }
    });
  }

  window.bdPluginButtons = {
    CORE_ICONS: CORE_ICONS,
    normalize: normalize,
    bindSwitchBoxes: bindSwitchBoxes
  };

  $(document).ready(function () {
    normalize();
    bindSwitchBoxes();
  });
  $(window).on('load', function () {
    normalize();
    bindSwitchBoxes();
  });
})(jQuery);
