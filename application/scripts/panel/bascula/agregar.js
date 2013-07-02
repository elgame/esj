var ppro_cont   = 0,
    actualFolio = 0,
    autoFocus   = '';

$(function(){

  actualFolio = $('#pfolio').val();
  autoFocus = $('#kjfocus').length === 0 ? '' : $('#kjfocus').val();

  // //Fotos
  // $('button#btnCamera').on('click', function(event) {
  //   event.preventDefault();

  //   var $this   = $(this),
  //       $parent = $this.parent().parent(),
  //       $form   = $('#form'),
  //       img = '';

  //   $.get(base_url+'panel/bascula/snapshot/', function(resp){
  //     img = '<img src="'+resp+'" width="320" id="imgSnapshot">';
  //     $parent.find('#snapshot').html(img);

  //     if ($('#'+$this.attr('data-name')).length == 0)
  //       $form.append('<input type="text" value="'+resp+'" name="'+$this.attr('data-name')+'" id="'+$this.attr('data-name')+'">');
  //     else
  //       $('#'+$this.attr('data-name')).val(resp);
  //   });
  // });

  $('#form').keyJump({
    'next': 13,
    'startFocus': autoFocus,
    'alt+66': function () { // alt + b
      $('#btnKilosBruto').trigger('click');
    },
    'alt+84': function () { // alt + t
      $('#btnKilosTara').trigger('click');
    },
    'alt+67': function () { // alt + c
      $('#icajas').focus();
    },
    '27': function () { // alt + n 78
      var href = $('#newPesada').attr('href');
      window.location.href = href;
    },
    'alt+71': function () { // alt + g
      $('#btnGuardar').trigger('click');
    },
    'alt+80': function () { // alt + p
      // var win=window.open($('#btnPrint').attr('href'), '_blank');
      // win.focus();

      var $form = $('#form');

      if (($('#paccion').val() !== 'p' && $('#paccion').val() !== 'b') || $('#isEditar').length === 1) {
        $form.attr('action', $form.attr('action') + '&p=t');
        $form.submit();
      } else {
        var win=window.open($('#btnPrint').attr('href'), '_blank');
        win.focus();
      }
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
      $("#pempresa").val(ui.item.label).css({'background-color': '#99FF99'});
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
      $("#pproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
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
      $("#pcliente").val(ui.item.label).css({'background-color': '#99FF99'});
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
      $("#pchofer").val(ui.item.label).css({'background-color': '#99FF99'});
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
      $("#pcamion").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#pid_camion').val('');
    }
  });

  $('#box-cajas').on('change', '#icalidad', function(event) {
    var calidad = $(this).find('option:selected').val();

    // if (calidad !== '') {
    //   $.get(base_url + 'panel/bascula/ajax_get_precio_calidad/', {id: calidad}, function(data) {
    //     $('#iprecio').val(data.info.precio_compra);
    //   }, 'json');
    // }

  });

  // Evento keypress para el input del folio.
  $('#pfolio').on('keypress', function(e) {
    var $this = $(this);
    if (e.charCode == '13' && (actualFolio != $this.val())) {
      e.preventDefault();
      $('#loadFolio').trigger('click');
    }
  });

  // Evento keypress para los input de agregar caja.
  $('#iprecio').on('keypress', function(e) {
    if (e.charCode == '13') {
      e.preventDefault();
      $('#addCaja').trigger('click');
      $("#icajas").focus();
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

      if ( ! validaCalidad($calidad.find('option:selected').val())) {
        return false;
      }

      // Construye string con el html del tr.
      trHtml = '<tr data-kneto=""><td>' + $caja.val() +
                  '<input type="hidden" name="pcajas[]" value="'+$caja.val()+'" id="pcajas">' +
                  '<input type="hidden" name="pcalidad[]" value="'+$calidad.find('option:selected').val()+'" id="pcalidad">' +
                  '<input type="hidden" name="pcalidadtext[]" value="'+$calidad.find('option:selected').text()+'" id="pcalidadtext">' +
                  '<input type="hidden" name="pkilos[]" value="" id="pkilos">' +
                  // '<input type="hidden" name="ppromedio[]" value="" id="ppromedio">' +
                  '<input type="hidden" name="pprecio[]" value="'+$precio.val()+'" id="pprecio">' +
                  '<input type="hidden" name="pimporte[]" value="" id="pimporte">' +
               '</td>' +
               '<td>' + $calidad.find('option:selected').text() + '</td>' +
               '<td id="tdkilos"></td>' +
               '<td id="tdpromedio"><input type="text" name="ppromedio[]" value="" id="ppromedio" class="ppro'+(ppro_cont)+'" style="width: 80px;" data-next="ppro'+(++ppro_cont)+'"></td>' +
               '<td>' + $precio.val() + '</td>' +
               '<td id="tdimporte"></td>' +
               '<td><button class="btn btn-info" type="button" title="Eliminar" id="delCaja"><i class="icon-trash"></i></button></td></tr>';

      // Agrega el html al body de la tabla.
      $(trHtml).appendTo($tabla.find('tbody'));

      $.fn.keyJump.setElem($('.ppro'+(ppro_cont-1)));

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
        editar = '',
        focus = '';

    if ($('#isEditar').length) editar = '&e=t';

    if (actualFolio != $('#pfolio').val()) focus = '&f=t';

    // console.log(base_url + 'panel/bascula/agregar?folio=' + $folio.val() + editar;
    location.href = base_url + 'panel/bascula/agregar?folio=' + $folio.val() + editar + focus;
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

  // Evento para asignar los keys del 0 al 9.
  $('#pkilos_brutos, #pkilos_tara, #pcajas_prestadas').keyup(function(e) {
    var key = e.which;

    if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
      calculaKilosNeto();
      calculaTotales();
    }
  });

  // Obtiene el pesaje de los brutos al tener el foco el input.
  $('#pkilos_brutos').on('focus', function(event) {
    $('#btnKilosBruto').trigger('click');
  }).on('focusout', function(event) {
    var $this = $(this);

    if ($this.val() !== '' && $this.val() !== 0 ) {
      $('#form').submit();
    }

  });;

  // Obtiene el pesaje de los tara al tener el foco el input.
  $('#pkilos_tara').on('focus', function(event) {
    $('#btnKilosTara').trigger('click');
  });

  // Evento chango para los promedio de la tabla de cajas.
  $('#tableCajas').on('change', 'input#ppromedio', function(event) {
    var $this = $(this),
        $tr = $this.parent().parent(),
        trIndex = $('#tableCajas tr').index($tr), // Obtiene el index q le corresponde de los tr

        promedio = parseFloat($this.val()),
        cajas    = parseFloat($tr.find('#pcajas').val()),
        kilos    = (promedio * cajas).toFixed(2),
        precio   = 0;

        kilosNeto  = parseFloat($tr.attr('data-kneto')),

        $kilos     = $tr.find('#pkilos'),
        $tdkilos   = $tr.find('#tdkilos')
        $precio    = $tr.find('#pprecio'),
        $importe   = $tr.find('#pimporte'),
        $tdimporte = $tr.find('#tdimporte');

    event.preventDefault();

    $kilos.val(kilos);
    $tdkilos.html(kilos);

    precio = (parseFloat(kilos) * parseFloat($precio.val())).toFixed(2);
    $importe.val(precio)
    $tdimporte.html(precio);

    calculaTotales(trIndex, kilosNeto - parseFloat(kilos));
  });

  $('#btnPrint').on('click', function(event) {
    event.preventDefault();

    var $form = $('#form');

    if (($('#paccion').val() !== 'p' && $('#paccion').val() !== 'b') || $('#isEditar').length === 1) {
      $form.attr('action', $form.attr('action') + '&p=t');
      $form.submit();
    } else {
      var win=window.open($('#btnPrint').attr('href'), '_blank');
      win.focus();
    }
  });

  // $('button#btnGuardar').on('click' , function(event) {
  //   if ($('input#pstatus').is(':checked')) {
  //     var res = msb.confirm('Estas seguro de pagar la boleta?', 'Bascula', this, function($this, $obj)
  //     {
  //       $('#form').submit();
  //     });
  //   } else {
  //     $('#form').submit();
  //   }
  // });

  // $('#form').submit(function ($t) {

  //   console.log($t);

  //   return false;

    // if ($('input#pstatus').is(':checked')) {
    //   var res = msb.confirm('Estas seguro de pagar la boleta?', 'Bascula', this, function($this, $obj)
    //   {
    //     $this.submit();
    //   });
    //   return false;
    // } else {
    //   return true;
    // }
  // });

  $('#pstatus').on('click', function(event) {

    var $this = $(this);

    if ($this.hasClass('active') === false) {

      msb.confirm('Estas seguro de pagar la boleta?', 'Bascula', this, function($this, $obj)
      {
        $('#form').submit();
      }, function () {
        $('#pstatus').trigger('click');
      });

    }

  });

});

var getSnapshot = function ($parent) {
  // var c = document.getElementById("myCanvas");
  // var ctx = c.getContext("2d");
  // var img = $parent.find('#imgSnapshot');
  // var img = document.getElementById("imgSnapshot");
  // ctx.drawImage(img[0], 10, 10);
  // alert(c.toDataURL());
  // return c.toDataURL();

  var c = document.getElementById('myCanvas');
  var ctx = c.getContext('2d');
  var img = new Image;
  // img.src = URL.createObjectURL(e.target.files[0]);
  img.src = base_url_cam_salida_snapshot;

  console.log(img);

  // img.onload = function() {
  //     ctx.drawImage(img, 0, 0);

  //     console.log(ctx);

  //     alert(c.toJSON());
  // }
};

var calculaKilosNeto = function () {
  var $inputBruto  = $('#pkilos_brutos'),
      $inputTara   = $('#pkilos_tara'),
      $inputCajasP = $('#pcajas_prestadas'),
      $inputNeto   = $('#pkilos_neto');

  $inputNeto.val( Math.abs(parseFloat($inputBruto.val() || 0) - parseFloat($inputTara.val() || 0)) - (parseFloat($inputCajasP.val() || 0) * 2) );
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

var calculaTotales = function (trIndex, kilosNeto) {
  var $ptotal_cajas = $('#ptotal_cajas')
      $tableCajas   = $('#tableCajas'),
      $ptotal       = $('#ptotal'),

      kilosNeto  = kilosNeto || (parseFloat($('#pkilos_neto').val()) || 0),
      totalCajas = 0,
      totalCajasP = 0,
      total      = 0,

      trIndex = trIndex || 0;

  // Recorre todas las cajas/calidades para obtener el total de cajas.
  $('input#pcajas').each(function(e, i) {
    totalCajas += parseFloat($(this).val());
  });

  // Recorre las cajas/calidades para obtener unicamente la suma de las cajas
  // que se editaran en caso de que el promedio cambie.
  $('input#pcajas').slice(trIndex).each(function(e, i) {
    totalCajasP += parseFloat($(this).val());
  });

  $tableCajas.find('tbody tr').slice(trIndex).each(function(e, i) {
    var $tr      = $(this),
        cajas    = parseFloat($tr.find('#pcajas').val()),
        kilos    = 0,
        promedio = 0,
        importe  = 0,
        precio   = parseFloat($tr.find('#pprecio').val());

    kilos = Math.floor( ((cajas * kilosNeto) / totalCajasP).toFixed(2) );
    $tr.find('#pkilos').val(kilos);
    $tr.find('#tdkilos').html(kilos);

    promedio = (kilos / cajas).toFixed(2);
    $tr.find('#ppromedio').val(promedio)
    // $tr.find('#tdpromedio').html(promedio)

    importe = (kilos * precio).toFixed(2);
    $tr.find('#pimporte').val(importe)
    $tr.find('#tdimporte').html(importe)

    $tr.attr('data-kneto', kilosNeto);

    // total +=  parseFloat(importe);
  });

  $('input#pimporte').each(function () {
      // console.log($(this).val());
      total +=  parseFloat($(this).val());
  });

  $ptotal_cajas.val(totalCajas);
  $ptotal.val(total.toFixed(2));
};