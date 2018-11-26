(function($){

  var settings;

  $.fn.keyJump = function (options) {

    settings = $.extend({}, $.fn.keyJump.settings ,options);

    if (settings.startFocus !== '') {
      $('#' + settings.startFocus).focus();
    }

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
          var $this = $(this),
              $next = $(fields[i+1]);

          if (settings.next == 13) event.preventDefault();

          if ($this.attr('data-next')) {

            var options = $this.attr('data-next').split('|');

            if($this.attr('data-next2')) {
              $('#'+$this.attr('data-next2')).focus();
            }
            else if (options.length > 1) {
              options.every(function (e) {
                var $this = $('#'+e);
                if ( $this.is(':visible') && $this.prop('readonly') !== true) {
                  $this.focus();
                  return false;
                } else {
                  return true;
                }
              });
            }
            else $('#'+$this.attr('data-next')).focus();
          }

          else if ($next.is(':visible')) $next.focus();

          else  $('[data-replace="'+$next.attr('id')+'"]').focus();

        }
      });
    });
    console.log(hiddens);
    hiddens.forEach(function ($h, i) {
      $h.on('keypress', function(event) {
        if (event.which == settings.next) {
          var $next = $(hiddens[i+1]);

          if (settings.next == 13) event.preventDefault();

          if ($h.attr('data-next')) {
            $('#'+$h.attr('data-next')).focus();
          }
          else if ($h.attr('data-replace')) {
            fields.forEach(function ($e, i) {
              if ($h.attr('data-replace') === $($e).attr('id')) {
                $(fields[i+1]).focus();
                return false;
              }
            });
          }
          else if ($next.is(':visible')) $next.focus();
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

  $.fn.keyJump.settings = {
    'next': 13,
    'startFocus': ''
  };

  $.fn.keyJump.setElem  = function ($elem) {
    $($elem).on('keypress', function(event) {
      if (event.which == settings.next) {

        if (settings.next == 13) event.preventDefault();

        if ($elem.attr('data-next')) {

          if ($('#'+$elem.attr('data-next')).length !== 0)
            $('#'+$elem.attr('data-next')).focus();

          else if ($('.'+$elem.attr('data-next')).length !== 0)
            $('.'+$elem.attr('data-next')).focus()

        }

      }

    });
    return $elem;
  };

  $.fn.keyJump.off  = function () {
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
      $($e).off('keypress');
    });

    hiddens.forEach(function ($h, i) {
      $h.off('keypress');
    });
  };


})(jQuery);