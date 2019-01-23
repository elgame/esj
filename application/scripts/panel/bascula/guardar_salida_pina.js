var ppro_cont   = 0,
    actualFolio = 0,
    autoFocus   = '';

$(function(){

  $.ajaxSetup({ cache: false });
  $('#form').keyJump({
    'next': 13,
  });

  eventosEstibas();
  autocompleteRanchos();
  autocompleteCentroCosto();

});

var eventosEstibas = function () {
  // Evento keypress para los input de agregar caja.
  $('#icentroCosto').on('keypress', function(e) {
    if (e.charCode == '13') {
      e.preventDefault();
      $('#addCaja').trigger('click');
      $("#icantidad").focus();
    }
  });

  // Evento click boton addCaja. Agrega las cajas a la tabla.
  $('#addCaja').on('click', function(event) {

    if (validaAddEstibas()) {

      var $cantidad     = $('#icantidad'),
          $calidad       = $('#icalidad'),
          $centroCosto   = $('#icentroCosto'),
          $centroCostoId = $('#icentroCostoId'),
          trHtml         = '',
          $tabla         = $('#tableEstibas'),
          countini       = (parseInt($('#iestibaIni').val())||1),
          countfin       = (parseInt($('#iestibaFin').val())||1);

      while(countini <= countfin) {
        trHtml += '<tr>'+
          '<td><input type="text" name="estiba[]" value="'+countini+'" class="estiba" readonly></td>'+
          '<td><input type="hidden" name="id_centro_costo[]" value="'+$centroCostoId.val()+'">'+$centroCosto.val()+'</td>'+
          '<td><input type="hidden" name="id_calidad[]" value="'+$calidad.val()+'">'+$('#icalidad option:selected').text()+'</td>'+
          '<td><input type="text" name="cantidad[]" value="'+$cantidad.val()+'" class="cantidad" readonly></td>'+
          '<td><button class="btn btn-info" type="button" title="Eliminar" id="delCaja"><i class="icon-trash"></i></button></td>'+
        '</tr>'
        ++countini;
      }

      // Agrega el html al body de la tabla.
      $(trHtml).appendTo($tabla.find('tbody'));

      // $.fn.keyJump.setElem($('.ppro'+(ppro_cont-1)));

      $('#iestibaIni').val('');
      $('#iestibaFin').val('');
      $cantidad.val('');
      $calidad.val('');
      $centroCosto.val('');
      $centroCostoId.val('');

      calculaTotales();
    }
  });

  // Evento click para los botones delCaja. Elimina el tr correspondiente.
  $('#tableEstibas').find('tbody').on('click', 'button#delCaja', function(event) {
    $(this).parent().parent().remove();
    calculaTotales();
  });
};

var calculaTotales = function () {
  var $tabla = $('#tableEstibas'),
  $cantidades = $tabla.find('tr input.cantidad'),
  kilos_neto = (parseFloat($('#kilos_neto').val())||0),
  piezas = 0;

  $cantidades.each(function(index, el) {
    piezas += (parseFloat($(this).val())||0);
  });
  kg_pieza = (kilos_neto/(piezas>0? piezas: 1)).toFixed(2);

  $('#total_piezas').val(piezas);
  $('#kg_pieza').val(kg_pieza);
};

var validaAddEstibas = function () {
  var option = $('#icalidad option:selected').val() || '';
  if ($('#icantidad').val() === '' || option === '' || $('#iestibaIni').val() === '' || $('#iestibaFin').val() === ''
    || $('#icentroCostoId').val() === '') {
    noty({"text": "Alguno de los campos están vacíos.", "layout":"topRight", "type": 'error'});
    return false;
  }
  var $tabla = $('#tableEstibas'), countini = (parseInt($('#iestibaIni').val())||1),
    countfin = (parseInt($('#iestibaFin').val())||1), band = true;
  while(countini <= countfin && band) {
    if ($tabla.find('tr input.estiba[value='+countini+']').length > 0) {
      band = false;
      noty({"text": "La estibas "+countini+" ya esta agregada al listado.", "layout":"topRight", "type": 'error'});
    }
    ++countini;
  }

  return band;
};

var autocompleteRanchos = function () {
  $("#rancho").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
      if(parseInt($("#areaId").val()) > 0)
        params.area = $("#areaId").val();
      $.ajax({
          url: base_url + 'panel/ranchos/ajax_get_ranchos/',
          dataType: "json",
          data: params,
          success: function(data) {
              response(data);
          }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      var $rancho =  $(this);

      $rancho.val(ui.item.id);
      $("#ranchoId").val(ui.item.id);
      $rancho.css("background-color", "#A1F57A");
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#rancho").css("background-color", "#FFD071");
      $("#ranchoId").val('');
    }
  });
};

var autocompleteCentroCosto = function () {
    $("#icentroCosto").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};

        params.tipo = ['melga'];
        if ($('#id_area').val() != '')
          params.id_area = $('#id_area').val();

        $.ajax({
            url: base_url + 'panel/centro_costo/ajax_get_centro_costo/',
            dataType: "json",
            data: params,
            success: function(data) {
              response(data);
            }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $centroCosto =  $(this);

        $centroCosto.val(ui.item.id);
        $("#icentroCostoId").val(ui.item.id);
        $centroCosto.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#icentroCosto").css("background-color", "#FFD071");
        $("#icentroCostoId").val('');
      }
    });
  };