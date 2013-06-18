(function($){

  var settings;

  $.fn.keyJump = function (options) {

    settings = $.extend({
      'next': 13
    }, options);

    var $wrapper = $(this),
        hiddens = [],
        fields = $wrapper
                 .find('input, textarea, select')
                 // .filter(':visible')
                 .filter(function (i) {
                    var $this = $(this);
                    if ($this.is(':hidden.sikey')) {
                      hiddens.push($this)
                    }
                    return $this.is(':visible');
                 })
                 .filter(':enabled')
                 .filter(function (i) {
                    return $(this).prop('readonly') !== true;
                 })
                 .not('.nokey')
                 .toArray();

    fields.forEach(function ($e, i) {
      $($e).on('keypress', function(event) {

        if (event.which == settings.next) {
          var $next = $(fields[i+1]);

          if (settings.next == 13) event.preventDefault();

          if ($next.is(':visible')) $next.focus();

          else  $('[data-replace="'+$next.attr('id')+'"]').focus();

        }
      });
    });

    hiddens.forEach(function ($h, i) {
      $h.on('keypress', function(event) {
        if (event.which == settings.next) {

          if (settings.next == 13) event.preventDefault();

          fields.forEach(function ($e, i) {
            if ($h.attr('data-replace') === $($e).attr('id')) {
              $(fields[i+1]).focus();
              return false;
            }
          });
        }
      });
    });

    $(window).keyup(function(e) {
      e.preventDefault();

      if (e.ctrlKey) keyFn('ctrl', e);

      else if (e.altKey) keyFn('alt', e);

      else if ($.isFunction(settings[e.which])) settings[e.which].call(this);
    });

    var keyFn = function (altCtrlkey, e) {
      for (var key in settings) {
          var keymap = key.split('+');
          if (keymap.length == 2) {
            if (keymap[0] === altCtrlkey && keymap[1] == e.which) {
              settings[key].call(this);
              return false;
            }
          }
        }
    };

  };

})(jQuery);