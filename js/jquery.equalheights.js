/**
 * $.fn.equalHeights([minHeight], [maxHeight])
 *
 * Sets every matched element to the height of the tallest one, optionally
 * raised to at least minHeight and capped at maxHeight (in pixels).
 *
 * Original implementation for this theme, released under Apache-2.0 like the
 * rest of it. Kept at this path with the same plugin name and signature
 * because the BC-BG child theme loads it from here.
 */
(function ($) {
  $.fn.equalHeights = function (minHeight, maxHeight) {
    var target = minHeight || 0;

    this.each(function () {
      target = Math.max(target, $(this).height());
    });

    if (maxHeight && target > maxHeight) {
      target = maxHeight;
    }

    return this.height(target);
  };
})(jQuery);
