(function (closure) {
  closure(jQuery, window);
})(function ($, window) {

  $(function(){
      $('#form').keyJump();

      loadSerieFolio($('#did_empresa').val());
      loadFolio();

      autocompleteClasifi();
      autocompleteClasifiLive();
      autocompleteClaveUnidadLive();

      delProducto();
      eventEnterImporte();
      eventKeyUp();
      eventChangeIva();
      eventChangeRetIva();

      EventOnChangeMoneda();
  });

  /*
   |------------------------------------------------------------------------
   | AJAX
   |------------------------------------------------------------------------
   */

  // Carga las series de la empresa.
  var loadSerieFolio = function (ide) {
    var $select = $('#dserie');
    loader.create();
      $.getJSON(base_url+'panel/notas_credito/ajax_get_series_folio/?ide='+ide, function(res){
        if(res.msg === 'ok') {
          var html_option = '<option value=""></option>';
          for (var i in res.data) {
            html_option += '<option value="'+res.data[i].serie+'">'+res.data[i].serie+' - '+(res.data[i].leyenda || '')+'</option>';
          }
          $select.html(html_option);

          $("#dfolio").val("");
          $("#dno_aprobacion").val("");
        } else {
          noty({"text":res.msg, "layout":"topRight", "type":res.ico});
        }
        loader.close();
      });
  };

  //Carga el folio para la serie seleccionada.
  var loadFolio = function () {
    $("#dserie").on('change', function(){
      loader.create();
      $.getJSON(base_url+'panel/notas_credito/ajax_get_folio/?serie='+$(this).val()+'&ide='+$('#did_empresa').val(),
      function(res){
        if(res.msg == 'ok'){
          $("#dfolio").val(res.data.folio);
          $("#dno_aprobacion").val(res.data.no_aprobacion);
          $("#dano_aprobacion").val(res.data.ano_aprobacion);
          // $("#dimg_cbb").val(res.data.imagen);
        }else{
          $("#dfolio").val('');
          $("#dno_aprobacion").val('');
          $("#dano_aprobacion").val('');
          // $("#dimg_cbb").val('');
          noty({"text":res.msg, "layout":"topRight", "type":res.ico});
        }
        loader.close();
      });
    });
  };

  /*
   |------------------------------------------------------------------------
   | AUTOCOMPLETE
   |------------------------------------------------------------------------
   */

  // Autocomplete Clasificaciones.
  var autocompleteClasifi = function () {
   $("input#prod_ddescripcion").autocomplete({
      source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this),
            $tr = $this.parent().parent();

        $this.css("background-color", "#B0FFB0");

        $tr.find('#prod_did_prod').val(ui.item.id);
        // $tr.find('#prod_dpreciou').val(ui.item.item.precio);
      }
    }).keydown(function(event){
        if(event.which == 8 || event == 46){
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#prod_did_prod').val('');
        }
    });
  }

  // Autocomplete Clasificaciones Live.
  var autocompleteClasifiLive = function () {
    $('#table_prod').on('focus', 'input#prod_ddescripcion:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('#prod_did_prod').val(ui.item.id);
          // $tr.find('#prod_dpreciou').val(ui.item.item.precio);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#prod_did_prod').val('');
        }
      });
    });
  }

  var autocompleteClaveUnidadLive = function () {
    $('#table_prod').on('focus', 'input#pclave_unidad:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/catalogos33/claveUnidad/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('#pclave_unidad_cod').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#pclave_unidad_cod').val('');
        }
      });
    });
  }

  /*
   |------------------------------------------------------------------------
   | EVENTOS
   |------------------------------------------------------------------------
   */

  var EventOnChangeMoneda = function () {
    $('#moneda').on('change', function(event) {
      if($(this).val() !== 'M.N.')
        $("#tipoCambio").show().focus();
      else
        $("#tipoCambio").val().hide();
    });
  };

  // Evento click. Elimina un producto del listado.
  var delProducto = function () {
    $(document).on('click', 'button#delProd', function(e) {
      $(this).parent().parent().remove();
      calculaTotal();
    });
  };

  // Asigna el evento "enter" a los inputs importe.
  var eventEnterImporte = function () {
    $('#table_prod').on('keypress', 'input#prod_importe', function(event) {
      event.preventDefault();

      if (event.which === 13) {
        var $tr = $(this).parent().parent();

        if (validAdd($tr)) {
          $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);
          addProducto();
        } else {
          $tr.find('#prod_ddescripcion').focus();
          $tr.find('td').effect("highlight", {'color': '#da4f49'}, 500);
          noty({"text": 'Verifique los datos del producto.', "layout":"topRight", "type": 'error'});
        }
      }
    });
  };

  // Evento Keyup para los input cantidad y precio unitario.
  var eventKeyUp = function () {
    $('#table_prod').on('keyup', '#prod_dcantidad, #prod_dpreciou, #dieps, #disr', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parents('tr');

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalProducto($tr);
      }
    });
  };

  // Evento change para el select iva.
  var eventChangeIva = function () {
    $('#table_prod').on('change', '#diva', function(event) {
      var $this = $(this),
          $tr = $this.parents('tr');

      $tr.find('#prod_diva_porcent').val($this.find('option:selected').val());

      calculaTotalProducto($tr);
    });
  };

  // Evento change para el select retencion.
  var eventChangeRetIva = function () {
    $('#table_prod').on('change', '#dreten_iva', function(event) {
      var $this = $(this),
          $tr = $this.parent('tr');

      $tr.find('#prod_dreten_iva_porcent').val($this.find('option:selected').val());

      calculaTotalProducto($tr)
    });
  };

  /*
   |------------------------------------------------------------------------
   | VALIDACIONES
   |------------------------------------------------------------------------
   */

  // Valida si un producto esta correcto.
  var validAdd = function ($tr) {
    if ($tr.find("#prod_dmedida").val() === '' || $tr.find("#prod_dcantidad").val() == 0 ||
        $tr.find("#prod_dpreciou").val() == 0 || $tr.find("#pclave_unidad").val() == '') {
      return false;
    } else return true;
  };

  /*
   |------------------------------------------------------------------------
   | DOM MANIPULACION
   |------------------------------------------------------------------------
   */

  // Agrega un nuevo <tr> al listado de productos.
  var jumpIndex = 0;
  var addProducto = function () {
    var $tabla    = $('#table_prod'),
        trHtml    = '',
        indexJump = jumpIndex + 1;

    trHtml = '<tr>' +
                '<td>' +
                  '<input type="text" name="prod_ddescripcion[]" value="" id="prod_ddescripcion" class="span12 jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" name="prod_did_prod[]" value="" id="prod_did_prod" class="span12">' +

                  '<input type="hidden" name="no_identificacion[]" value="" id="no_identificacion" class="span9 pull-right">'+
                  '<input type="hidden" name="prod_dcalidad[]" value="" id="prod_dcalidad" class="span9 pull-right">'+
                  '<input type="hidden" name="prod_dtamanio[]" value="" id="prod_dtamanio" class="span9 pull-right">'+
                  '<input type="hidden" name="prod_ddescripcion2[]" value="" id="prod_ddescripcion2" class="span9 pull-right">'+
                  '<input type="hidden" name="prod_did_calidad[]" value="" id="prod_did_calidad" class="span9 pull-right">'+
                  '<input type="hidden" name="prod_did_tamanio[]" value="" id="prod_did_tamanio" class="span9 pull-right">'+
                '</td>' +
                '<td><input type="text" name="prod_dmedida[]" value="" id="prod_dmedida" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
                  '<input type="text" name="pclave_unidad[]" class="span12 jump'+jumpIndex+'" id="pclave_unidad" value="" placeholder="Clave de Unidad" data-next="jump'+(++jumpIndex)+'">'+
                  '<input type="hidden" name="pclave_unidad_cod[]" class="span9" id="pclave_unidad_cod" value="">'+
                '</td>' +
                '<td>' +
                    '<input type="text" name="prod_dcantidad[]" value="0" id="prod_dcantidad" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                '</td>' +
                '<td>' +
                  '<input type="text" name="prod_dpreciou[]" value="0" id="prod_dpreciou" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                '</td>' +
                '<td>' +
                    '<select name="diva" id="diva" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                      '<option value="0">0%</option>' +
                      '<option value="11">11%</option>' +
                      '<option value="16">16%</option>' +
                    '</select>' +
                    '<input type="hidden" name="prod_diva_total[]" value="0" id="prod_diva_total" class="span12">' +
                    '<input type="hidden" name="prod_diva_porcent[]" value="0" id="prod_diva_porcent" class="span12">' +
                '</td>' +
                '<td>' +
                  '<select name="dreten_iva" id="dreten_iva" class="span12 prod jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                    '<option value="0">No retener</option>' +
                    '<option value="0.04">4%</option>' +
                    '<option value="0.10667">2 Terceras</option>' +
                    '<option value="0.16">100 %</option>' +
                  '</select>' +
                  '<input type="hidden" name="prod_dreten_iva_total[]" value="0" id="prod_dreten_iva_total" class="span12">' +
                  '<input type="hidden" name="prod_dreten_iva_porcent[]" value="0" id="prod_dreten_iva_porcent" class="span12">' +
                '</td>' +
                '<td>' +
                  '<input type="text" name="prod_importe[]" value="0" id="prod_importe" class="span12 vpositive jump'+jumpIndex+'">' +
                '</td>' +
                '<td>' +
                  '<div class="btn-group">' +
                    '<button type="button" class="btn impuestosEx">' +
                      '<span class="caret"></span>' +
                    '</button>' +
                    '<ul class="dropdown-menu impuestosEx" style="width: 250px;">' +
                      '<li class="clearfix">' +
                        '<label class="pull-left">% IEPS:</label> <input type="number" step="any" name="dieps[]" value="0" id="dieps" max="100" min="0" class="span9 pull-right vpositive">' +
                        '<input type="hidden" name="dieps_total[]" value="0" id="dieps_total" class="span12">' +
                      '</li>' +
                      '<li class="clearfix">' +
                        '<label class="pull-left">% Ret ISR:</label> <input type="number" step="any" name="disr[]" value="0" id="disr" max="100" min="0" class="span9 pull-right vpositive">' +
                        '<input type="hidden" name="disr_total[]" value="0" id="disr_total" class="span12">' +
                      '</li>' +
                    '</ul>' +
                  '</div>' +
                  '<button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>' +
                '</td>' +
              '</tr>';

    $(trHtml).appendTo($tabla.find('tbody'));

    for (i = indexJump, max = jumpIndex; i <= max; i += 1)
      $.fn.keyJump.setElem($('.jump'+i));

    $('.jump'+indexJump).focus();
  };

  /*
   |------------------------------------------------------------------------
   | TOTALES
   |------------------------------------------------------------------------
   */

  // Calcula el total por producto.
  var calculaTotalProducto = function ($tr) {
    var $cantidad   = $tr.find('#prod_dcantidad'),
        $precio_uni = $tr.find('#prod_dpreciou'),
        $iva        = $tr.find('#diva'),
        $ieps       = $tr.find('#dieps'),
        $isr        = $tr.find('#disr'),
        $retencion  = $tr.find('#dreten_iva'),
        $importe    = $tr.find('#prod_importe'),
        $iepsSub    = $tr.find('#prod_ieps_subtotal'),

        $totalIva       = $tr.find('#prod_diva_total'),
        $totalRetencion = $tr.find('#prod_dreten_iva_total'),
        $totalIeps      = $tr.find('#dieps_total'),
        $totalIsr       = $tr.find('#disr_total'),

        totalImporte   = util.trunc2Dec(parseFloat(($cantidad.val() || 0) * parseFloat($precio_uni.val() || 0))),
        // totalIva       = util.trunc2Dec(((totalImporte) * parseFloat($iva.find('option:selected').val())) / 100),
        totalRetencion = util.trunc2Dec(totalImporte * parseFloat($retencion.find('option:selected').val()));
        totalIeps      = util.trunc2Dec(((totalImporte) * (parseFloat($ieps.val())||0) ) / 100);
        totalIsr       = util.trunc2Dec(((totalImporte) * (parseFloat($isr.val())||0) ) / 100);

    console.log('iva con el ieps', $iepsSub.val(), $cantidad.val(), $precio_uni.val());
    if($iepsSub.val() == 't') {
      totalIva = util.trunc2Dec(((totalImporte+totalIeps) * (parseFloat($iva.find('option:selected').val()) || 0) ) / 100)
    } else {
      totalIva = util.trunc2Dec(((totalImporte) * (parseFloat($iva.find('option:selected').val()) || 0) ) / 100);
    }

    $totalIva.val(totalIva);
    $totalIeps.val(totalIeps);
    $totalIsr.val(totalIsr);
    $totalRetencion.val(totalRetencion);
    $importe.val(totalImporte);

    calculaTotal();
    // var importe   = trunc2Dec(parseFloat($('#dcantidad').val() * parseFloat($('#dpreciou').val()))),
    //     iva       = trunc2Dec(((importe - descuento) * parseFloat($('#diva option:selected').val())) / 100),
    //     retencion = trunc2Dec(iva * parseFloat($('#dreten_iva option:selected').val()));
  };

  // Calcula el total de la nota de credito.
  var calculaTotal = function ($tr) {
    var total_importes = 0,
        total_descuentos = 0,
        total_ivas = 0,
        total_ieps        = 0,
        total_isr         = 0,
        total_retenciones = 0,
        total_factura = 0;

    $('input#prod_importe').each(function(i, e) {
      total_importes += parseFloat($(this).val());
    });

    $('input#prod_ddescuento').each(function(i, e) {
      total_descuentos += parseFloat($(this).val());
    });

    $('input#dieps_total').each(function(i, e) {
      total_ieps += parseFloat($(this).val());
    });

    $('input#disr_total').each(function(i, e) {
      total_isr += parseFloat($(this).val());
    });


    var total_subtotal = parseFloat(total_importes) - parseFloat(total_descuentos);

    $('input#prod_diva_total').each(function(i, e) {
      total_ivas += parseFloat($(this).val());
    });

    $('input#prod_dreten_iva_total').each(function(i, e) {
      total_retenciones += parseFloat($(this).val());
    });

    total_factura = (parseFloat(total_subtotal) + parseFloat(total_ivas) + parseFloat(total_ieps) - parseFloat(total_retenciones) - parseFloat(total_isr));
    console.log( total_factura );

    $('#importe-format').html(util.darFormatoNum(total_importes.toFixed(2)));
    $('#total_importe').val(total_importes.toFixed(2));

    $('#descuento-format').html(util.darFormatoNum(total_descuentos.toFixed(3)));
    $('#total_descuento').val(total_descuentos.toFixed(3));

    $('#subtotal-format').html(util.darFormatoNum(total_subtotal.toFixed(2)));
    $('#total_subtotal').val(total_subtotal.toFixed(2));

    $('#iva-format').html(util.darFormatoNum(total_ivas.toFixed(2)));
    $('#total_iva').val(total_ivas.toFixed(2));

    $('#ieps-format').html(util.darFormatoNum(total_ieps));
    $('#total_ieps').val(total_ieps);

    $('#isr-format').html(util.darFormatoNum(total_isr));
    $('#total_isr').val(total_isr);

    $('#retiva-format').html(util.darFormatoNum(total_retenciones.toFixed(2)));
    $('#total_retiva').val(total_retenciones.toFixed(2));

    $('#totfac-format').html(util.darFormatoNum(total_factura.toFixed(2)));
    $('#total_totfac').val(total_factura.toFixed(2));

    $('#total_letra').val(util.numeroToLetra.covertirNumLetras(total_factura.toFixed(2).toString(), $('#moneda').val()))

  }

});