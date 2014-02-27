(function (closure) {

  closure(jQuery, window);

})(function ($, window) {

  $(function () {

    autocompleteCalibres();

  });

  var selectFromAutocomplete = false;

  var autocompleteCalibres = function () {

    $("#auto-calibres").autocomplete({
      source: base_url+'panel/areas/ajax_get_calibres/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this);
        $this.css("background-color", "#B0FFB0");

        addCalibre(ui.item.id, ui.item.item.nombre);

        selectFromAutocomplete = true;

      }
    }).keydown(function(event){

      var $this = $(this);

      if(event.which == 8 || event == 46) {

        $(this).val('').css("background-color", "#FFD9B3");

      }

      if (event.which == 13 && selectFromAutocomplete === false) {

        event.preventDefault();

        $.get(base_url + 'panel/areas/ajax_add_new_calibre', {'nombre': $this.val()}, function(data) {

          if ( ! data.existe) {

            addCalibre(data.id, data.nombre);

          } else {

            noty({"text": 'Ya existe un calibre con el nombre especificado.', "layout":"topRight", "type": 'error'});

          }

        }, 'json');

      }

      selectFromAutocomplete = false;
    });

  };

  var addCalibre = function (id, nombre) {

    if ( ! validateIfCalibreIsSelected(id)) {

      var $list = $('#list-calibres'),
          labelTag = '<label><input type="checkbox" name="fcalibres[]" value="'+id+'" class="sel-calibres" checked><input type="hidden" name="fcalibre_nombre[]" value="'+nombre+'">'+nombre+'</label>';

      $(labelTag).appendTo($list);

      $("input:checkbox, input:radio, input:file").not('[data-uniform="false"],#uniform-is-ajax').uniform();

    } else {

      noty({"text": 'El calibre ya se encuentra seleccionado', "layout":"topRight", "type": 'error'});

    }

  };

  var validateIfCalibreIsSelected = function (id) {

    var existe = false;

    $('#list-calibres').find('input.sel-calibres').each(function(index, el) {

      if ($(this).val() == id) {

        existe = true;

        return false;

      }

    });

    return existe;
  }

});