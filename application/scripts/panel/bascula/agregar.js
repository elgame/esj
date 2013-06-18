$(function(){

  $('#form').keyJump({
    'next': 13,
    'alt+66': function () { // alt + b
      $('#btnKilosBruto').click();
    },
    'alt+84': function () { // alt + t
      $('#btnKilosTara').click();
    },
    'alt+67': function () { // alt + c
      $('#icajas').focus();
    },
    'alt+78': function () { // alt + n
      var href = $('#newPesada').attr('href');
      window.location.href = href;
    },
    'alt+71': function () { // alt + g
      $('#btnGuardar').click();
    },
  });

  $('#ptipo').on('change', function(event) {
    var $this = $(this),
        option = $this.find('option:selected').val();

    if (option === 'en') {
      $('#groupProveedor').css({'display': 'block'});
      $('#groupCliente').css({'display': 'none'});
    } else {
      $('#groupProveedor').css({'display': 'none'});
      $('#groupCliente').css({'display': 'block'});
    }
  });

  recargaTipo();

  recargaCalidadesArea();

  $('#parea').on('change', function(event) {
    var $this = $(this),
        option = $this.find('option:selected').val();
    if (option !== '') {
      $.get(base_url + 'panel/bascula/ajax_get_calidades/', {id: option}, function(data) {
        var optionHtml = ['<option value=""></option>'];
        data.calidades.forEach(function(e, i) {
          optionHtml.push('<option value="'+e.id_calidad+'">'+e.nombre+'</option>');
        });
        $('#icalidad').html(optionHtml.join(''));
      }, 'json');
    }
  });


  $('#pstatus').btnToggle();

  // Autocomplete Empresas
  $("#pempresa").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#pid_empresa").val(ui.item.id);
      $("#pempresa").val(ui.item.label);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#pid_empresa').val('');
    }
  });

  // Autocomplete Proveedor
  $("#pproveedor").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_proveedores/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#pid_proveedor").val(ui.item.id);
      $("#pproveedor").val(ui.item.label);
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#pid_proveedor').val('');
    }
  });

  // Autocomplete Cliente
  $("#pcliente").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_clientes/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#pid_cliente").val(ui.item.id);
      $("#pcliente").val(ui.item.label);
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#pid_cliente').val('');
    }
  });

  // Autocomplete Chofer
  $("#pchofer").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_choferes/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#pid_chofer").val(ui.item.id);
      $("#pchofer").val(ui.item.label);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#pid_chofer').val('');
    }
  });

  // Autocomplete Camiones
  $("#pcamion").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_camiones/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#pid_camion").val(ui.item.id);
      $("#pcamion").val(ui.item.label);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#pid_camion').val('');
    }
  });

  $('#box-cajas').on('click', '#icalidad', function(event) {
    var calidad = $(this).find('option:selected').val();

    if (calidad !== '') {
      $.get(base_url + 'panel/bascula/ajax_get_precio_calidad/', {id: calidad}, function(data) {
        $('#iprecio').val(data.info.precio_compra);
      }, 'json');
    }

  });

  // Evento keypress para el input del folio.
  $('#pfolio').on('keypress', function(e) {
    if (e.charCode == '13') {
      e.preventDefault();
      $('#loadFolio').click();
    }
  });

  // Evento keypress para los input de agregar caja.
  // $('#icajas, #iprecio').on('keypress', function(e) {
  //   if (e.charCode == '13') {
  //     e.preventDefault();
  //     $('#addCaja').click();
  //   }
  // });

  // Evento click boton addCaja. Agrega las cajas a la tabla.
  $('#addCaja').on('click', function(event) {

    if (validaAddCaja()) {

      var $caja     = $('#icajas'),
          $calidad  = $('#icalidad'),
          // $kilos    = $('#ikilos'),
          // $promedio = $('#ipromedio'),
          $precio   = $('#iprecio'),
          // $importe  = $('#iimporte'),
          trHtml = '',
          $tabla = $('#tableCajas');

      if ( ! validaCalidad($calidad.find('option:selected').val())) {
        return false;
      }

      // Construye string con el html del tr.
      trHtml = '<tr><td>' + $caja.val() +
                  '<input type="hidden" name="pcajas[]" value="'+$caja.val()+'" id="pcajas">' +
                  '<input type="hidden" name="pcalidad[]" value="'+$calidad.find('option:selected').val()+'" id="pcalidad">' +
                  '<input type="hidden" name="pcalidadtext[]" value="'+$calidad.find('option:selected').text()+'" id="pcalidadtext">' +
                  '<input type="hidden" name="pkilos[]" value="" id="pkilos">' +
                  '<input type="hidden" name="ppromedio[]" value="" id="ppromedio">' +
                  '<input type="hidden" name="pprecio[]" value="'+$precio.val()+'" id="pprecio">' +
                  '<input type="hidden" name="pimporte[]" value="" id="pimporte">' +
               '</td>' +
               '<td>' + $calidad.find('option:selected').text() + '</td>' +
               '<td id="tdkilos"></td>' +
               '<td id="tdpromedio"></td>' +
               '<td>' + $precio.val() + '</td>' +
               '<td id="tdimporte"></td>' +
               '<td><button class="btn btn-info" type="button" title="Eliminar" id="delCaja"><i class="icon-trash"></i></button></td></tr>';

      // Agrega el html al body de la tabla.
      $(trHtml).appendTo($tabla.find('tbody'));

      $caja.val('');
      $calidad.val('');
      // $kilos.val('');
      // $promedio.val('');
      $precio.val('');
      // $importe.val('');

      calculaTotales();
    }
  });

  // Evento click para los botones delCaja. Elimina el tr correspondiente.
  $('#tableCajas').find('tbody').on('click', 'button#delCaja', function(event) {
    $(this).parent().parent().remove();
    calculaTotales();
  });

  // Evento click para el boton cargar folio.
  $('#loadFolio').on('click', function(event) {
    var $form = $('#form'),
        $folio = $('#pfolio'),
        editar = '';


    if ($('#isEditar').length) editar = '&e=t';

    // console.log(base_url + 'panel/bascula/agregar?folio=' + $folio.val() + editar;
    location.href = base_url + 'panel/bascula/agregar?folio=' + $folio.val() + editar;
  });

  // Evento click boton cargar de kilos tara.
  $('#btnKilosBruto').on('click', function(event) {
    $inputBruto = $('#pkilos_brutos');

    // AQUI CAMBIAR LA URL A DONDE HARA LA PETICION DE LA BASCULA
    $.get(base_url_bascula, {}, function(data) {
      $inputBruto.val(data.data.peso);

      calculaKilosNeto();
      calculaTotales();
    }, 'json');
  });

  // Evento click boton cargar de kilos tara.
  $('#btnKilosTara').on('click', function(event) {
    var $inputTara  = $('#pkilos_tara');

    // AQUI CAMBIAR LA URL A DONDE HARA LA PETICION DE LA BASCULA
    $.get(base_url_bascula, {}, function(data) {
      $inputTara.val(data.data.peso);

      calculaKilosNeto();
      calculaTotales();
    }, 'json');
  });

  $('#pkilos_brutos, #pkilos_tara').keyup(function(e) {
    var key = e.which;

    if ((key > 47 && key < 58) || key === 8) {
      calculaKilosNeto();
      calculaTotales();
    }
  });

});

var calculaKilosNeto = function () {
  var $inputBruto = $('#pkilos_brutos'),
      $inputTara  = $('#pkilos_tara'),
      $inputNeto  = $('#pkilos_neto');

  $inputNeto.val(Math.abs(parseFloat($inputBruto.val() || 0) - parseFloat($inputTara.val() || 0)));
};

var recargaTipo = function () {
  var option = $('#ptipo').find('option:selected').val();

  if (option === 'en') {
    $('#groupProveedor').css({'display': 'block'});
    $('#groupCliente').css({'display': 'none'});
  } else {
    $('#groupProveedor').css({'display': 'none'});
    $('#groupCliente').css({'display': 'block'});
  }
};

var recargaCalidadesArea = function () {
  var option = $('#parea').find('option:selected').val();

  if (option !== '') {
    $.get(base_url + 'panel/bascula/ajax_get_calidades/', {id: option}, function(data) {
        var optionHtml = ['<option value=""></option>'];
        data.calidades.forEach(function(e, i) {
          optionHtml.push('<option value="'+e.id_calidad+'">'+e.nombre+'</option>');
        });
        $('#icalidad').html(optionHtml.join(''));
      }, 'json');
  }

};

var validaAddCaja = function () {
  // || $('#ikilos').val() === '' || $('#ipromedio').val() === '' || $('#iimporte').val() === ''

  // var knetos = parseFloat($('#pkilos_neto').val()) || 0;

  // if (knetos == 0) {
  //   noty({"text": "Los Kilos Neto no pueden ser cero.", "layout":"topRight", "type": 'error'});
  //   return false;
  // }
  // console.log($('#icalidad option:selected').val());
  var option = $('#icalidad option:selected').val() || '';
  if ($('#icajas').val() === '' || option === '' || $('#iprecio').val() === '') {
    noty({"text": "Alguno de los campos estan vacios.", "layout":"topRight", "type": 'error'});
    return false;
  }
  return true;
};

var validaCalidad = function (calidad) {
  var aux = true;
  $('input#pcalidad').each(function(e, i) {
    if ($(this).val() == calidad) {
      noty({"text": 'La calidad seleccionada ya fue agregada', "layout":"topRight", "type": 'error'});
      aux = false;
      return aux;
    }
  });

  return aux;
};

var calculaTotales = function () {
  var $ptotal_cajas = $('#ptotal_cajas')
      $tableCajas   = $('#tableCajas'),
      $ptotal       = $('#ptotal'),

      kilosNeto  = parseFloat($('#pkilos_neto').val()) || 0,
      totalCajas = 0,
      total = 0;

  // Recorre todas las cajas/calidades para obtener el total de cajas.
  $('input#pcajas').each(function(e, i) {
    totalCajas += parseFloat($(this).val());
  });

  $tableCajas.find('tbody tr').each(function(e, i) {
    var $tr   = $(this),
        cajas = parseFloat($tr.find('#pcajas').val()),
        kilos = 0,
        promedio = 0,
        importe = 0,
        precio = parseFloat($tr.find('#pprecio').val());

    kilos = ((cajas * kilosNeto) / totalCajas).toFixed(2);
    $tr.find('#pkilos').val(kilos);
    $tr.find('#tdkilos').html(kilos);

    promedio = (kilos / cajas).toFixed(2);
    $tr.find('#ppromedio').val(promedio)
    $tr.find('#tdpromedio').html(promedio)

    importe = (kilos * precio).toFixed(2);
    $tr.find('#pimporte').val(importe)
    $tr.find('#tdimporte').html(importe)

    total +=  parseFloat(importe);
  });

  $ptotal_cajas.val(totalCajas);
  $ptotal.val(total.toFixed(2));
};