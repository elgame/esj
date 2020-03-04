(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    clickRangoCentrosCosto();
    keyRangoCentrosCosto();
  });

  var clickRangoCentrosCosto = function () {
    $('#btnRangoCentrosCosto').on('click', function(event) {
      if (validaFormatoRango()) {
        $.ajax({
          url: base_url + 'panel/centro_costo/ajax_get_centros_costos/',
          dataType: "json",
          data: {
            centrosCosto: $('#rangoCentrosCosto').val()
          },
          success: function(data) {
            console.log('respuesta', data);
          }
        });
      } else {
        noty({"text":"El Formato del rango no es correcto.", "layout":"topRight", "type":"error"});
      }
    });
  };

  var keyRangoCentrosCosto = function () {
    $('#rangoCentrosCosto').on('keyup', function(event) {
      if (validaFormatoRango()) {
        $(this).css('border', '1px solid #cccccc');
      } else {
        $(this).css('border', '1px solid red');
      }
    });
  };

  var addCCTag = function(item) {
    if ($('#tagsCCIds .centroCostoId[value="'+item.id+'"]').length === 0) {
      $('#tagsCCIds').append('<li><span class="tag">'+item.value+'</span>'+
        '<input type="hidden" name="centroCostoId[]" class="centroCostoId" value="'+item.id+'">'+
        '<input type="hidden" name="centroCostoText[]" class="centroCostoText" value="'+item.value+'">'+
        '<input type="hidden" name="centroCostoHec[]" class="centroCostoHec" value="'+(parseFloat(item.item.hectareas)||0)+'">'+
        '<input type="hidden" name="centroCostoNoplantas[]" class="centroCostoNoplantas" value="'+(parseFloat(item.item.no_plantas)||0)+'">'+
        '</li>');
    } else {
      noty({"text": 'Ya esta agregada el Centro de costo.', "layout":"topRight", "type": 'error'});
    }
  };

  var validaFormatoRango = function () {
    var patter = /^([A-Z,a-z]*[0-9]+){1,}((-([A-Z,a-z]*[0-9]+){1,})|(,([A-Z,a-z]*[0-9]+){1,})?){1,}$/g;
    var result = patter.test($('#rangoCentrosCosto').val());
    console.log('test', result);
    return result;
  };

});
