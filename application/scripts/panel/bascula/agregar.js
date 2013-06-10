$(function(){

  $('#parea').on('change', function(event) {
    var $this = $(this),
        option = $this.find('option:selected').val();
    if (option !== '') {
      $.get(base_url + 'panel/bascula/ajax_get_calidades/', {id: option}, function(data) {

        console.log(data);

      }, 'json');
    }
  });

  $('#pstatus').btnToggle();

   // Autocomplete Empresas
  $("#parea").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_areas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#pid_area").val(ui.item.id);
      $("#parea").val(ui.item.label);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).val('');
      $('#pid_area').val('');
    }
  });

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
      $(this).val('');
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
      $(this).val('');
      $('#pid_proveedor').val('');
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
      $(this).val('');
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
      $(this).val('');
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

      // Construye string con el html del tr.
      trHtml = '<tr><td>' + $caja.val() +
                  '<input type="text" name="pcajas[]" value="'+$caja.val()+'" id="pcajas">' +
                  '<input type="text" name="pcalidad[]" value="'+$calidad.find('option:selected').val()+'" id="pcalidad">' +
                  '<input type="text" name="pcalidadtext[]" value="'+$calidad.find('option:selected').text()+'" id="pcalidadtext">' +
                  '<input type="text" name="pkilos[]" value="" id="pkilos">' +
                  '<input type="text" name="ppromedio[]" value="" id="ppromedio">' +
                  '<input type="text" name="pprecio[]" value="'+$precio.val()+'" id="pprecio">' +
                  '<input type="text" name="pimporte[]" value="" id="pimporte">' +
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
        $folio = $('#pfolio');

    location.href = base_url + 'panel/bascula/agregar?folio=' + $folio.val();
  });

  // Evento click boton cargar de kilos tara.
  $('#btnKilosTara').on('click', function(event) {
    var $inputBruto = $('#pkilos_brutos'),
        $inputTara  = $('#pkilos_tara'),
        $inputNeto  = $('#pkilos_neto');

    $inputTara.val(100);

    $inputNeto.val(parseFloat($inputBruto.val()) - parseFloat($inputTara.val()));
  });

  // POST para obtener el peso desde el servidor bascula.
  // {"msg":true,"data":{"id":"dRmVAfDOq","fecha":"2013-06-04 15:09:04","peso":"90"}}

});

var validaAddCaja = function () {
  // || $('#ikilos').val() === '' || $('#ipromedio').val() === '' || $('#iprecio').val() === ''

  var knetos = parseFloat($('#pkilos_neto').val()) || 0;

  if (knetos == 0) {
    noty({"text": "Los Kilos Neto no pueden ser cero.", "layout":"topRight", "type": 'error'});
    return false;
  }

  if ($('#icajas').val() === '' || $('#icalidad option:selected').val() === '' || $('#iimporte').val() === '') {
    noty({"text": "Alguno de los campos estan vacios.", "layout":"topRight", "type": 'error'});
    return false;
  }
  return true;
};

var calculaTotales = function () {
  var $ptotal_cajas = $('#ptotal_cajas')
      $tableCajas   = $('#tableCajas'),
      $ptotal       = $('#ptotal'),

      kilosNeto  = parseFloat($('#pkilos_neto').val()),
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
  $ptotal.val(total);
};