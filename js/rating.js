/**
 * Star rating widget for the picture page.
 *
 * Original implementation for this theme, released under Apache-2.0 like the
 * rest of it. It speaks the protocol the picture templates already use, and
 * nothing else:
 *
 *   var _pwgRatingAutoQueue = _pwgRatingAutoQueue || [];
 *   _pwgRatingAutoQueue.push({rootUrl: '…', image_id: 42, onSuccess: function (result) {…}});
 *
 * It then drives the #rateForm markup (one <span data-value="N"> per mark,
 * class rateButtonStarFull / rateButtonStarEmpty) and posts the mark through
 * the pwg.images.rate web service, using PwgWS from Piwigo's own scripts.js.
 * The file keeps its path because the BC-BG child theme loads it from here.
 */
(function ($) {
  var FULL = 'rateButtonStarFull';
  var EMPTY = 'rateButtonStarEmpty';

  function init(options) {
    var form = $('#rateForm');
    var stars = form.find('span');
    var current = null;
    var busy = false;

    if (!stars.length) {
      return;
    }

    stars.filter('.' + FULL).each(function () {
      current = $(this).data('value');
    });

    // Fill every star up to and including `mark`; an empty mark clears them all.
    function paint(mark) {
      stars.each(function () {
        var on = mark !== null && mark >= $(this).data('value');
        $(this).toggleClass(FULL, on).toggleClass(EMPTY, !on);
      });
    }

    function rate(mark) {
      if (busy || mark === current) {
        return;
      }
      busy = true;

      new PwgWS(options.rootUrl).callService(
        'pwg.images.rate',
        {image_id: options.image_id, rate: mark},
        {
          method: 'POST',
          onFailure: function (code, message) {
            alert(code + ' ' + message);
            // No-JS path: let the server handle the mark through the form.
            document.location = form.attr('action') + '&rate=' + mark;
          },
          onSuccess: function (result) {
            current = mark;
            busy = false;
            if (options.onSuccess) {
              options.onSuccess(result);
            }
          }
        }
      );
    }

    stars.on('click', function () {
      rate($(this).data('value'));
      return false;
    }).on('mouseover', function () {
      paint($(this).data('value'));
    }).on('mouseout', function () {
      paint(current);
    });

    paint(current);
  }

  // Replay whatever the page queued before this file loaded, then take over
  // the queue so later pushes initialise immediately.
  var queued = window._pwgRatingAutoQueue;
  window._pwgRatingAutoQueue = {push: init};
  if (queued && queued.length) {
    $.each(queued, function (i, options) {
      init(options);
    });
  }
})(jQuery);
