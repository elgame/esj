/*
 | libreria para simular el funcionamiento de los checkbox y radios usando los
 | buttons de Bootstrap (Single toggle, Checkbox, Radio)
 |
 | http://twitter.github.io/bootstrap/javascript.html#buttons
 |
 |  Ejemplo de uso:

    ###  HTML ###

      <div class="btn-group" data-toggle="buttons-radio" id="groupRadios" data-name="radios[]">
        <button type="button" class="btn btn-primary" id="btnLeft" data-value="left">Left</button>
        <button type="button" class="btn btn-primary" id="btnMiddle" data-value="middle">Middle</button>
        <button type="button" class="btn btn-primary" id="btnRight" data-value="right">Right</button>
      </div>

      <div class="btn-group" data-toggle="buttons-checkbox" id="groupCheckbox" data-name="checkbox[]">
        <button type="button" class="btn btn-primary" id="btnLeft" data-value="left">Left</button>
        <button type="button" class="btn btn-primary" id="btnMiddle" data-value="middle">Middle</button>
        <button type="button" class="btn btn-primary" id="btnRight" data-value="right">Right</button>
      </div>

      <button type="button" class="btn btn-primary" data-toggle="button"
        id="single" data-name="single" data-value="single">Single Toggle</button>

    ### JAVASCRIPT ###

      $('#groupRadios').btnToggle();
      $('#groupCheckbox').btnToggle();
      $('#singleButton').btnToggle();
 |
 |
 */

(function($){

  var settings, // Plugin Settings
      typesAllow = ['button', 'buttons-checkbox', 'buttons-radio'], // Tipos validos.
      type, // Almacena el tipo del atributo data-toggle del elem selector.
      isAllow, // Indica si el valor del atributo data-toggle es valido.
      wrapper; // jQuery Object $(this).

  $.fn.btnToggle = function (options) {

    settings = $.extend({
      fn: '',
      display: 'none'
    }, options);

    isAllow = false;

    wrapper = $(this);

    type = wrapper.attr('data-toggle');

    typesAllow.forEach(function(elem, index) {
      if (elem === type ) {
        isAllow = true;
        return false;
      }
    });

    if ( ! isAllow) {
      console.log("Error: No se detecto ninguno de los tipos permitidos [button, buttons-checkbox, buttons-radio]");
      return false;
    }

    wrapper.parent().append(buildInputs());

    onBtnClick(wrapper, type, settings);

    return this;
  };

  var buildInputs = function () {
    var html      = ['<div id="'+wrapper.attr('id')+'Input">'],
        inputType = (type === 'buttons-radio') ? 'radio' : 'checkbox',
        name      = 'undefined',
        active    = '';

    if (wrapper.attr('data-name') !== undefined) name = wrapper.attr('data-name');

    if (type === typesAllow[0]) {

     if (wrapper.hasClass('active'))
        active = 'checked="checked"';

      html.push('<input type="'+inputType+'"' +
                        'name="'+name+'"' +
                        'value="'+wrapper.attr('data-value')+'"'+
                        'id="'+wrapper.attr('id')+'"'+
                        'style="display: ' + settings.display + ';" ' + active + '>');

    } else {

      wrapper.find('button').each(function(e, i) {

        var btn = $(this),
            active = '';

        if (btn.hasClass('active'))
          active = 'checked="checked"';

        if (btn.attr('data-name') !== undefined) name = btn.attr('data-name');

        html.push('<input type="'+inputType+'"' +
                         'name="'+name+'"' +
                         'value="'+btn.attr('data-value')+'"'+
                         'id="'+btn.attr('id')+'"'+
                         'style="display: ' + settings.display + ';" ' + active + '>');

      });

    }

    html.push('</div>');

    return html.join('');
  };

  var onBtnClick = function (wrapper, type, settings) {

    if (type === typesAllow[0]) obj = wrapper;
    else obj = wrapper.find('button');

    obj.on('click', function(event) {
      var btn    = $(this),
        isActive = btn.hasClass('active'),
        input    = $('#' + wrapper.attr('id') + 'Input').find('#' + btn.attr('id'));

      if (type === typesAllow[2])
        input.prop('checked', 'checked');
      else {
        if( ! isActive) input.prop('checked', 'checked');
        else input.prop('checked', '');
      }

      if ($.isFunction(settings.fn)) settings.fn(btn);
    });
  };

})(jQuery);