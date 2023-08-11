var ppro_cont   = 0,
    actualFolio = 0,
    autoFocus   = '';

$(function(){

  $.ajaxSetup({ cache: false });

  actualFolio = $('#pfolio').focusin(function(){
    if (this.setSelectionRange)
    {
      var len = $(this).val().length;
      this.setSelectionRange(len, len);
    }else
      $(this).val($(this).val());
  }).val();
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

  $('#ptipo, #parea').on('change', function(event) {
    var $tipo = $('#ptipo'),
        $area = $('#parea'),
        getData = {
          'tipo': $tipo.find('option:selected').val(),
          'area': $area.find('option:selected').val(),
        };

    // var param_empresa = '&did_empresa=' + $('#pid_empresa').val();
    // $("#pproveedor").autocomplete( "option", "source", base_url + 'panel/bascula/ajax_get_proveedores/?type='+$("#parea option:selected").attr('data-tipo')+param_empresa );

    if (getData.area !== '') {
      $.get(base_url + 'panel/bascula/ajax_get_next_folio/', getData, function(data) {
        $('#pfolio').val(data);

        actualFolio = data;
      });
    }

    // cambia el tipo de cajas
    if (getData.tipo == 'sa') {
      $("#box-cajas").hide();
      $("#box-cajas-salidas").show();
    }else {
      $("#box-cajas").show();
      $("#box-cajas-salidas").hide();
    }
  });

  $('#newPesada').on('click', function(event) {
    event.preventDefault();
    var band = false;
    if($("#pno_lote").length > 0) {
      if ($("#pno_lote").val() != '')
        band = true;
    }else
      band = true;

    if(band) {
      var href = $(this).attr('href');
      window.location.href = href;
    }else
      noty({"text": 'Agrega el numero de lote a la boleta', "layout":"topRight", "type": 'error'});
  });

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
      var band = false;
      if($("#pno_lote").length > 0) {
        if ($("#pno_lote").val() != '')
          band = true;
      }else
        band = true;

      if(band) {
        var href = $('#newPesada').attr('href');
        window.location.href = href;
      }else
        noty({"text": 'Agrega el numero de lote a la boleta', "layout":"topRight", "type": 'error'});
    },
    'alt+71': function () { // alt + g
      $('#btnGuardar').trigger('click');
    },
    'alt+80': function () { // alt + p
      imprimirBoletaa();

      // var win=window.open($('#btnPrint').attr('href'), '_blank');
      // win.focus();

      // var $form = $('#form');

      // // if (($('#paccion').val() !== 'p' && $('#paccion').val() !== 'b') || $('#isEditar').length === 1) {
      // if ($('#autorizar').length === 0) {
      //   $form.attr('action', $form.attr('action') + '&p=t');
      //   $form.submit();
      // } else {
      //   var win=window.open($('#btnPrint').attr('href'), '_blank');
      //   win.focus();
      // }
    },
  });

  $('#ptipo').on('change', function(event) {
    var $this = $(this),
        option = $this.find('option:selected').val(),
        priv_modif_kilosbt = $("#modif_kilosbt").val(),
        paccion = $('#paccion').val();
    if (option === 'en') {
      $('#groupProveedor, #groupProveedorRancho').css({'display': 'block'});
      $('#groupCliente').css({'display': 'none'});
      $('#groupTrazabilidad').css({'display': 'none'});

      // cargar kilos
      if (paccion == 'n')
        $("#pproductor").attr('data-next2', 'pkilos_brutos');
      else
        $("#pproductor").attr('data-next2', 'pkilos_tara');

      if (priv_modif_kilosbt == 'true') {
        $('#pkilos_brutos').prop("readonly", '');
        $('#pkilos_tara').prop("readonly", '');
      } else {
        $('#pkilos_brutos').prop("readonly", '');
        $('#pkilos_tara').prop("readonly", 'readonly');
      }
    } else {
      $('#groupProveedor, #groupProveedorRancho').css({'display': 'none'});
      $('#groupCliente').css({'display': 'block'});
      $('#groupTrazabilidad').css({'display': 'block'});

      // cargar kilos
      if (paccion == 'n')
        $("#pproductor").attr('data-next2', 'pkilos_tara');
      else
        $("#pproductor").attr('data-next2', 'pkilos_brutos');

      if (priv_modif_kilosbt == 'true') {
        $('#pkilos_brutos').prop("readonly", '');
        $('#pkilos_tara').prop("readonly", '');
      } else {
        $('#pkilos_brutos').prop("readonly", 'readonly');
        $('#pkilos_tara').prop("readonly", '');
      }
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

      $("#pid_proveedor").val('').css({'background-color': '#FFF'});
      $("#pproveedor").val('').css({'background-color': '#FFF'});
      $("#pid_productor").val('').css({'background-color': '#FFF'});
      $("#pproductor").val('').css({'background-color': '#FFF'});
      $("#pid_cliente").val('').css({'background-color': '#FFF'});
      $("#pcliente").val('').css({'background-color': '#FFF'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#pid_empresa').val('');
    }
  });

  // Autocomplete Proveedor
  $("#pproveedor").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#pid_empresa").val()) > 0)
        params.did_empresa = $("#pid_empresa").val();

      params.type = $("#parea option:selected").attr('data-tipo');

      $.ajax({
          url: base_url + 'panel/bascula/ajax_get_proveedores/',
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
      $("#pid_proveedor").val(ui.item.id);
      $("#pproveedor").val(ui.item.label).css({'background-color': '#99FF99'});


      if ($('#ptipo').find('option:selected').val() === 'en')
      {
        $.getJSON(base_url + 'panel/bascula/ajax_check_limite_proveedor/', {'idp': ui.item.id}, function(data) {
          if (data.status) {
            var msgg = '';
            if(data.total >= 900000){
              msgg = 'El limite ('+data.limite+') de facturación del proveedor seleccionado ya esta superado.';
            } else if(data.total >= 850000){
              msgg = 'El limite de facturación del proveedor esta por vencer, restan '+(data.limite - data.total);
            }
            noty({"text": msgg, "layout":"topRight", "type": 'error'});
          }

        });

        if(ui.item.item.ret_isr == 't'){
          $('#pisrPorcent').val(1.25); // Asigna el % de retención
          calculaTotales();
        }
      }
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#pid_proveedor').val('');
    }
  });

  // Autocomplete Proveedor
  $("#pproductor").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#pid_empresa").val()) > 0)
        params.did_empresa = $("#pid_empresa").val();
      $.ajax({
          url: base_url + 'panel/productores/ajax_get_productores/',
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
      $("#pid_productor").val(ui.item.id);
      $("#pproductor").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#pid_productor').val('');
    }
  });

  // Autocomplete RANCHOS
  $("#prancho").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_ranchos/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#prancho").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
    }
  });

  // Autocomplete tablas
  $("#ptabla").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_tablas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#ptabla").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
    }
  });

  // Autocomplete Cliente
  $("#pcliente").autocomplete({
    source: function(request, response) {
      $.ajax({
          url: base_url + 'panel/bascula/ajax_get_clientes/',
          dataType: "json",
          data: {
              term : request.term,
              did_empresa : $("#pid_empresa").val()
          },
          success: function(data) {
              response(data);
          }
      });
    },
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
    // source: base_url + 'panel/bascula/ajax_get_choferes/',
    source: function(request, response) {
      params = {term : request.term};

      if ($('#ptipo').val() === 'sa') {
        params['alldata'] = 'true';
      }

      $.ajax({
          url: base_url + 'panel/bascula/ajax_get_choferes/',
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
    // source: base_url + 'panel/bascula/ajax_get_camiones/',
    source: function(request, response) {
      params = {term : request.term};

      if ($('#ptipo').val() === 'sa') {
        params['alldata'] = 'true';
      }

      $.ajax({
          url: base_url + 'panel/bascula/ajax_get_camiones/',
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
  $('#certificado').on('keypress', function(e) {
    var $this = $(this);
    if (e.charCode == '32') {
      e.preventDefault();
      if ($this.is(':checked'))
        $this.removeAttr("checked");
      else
        $this.attr("checked", "checked");
    }
  });

  $('#intangible').on('keypress', function(e) {
    var $this = $(this);
    if (e.charCode == '32') {
      e.preventDefault();
      if ($this.is(':checked'))
        $this.removeAttr("checked");
      else
        $this.attr("checked", "checked");
    }
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

      // if ( ! validaCalidad($calidad.find('option:selected').val())) {
      //   return false;
      // }
      // Construye string con el html del tr.
      trHtml = '<tr data-kneto=""><td>' + $caja.val() +
                  '<input type="hidden" name="pnum_registro[]" value="" id="pnum_registro">' +
                  '<input type="hidden" name="pcajas[]" value="'+$caja.val()+'" id="pcajas">' +
                  '<input type="hidden" name="pcalidad[]" value="'+$calidad.find('option:selected').val()+'" id="pcalidad">' +
                  '<input type="hidden" name="pcalidadtext[]" value="'+$calidad.find('option:selected').text()+'" id="pcalidadtext">' +
                  // '<input type="hidden" name="pkilos[]" value="" id="pkilos">' +
                  // '<input type="hidden" name="ppromedio[]" value="" id="ppromedio">' +
                  // '<input type="hidden" name="pprecio[]" value="'+$precio.val()+'" id="pprecio">' +
                  '<input type="hidden" name="pimporte[]" value="" id="pimporte">' +
               '</td>' +
               '<td>' + $calidad.find('option:selected').text() + '</td>' +
               '<td id="tdkilos">' +
                  '<span></span>' +
                  '<input type="'+((parseFloat($('#pkilos_neto').val()) <= 300) ? 'text': 'hidden')+'" name="pkilos[]" value="" id="pkilos" style="width: 100px;">' +
               '</td>' +
               '<td id="tdpromedio"><input type="text" name="ppromedio[]" value="" id="ppromedio" class="ppro'+(ppro_cont)+'" style="width: 80px;" data-next="ppro'+(++ppro_cont)+'"></td>' +
               '<td><input type="text" name="pprecio[]" value="'+$precio.val()+'" class="vpositive" id="pprecio" style="width: 80px;"></td>' +
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

  var winFotos;
  // Evento click para el boton cargar folio.
  $('#loadFolio').on('click', function(event) {
    var $form = $('#form'),
        $folio = $('#pfolio'),
        editar = '',
        focus = '',

        $tipo = $('#ptipo'),
        $area = $('#parea'),
        getData = {
          'folio' : $folio.val(),
          'tipo': $tipo.find('option:selected').val(),
          'area': $area.find('option:selected').val(),
        };

    if (getData.area !== '') {
      $.get(base_url + 'panel/bascula/ajax_load_folio/', getData, function(data) {

        // console.log(data);

        if (data != 0) {

        if ($('#isEditar').length) editar = '&e=t';

        if (actualFolio != $('#pfolio').val()) focus = '&f=t';

        // // console.log(base_url + 'panel/bascula/agregar?folio=' + $folio.val() + editar;
        // location.href = base_url + 'panel/bascula/agregar?folio=' + $folio.val() + editar + focus;

        location.href = base_url + 'panel/bascula/agregar?idb=' + data + editar + focus;

        // winFotos = window.open(base_url + 'panel/bascula/fotos?idb=' + data, "Fotos");
        // winFotos.location.reload();

        } else {
          noty({"text": 'El folio no existe para el tipo y area especificado!', "layout":"topRight", "type": 'error'});

          $('#pfolio').focus();
        }

      });
    }


  });

  // Evento click boton cargar de kilos tara.
  $('#btnKilosBruto').on('click', function(event) {
    $inputBruto = $('#pkilos_brutos');

    // AQUI CAMBIAR LA URL A DONDE HARA LA PETICION DE LA BASCULA
    // base_url + 'panel/bascula/ajax_get_kilos/'
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
    // base_url + 'panel/bascula/ajax_get_kilos/'
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
  }).change(function(e) {
    calculaKilosNeto();
    calculaTotales();
  }).focusin(function(){
    if (this.setSelectionRange)
    {
      var len = $(this).val().length;
      this.setSelectionRange(len, len);
    }else
      $(this).val($(this).val());
  });
  $("#pkilos_tara").keydown(function(event) {
    var key = event.which;
    if (key == 13 && $('#ptipo option:selected').val() == 'sa') {
      event.preventDefault();
      $("#pcajas_prestadas").focus();
    }
  });

  // Evento click para seleccionar cual kilos neto usar (kilos neto , kilos neto 2).
  $('#info_kilos_netos').on('click', function(event) {
    var pkilos_neto  = parseFloat($('#pkilos_neto').val() || 0);
    $('#pkilos_neto').val( parseFloat($('#info_kilos_netos').text() || 0) );
    $('#info_kilos_netos').text(pkilos_neto);
    $("#ppesada").val( $('#pkilos_neto').val() );
    calculaTotales();
  });

  $('#pisr').click(function(e) {
    if((parseFloat($('#pisrPorcent').val())||0) > 0) {
      // $('#pisr').val(0);
      $('#pisrPorcent').val(0);
    } else {
      $('#pisrPorcent').val(1.25);
    }
    calculaKilosNeto();
    calculaTotales();
  });

  // Obtiene el pesaje de los brutos al tener el foco el input.

  if ($('#isEditar').length !== 1) {
    $('#pkilos_brutos').on('focus', function(event) {
      $('#btnKilosBruto').trigger('click');
    }).on('focusout', function(event) {
      var $this = $(this);

      if ($this.val() !== '' && $this.val() !== 0 && $('#ptipo option:selected').val() !== 'sa'
         && $('#paccion').val() === 'n') {
        $('#form').submit();
      }

    });
  }

  // Obtiene el pesaje de los tara al tener el foco el input.
  if ($('#isEditar').length !== 1) {

    if (($('#paccion').val() === 'n' && $('#ptipo option:selected').val() === 'sa') ||
        ($('#ptipo option:selected').val() === 'en'))

        $('#pkilos_tara').on('focus', function(event) {
          $('#btnKilosTara').trigger('click');
        }).on('focusout', function(event) {
          var $this = $(this);

          if ($this.val() !== '' && $this.val() !== 0 && $('#ptipo option:selected').val() == 'sa') {
            $('#form').submit();
          }
        });
  }

  // Evento chango para los promedio de la tabla de cajas.
  $('#tableCajas, #table_prod').on('change', 'input#ppromedio', function(event) {
    var $this = $(this),
        $tr = $this.parent().parent(),
        $ptipo     = $('#ptipo'),
        tableCajas = ($ptipo.val() == 'en'? 'tableCajas': 'table_prod'),
        pcajas     = ($ptipo.val() == 'en'? 'pcajas': 'prod_dcantidad'),
        trIndex = $('#'+tableCajas+' tr').index($tr), // Obtiene el index q le corresponde de los tr

        promedio = parseFloat($this.val()),
        cajas    = parseFloat($tr.find('#'+pcajas).val()),
        kilos    = (promedio * cajas).toFixed(2),
        precio   = 0;

        kilosNeto  = parseFloat($tr.attr('data-kneto')),

        $kilos     = $tr.find('#pkilos'),
        $tdkilos   = $tr.find('#tdkilos'),
        $precio    = $tr.find('#pprecio'),
        $importe   = $tr.find('#pimporte'),
        $tdimporte = $tr.find('#tdimporte');

    event.preventDefault();

    $kilos.val(kilos);
    if (parseFloat($('#pkilos_neto').val()) > 300) {
      $tr.find('#tdkilos').find('span').html(kilos);
      $tr.find('#pkilos').get(0).type = 'hidden';
    } else {
      $tr.find('#tdkilos').find('span').html('');
      $tr.find('#pkilos').get(0).type = 'text';
    }

    precio = (parseFloat(kilos) * parseFloat($precio.val())).toFixed(2);
    $importe.val(precio);
    $tdimporte.html(precio);

    console.log('ppromedio', promedio, cajas, kilos, precio, kilosNeto, parseFloat(kilos));
    calculaTotales(trIndex, kilosNeto - parseFloat(kilos));
  });
  $('#table_prod').on('change', 'input#prod_dcantidad', function(event) {
    calculaKilosNeto();
    calculaTotales();
    // var $this = $(this),
    //     $tr = $this.parent().parent(),
    //     $ptipo     = $('#ptipo'),
    //     tableCajas = ($ptipo.val() == 'en'? 'tableCajas': 'table_prod'),
    //     pcajas     = ($ptipo.val() == 'en'? 'pcajas': 'prod_dcantidad'),
    //     trIndex = $('#'+tableCajas+' tr').index($tr), // Obtiene el index q le corresponde de los tr

    //     promedio = parseFloat($this.val()),
    //     cajas    = parseFloat($tr.find('#'+pcajas).val()),
    //     kilos    = (promedio * cajas).toFixed(2),
    //     precio   = 0;

    //     kilosNeto  = parseFloat($tr.attr('data-kneto')),

    //     $kilos     = $tr.find('#pkilos'),
    //     $tdkilos   = $tr.find('#tdkilos'),
    //     $precio    = $tr.find('#pprecio'),
    //     $importe   = $tr.find('#pimporte'),
    //     $tdimporte = $tr.find('#tdimporte');

    // event.preventDefault();

    // $kilos.val(kilos);
    // if (parseFloat($('#pkilos_neto').val()) > 300) {
    //   $tr.find('#tdkilos').find('span').html(kilos);
    //   $tr.find('#pkilos').get(0).type = 'hidden';
    // } else {
    //   $tr.find('#tdkilos').find('span').html('');
    //   $tr.find('#pkilos').get(0).type = 'text';
    // }

    // precio = (parseFloat(kilos) * parseFloat($precio.val())).toFixed(2);
    // $importe.val(precio);
    // $tdimporte.html(precio);

    // console.log('ppromedio', promedio, cajas, kilos, precio, kilosNeto, parseFloat(kilos));
    // calculaTotales(trIndex, kilosNeto - parseFloat(kilos));
  });

  $('a#btnPrint').on('click', function(event) {
    event.preventDefault();

    imprimirBoletaa();
  });
  var imprimirBoletaa = function () {
    var $form = $('#form');

    // if (($('#paccion').val() !== 'p' && $('#paccion').val() !== 'b') || $('#isEditar').length === 1) {
    if ($('#autorizar').length === 0) {
      let printt = '';
      if ($('#ptipo').val() == 'en') {
        if ($('#pid_empresa').val() != '' && $('#pid_proveedor').val() != '' && $('#prancho').val() != '' &&
          $('#tableCajas tbody #pcajas').length > 0) {
          printt = '&p=t';
        }
      } else {
        printt = '&p=t';
      }

      $form.attr('action', $form.attr('action') + printt);

      let totalKg = parseFloat($('#pkilos_neto').val())||0;
      let totalKgVal = 0;
      if($('#ptipo').val() == 'en' && totalKg > 0) {
        $('#tableCajas #pkilos').each(function(index, el) {
          totalKgVal += parseFloat($(el).val())||0;
        });

        let userId = parseInt($('#userId').val())||0;
        if((totalKg+10) < totalKgVal && userId != 1 && userId != 1908 && userId != 4 && userId != 3700 && userId != 5442) {
          noty({"text": 'Los Kilos de las Cajas no pueden ser mayor a los Kilos Netos ' + totalKg + '. (' + (totalKg+10) + ' >= ' + totalKgVal + ')', "layout":"topRight", "type": 'error'});
        } else {
          $form.submit();
        }
      } else {
        $form.submit();
      }
      console.log(totalKg, totalKgVal);
    } else {
      var win=window.open($('#btnPrint').attr('href'), '_blank');
      win.focus();
    }
  };

  $('button#btnGuardar:not(.bonificar)').on('click' , function(event) {
    event.preventDefault();
    $.ajax({
      url: base_url + 'panel/bascula/puede_modificar/',
      type: 'get',
      dataType: 'json',
      data: {pidb: $('#pidb').val()},
    })
    .done(function(response) {
      if (response.puede_modificar == false)
        location.reload();
      else {
        let totalKg = parseFloat($('#pkilos_neto').val())||0;
        let totalKgVal = 0;
        if($('#ptipo').val() == 'en' && totalKg > 0) {
          $('#tableCajas #pkilos').each(function(index, el) {
            totalKgVal += parseFloat($(el).val())||0;
          });

          let userId = parseInt($('#userId').val())||0;
          if((totalKg+10) < totalKgVal && userId != 1 && userId != 1908 && userId != 4 && userId != 3700 && userId != 5442) {
            noty({"text": 'Los Kilos de las Cajas no pueden ser mayor a los Kilos Netos ' + totalKg + '. (' + (totalKg+10) + ' >= ' + totalKgVal + ')', "layout":"topRight", "type": 'error'});
          } else {
            $('#form').submit();
          }
        } else {
          $('#form').submit();
        }
        console.log(totalKg, totalKgVal);
      }
    });
  });

  // $('#form').submit(function ($t) {
  //   return false;

  //   if ($('input#pstatus').is(':checked')) {
  //     var res = msb.confirm('Estas seguro de pagar la boleta?', 'Bascula', this, function($this, $obj)
  //     {
  //       $this.submit();
  //     });
  //     return false;
  //   } else {
  //     return true;
  //   }
  // });

  $('#pstatus').on('click', function(event) {

    var $this = $(this),
    $pagada = $this.hasClass('active');

    // if ($pagada === false) {

      msb.confirm('Estas seguro de ' + ($pagada? 'quitar el pago a': 'pagar') + ' la boleta?', 'Bascula', this, function($this, $obj)
      {
        console.log('test', parseInt($('#pidb').val()));
        if ((parseInt($('#pidb').val())||0) > 0) {
          // $('#form').submit();
          $.ajax({
            url: base_url + 'panel/bascula/ajax_pagar_boleta/',
            type: 'get',
            dataType: 'json',
            data: { idb: $('#pidb').val(), pagada: $pagada },
          })
          .done(function() {
            // location.reload();
          });
        } else {
          $('#pstatus').trigger('click');
        }

      }, function () {
        $('#pstatus').trigger('click');
      });
    // }

  });

  $('#btnSetFocoKilosTara').on('click', function(event) {

    $('#pkilos_tara').focus();

  });

  $('#btn-auth').on('click', function(event) {
    $.ajax({
      url: base_url + 'panel/bascula/auth_modify/',
      type: 'POST',
      dataType: 'json',
      data: {
        usuario: $('#usuario').val(),
        pass: $('#pass').val()
      },
    })
    .done(function(resp) {
      console.log(resp);
      if (resp.passes) {
        let totalKg = parseFloat($('#pkilos_neto').val())||0;
        let totalKgVal = 0;
        if($('#ptipo').val() == 'en' && totalKg > 0) {
          $('#tableCajas #pkilos').each(function(index, el) {
            totalKgVal += parseFloat($(el).val())||0;
          });

          let userId = parseInt($('#userId').val())||0;
          if((totalKg+10) < totalKgVal && userId != 1 && userId != 1908 && userId != 4 && userId != 3700 && userId != 5442) {
            noty({"text": 'Los Kilos de las Cajas no pueden ser mayor a los Kilos Netos ' + totalKg + '. (' + (totalKg+10) + ' >= ' + totalKgVal + ')', "layout":"topRight", "type": 'error'});
          } else {
            $('#autorizar').val(resp.user_id);
            $('#form').submit();
          }
        } else {
          $('#autorizar').val(resp.user_id);
          $('#form').submit();
        }
        console.log(totalKg, totalKgVal);
      } else {
        noty({"text": resp.msg, "layout":"topRight", "type": 'error'});
      }
    });
  });

  $('#tableCajas').on('change', 'input#pkilos', function(event) {
    var $parent = $(this).parents('tr');

    $parent.find('#pprecio').trigger('change');
  });

  var fechaDeCambio = '';
  $("#cambiarFecha").click(function() {
    $("#myModalFechaCh").modal('show');
  });
  $('#myModalFechaCh').on('show', function () {
    $.ajax({
      url: base_url + 'panel/bascula/auth_modify/',
      type: 'POST',
      dataType: 'json',
      data: {
      },
    })
    .done(function(resp) {
      fechaDeCambio = resp.fecha;
      $('#fechaCh').val(fechaDeCambio);
    });
  });
  $('#btn-auth2').on('click', function(event) {
    $.ajax({
      url: base_url + 'panel/bascula/auth_modify/',
      type: 'POST',
      dataType: 'json',
      data: {
        usuario: $('#usuarioCh').val(),
        pass: $('#passCh').val(),
        tipo: 'fecha'
      },
    })
    .done(function(resp) {
      if (resp.passes) {
        $('#pfecha').val($('#fechaCh').val());
        $('#usuarioCh').val('');
        $('#passCh').val('');
        $("#myModalFechaCh").modal('hide');
      } else {
        noty({"text": resp.msg, "layout":"topRight", "type": 'error'});
      }
    });
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

  // console.log(img);

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
      $inputNeto   = $('#pkilos_neto'),
      $inputNeto2  = $('#pkilos_neto2'),

      kilos_netos = Math.abs(parseFloat($inputBruto.val() || 0) - parseFloat($inputTara.val() || 0)) - (parseFloat($inputCajasP.val() || 0) * 2);

  if ( parseFloat($inputNeto2.val() || 0) > 0) {
    $inputNeto.val( parseFloat($inputNeto2.val() || 0) ); // - (parseFloat($inputCajasP.val() || 0) * 2)
    $("#info_kilos_netos").text(kilos_netos);
  } else {
    $inputNeto.val( kilos_netos );
  }
};

var recargaTipo = function () {
  var option = $('#ptipo').find('option:selected').val(),
  priv_modif_kilosbt = $("#modif_kilosbt").val(),
  paccion = $('#paccion').val();
  if (option === 'en') {
    $('#groupProveedor, #groupProveedorRancho').css({'display': 'block'});
    $('#groupCliente').css({'display': 'none'});
    $('#groupTrazabilidad').css({'display': 'none'});

    // cargar kilos
    if (paccion == 'n')
      $("#pproductor").attr('data-next2', 'pkilos_brutos');
    else
      $("#pproductor").attr('data-next2', 'pkilos_tara');

    if (paccion === 'n' && priv_modif_kilosbt == 'true') {
      $('#pkilos_brutos').prop("readonly", '');
      $('#pkilos_tara').prop("readonly", 'readonly');
    } else if (paccion === 'sa') {
      $("#pproductor").attr('data-next2', 'pkilos_tara');
    }
  } else {
    $('#groupProveedor, #groupProveedorRancho').css({'display': 'none'});
    $('#groupCliente').css({'display': 'block'});
    $('#groupTrazabilidad').css({'display': 'block'});

    // cargar kilos
    if (paccion == 'n')
      $("#pproductor").attr('data-next2', 'pkilos_tara');
    else
      $("#pproductor").attr('data-next2', 'pkilos_brutos');

    if (paccion === 'n' && priv_modif_kilosbt == 'true') {
      $('#pkilos_brutos').prop("readonly", 'readonly');
      $('#pkilos_tara').prop("readonly", '');
    }
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
  console.log('calculaTotales', kilosNeto);
  var $ptipo        = $('#ptipo'),
      $ptotal_cajas = $('#ptotal_cajas'),
      $tableCajas   = ($ptipo.val() == 'en'? $('#tableCajas'): $('#table_prod')),
      $ptotal       = $('#ptotal'),
      $pisr         = $('#pisr'),
      $pisrPorcent  = $('#pisrPorcent'),
      $area         = $('#parea'),

      kilosNeto   = kilosNeto || (parseFloat($('#pkilos_neto').val()) || 0),
      totalCajas  = 0,
      totalCajasP = 0,
      isrTotal    = 0,
      total       = 0,
      pcajas      = ($ptipo.val() == 'en'? 'pcajas': 'prod_dcantidad');

      trIndex = trIndex || 0;

  console.log('calculaTotales 2', kilosNeto);
  // Recorre todas las cajas/calidades para obtener el total de cajas.
  $('input#'+pcajas).each(function(e, i) {
    totalCajas += parseFloat($(this).val());
  });

  // Recorre las cajas/calidades para obtener unicamente la suma de las cajas
  // que se editaran en caso de que el promedio cambie.
  $('input#'+pcajas).slice(trIndex).each(function(e, i) {
    totalCajasP += parseFloat($(this).val());
  });

  $tableCajas.find('tbody tr').slice(trIndex).each(function(e, i) {
    var $tr      = $(this),
        cajas    = parseFloat($tr.find('#'+pcajas).val()),
        kilos    = 0,
        promedio = 0,
        importe  = 0,
        precio   = parseFloat($tr.find('#pprecio').val());

    kilos = Math.round( ((cajas * kilosNeto) / totalCajasP).toFixed(2) );

    $tr.find('#pkilos').val(kilos);
    if (parseFloat($('#pkilos_neto').val()) > 300) {
      $tr.find('#tdkilos').find('span').html(kilos);
      $tr.find('#pkilos').get(0).type = 'hidden';
    } else {
      $tr.find('#tdkilos').find('span').html('');
      $tr.find('#pkilos').get(0).type = 'text';
    }

    promedio = parseFloat((kilos / cajas)||0).toFixed(2);
    $tr.find('#ppromedio').val(promedio);
    // $tr.find('#tdpromedio').html(promedio)

    // Si el area es coco entonces calcula diferente el importe
    if ($area.find('option:selected').attr('data-coco') === 't') {
      importe = (cajas * precio).toFixed(2);
    } else { // Calcula con los kilos
      importe = (kilos * precio).toFixed(2);
    }

    $tr.find('#pimporte').val(importe);
    $tr.find('#tdimporte').html(importe);

    $tr.attr('data-kneto', kilosNeto);

    // total +=  parseFloat(importe);
  });

  $('input#pimporte').each(function () {
      // console.log($(this).val());
      total +=  parseFloat($(this).val());
  });

  isrTotal = (total * (parseFloat($pisrPorcent.val())||0) / 100).toFixed(2);
  $ptotal_cajas.val(totalCajas);
  $pisr.val(isrTotal);
  $ptotal.val((total - isrTotal).toFixed(2));
};

function setLoteBoleta(){
  $("#pno_lote").val("ok");
}