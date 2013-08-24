$(function(){

  $('#form').keyJump();

  $("#dcliente").autocomplete({
      source: base_url+'panel/facturacion/ajax_get_clientes/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_cliente").val(ui.item.id);
        createInfoCliente(ui.item.item);
        $("#dcliente").css("background-color", "#B0FFB0");

        $('#dplazo_credito').val(ui.item.item.dias_credito);
      }
  }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $("#dcliente").css("background-color", "#FFD9B3");
        $("#did_cliente").val("");
        $("#dcliente_rfc").val("");
        $("#dcliente_domici").val("");
        $("#dcliente_ciudad").val("");
      }
  });

  $("#dempresa").autocomplete({
      source: base_url+'panel/facturacion/ajax_get_empresas_fac/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_empresa").val(ui.item.id);
        $("#dempresa").css("background-color", "#B0FFB0");

        $('#dversion').val(ui.item.item.cfdi_version);
        $('#dcer_caduca').val(ui.item.item.cer_caduca);

        $('#dno_certificado').val(ui.item.item.no_certificado);

        loadSerieFolio(ui.item.id);
      }
  }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $("#dempresa").css("background-color", "#FFD9B3");
        $("#did_empresa").val("");
        $('#dserie').html('');
        $("#dfolio").val("");
        $("#dno_aprobacion").val("");

        $('#dversion').val('');
        $('#dcer_caduca').val('');
        $('#dno_certificado').val('');
      }
  });

  autocompleteClasifi();
  autocompleteClasifiLive();

  if ($('#did_empresa').val() !== '') {
    loadSerieFolio($('#did_empresa').val());
  }

  //Carga el folio para la serie seleccionada
  $("#dserie").on('change', function(){
    loader.create();
    $.getJSON(base_url+'panel/facturacion/get_folio/?serie='+$(this).val()+'&ide='+$('#did_empresa').val(),
    function(res){
      if(res.msg == 'ok'){
        $("#dfolio").val(res.data.folio);
        $("#dno_aprobacion").val(res.data.no_aprobacion);
        $("#dano_aprobacion").val(res.data.ano_aprobacion);
        $("#dimg_cbb").val(res.data.imagen);
      }else{
        $("#dfolio").val('');
        $("#dno_aprobacion").val('');
        $("#dano_aprobacion").val('');
        $("#dimg_cbb").val('');
        noty({"text":res.msg, "layout":"topRight", "type":res.ico});
      }
      loader.close();
    });
  });

  // $('#addProducto').on('click', function(event) {
  //   if (!valida_agregar())
  //     alert('Los campos de arriba son necesarios.');
  //   else
  //     addProducto();
  // });

  // Elimina un prod del listado
  $(document).on('click', 'button#delProd', function(e) {
      $(this).parent().parent().remove();
      calculaTotal();
  });

  // $('.prod').on('keydown', function(event) {
  //   if (event.which === 13) {
  //     $('#addProducto').click();
  //   }
  // });

  // $("form :input").on("keypress", function(e) {
  //     return e.keyCode != 13;
  // });

  // Asigna evento enter cuando dan enter al input de importe.
  $('#table_prod').on('keypress', 'input#prod_importe', function(event) {
    event.preventDefault();

    if (event.which === 13) {
      var $tr = $(this).parent().parent();

      if (valida_agregar($tr)) {
        $tr.find('td').effect("highlight", {'color': '#99FF99'}, 500);
        addProducto();
      } else {
        $tr.find('#prod_ddescripcion').focus();
        $tr.find('td').effect("highlight", {'color': '#da4f49'}, 500);
        noty({"text": 'Verifique los datos del producto.', "layout":"topRight", "type": 'error'});
      }
    }
  });

  $('#table_prod').on('keyup', '#prod_dcantidad, #prod_dpreciou', function(e) {
    var key = e.which,
        $this = $(this),
        $tr = $this.parent().parent();

    if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
      calculaTotalProducto($tr);
    }
  });

  $('#table_prod').on('change', '#diva', function(event) {
    var $this = $(this),
        $tr = $this.parent().parent();

    $tr.find('#prod_diva_porcent').val($this.find('option:selected').val());

    calculaTotalProducto ($tr);
  });

  $('#table_prod').on('change', '#dreten_iva', function(event) {
    var $this = $(this),
        $tr = $this.parent().parent();

    $tr.find('#prod_dreten_iva_porcent').val($this.find('option:selected').val());

    calculaTotalProducto ($tr)
  });
});


function calculaTotalProducto ($tr) {

  var $cantidad   = $tr.find('#prod_dcantidad'),
      $precio_uni = $tr.find('#prod_dpreciou'),
      $iva        = $tr.find('#diva'),
      $retencion  = $tr.find('#dreten_iva'),
      $importe    = $tr.find('#prod_importe'),

      $totalIva       = $tr.find('#prod_diva_total'),
      $totalRetencion = $tr.find('#prod_dreten_iva_total'),

      totalImporte   = trunc2Dec(parseFloat(($cantidad.val() || 0) * parseFloat($precio_uni.val() || 0))),
      totalIva       = trunc2Dec(((totalImporte) * parseFloat($iva.find('option:selected').val())) / 100),
      totalRetencion = trunc2Dec(totalImporte * parseFloat($retencion.find('option:selected').val()));
      // totalRetencion = trunc2Dec(totalIva * parseFloat($retencion.find('option:selected').val()));

  $totalIva.val(totalIva);
  $totalRetencion.val(totalRetencion);
  $importe.val(totalImporte);

  calculaTotal();
  // var importe   = trunc2Dec(parseFloat($('#dcantidad').val() * parseFloat($('#dpreciou').val()))),
  //     iva       = trunc2Dec(((importe - descuento) * parseFloat($('#diva option:selected').val())) / 100),
  //     retencion = trunc2Dec(iva * parseFloat($('#dreten_iva option:selected').val()));
}

var jumpIndex = 0;
function addProducto() {
  // var importe   = trunc2Dec(parseFloat($('#dcantidad').val() * parseFloat($('#dpreciou').val()))),
  //     descuento = trunc2Dec((importe * parseFloat($('#ddescuento').val())) / 100),
  //     iva       = trunc2Dec(((importe - descuento) * parseFloat($('#diva option:selected').val())) / 100),
  //     retencion = trunc2Dec(iva * parseFloat($('#dreten_iva option:selected').val()));

  var $tabla = $('#table_prod'),
      trHtml = '',
      indexJump = jumpIndex + 1;

  trHtml = '<tr>' +
              '<td>' +
                '<input type="text" name="prod_ddescripcion[]" value="" id="prod_ddescripcion" class="span12 jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">' +
                '<input type="hidden" name="prod_did_prod[]" value="" id="prod_did_prod" class="span12">' +
              '</td>' +
              '<td>' +
                '<select name="prod_dmedida[]" id="prod_dmedida" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                  '<option value="Pieza">Pieza</option>' +
                  '<option value="Caja">Caja</option>' +
                  '<option value="Kilos">Kilos</option>' +
                  '<option value="No aplica">No aplica</option>' +
                '</select>' +
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
              '<td><button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button></td>' +
            '</tr>';


  $(trHtml).appendTo($tabla.find('tbody'));

  for (i = indexJump, max = jumpIndex; i <= max; i += 1)
    $.fn.keyJump.setElem($('.jump'+i));

  $('.jump'+indexJump).focus();
}

function calculaTotal ($tr) {
  var total_importes    = 0,
      total_descuentos  = 0,
      total_ivas        = 0,
      total_retenciones = 0,
      total_factura     = 0;

  $('input#prod_importe').each(function(i, e) {
    total_importes += parseFloat($(this).val());
  });

  total_importes = trunc2Dec(total_importes);

  $('input#prod_ddescuento').each(function(i, e) {
    total_descuentos += parseFloat($(this).val());
  });

  total_descuentos = trunc2Dec(total_descuentos);

  var total_subtotal = trunc2Dec(parseFloat(total_importes) - parseFloat(total_descuentos));

  $('input#prod_diva_total').each(function(i, e) {
    total_ivas += parseFloat($(this).val());
  });

  $('input#prod_dreten_iva_total').each(function(i, e) {
    total_retenciones += parseFloat($(this).val());
  });

  total_factura = trunc2Dec(parseFloat(total_subtotal) + (parseFloat(total_ivas) - parseFloat(total_retenciones)));

  $('#importe-format').html(util.darFormatoNum(total_importes));
  $('#total_importe').val(total_importes);

  $('#descuento-format').html(util.darFormatoNum(total_descuentos));
  $('#total_descuento').val(total_descuentos);

  $('#subtotal-format').html(util.darFormatoNum(total_subtotal));
  $('#total_subtotal').val(total_subtotal);

  $('#iva-format').html(util.darFormatoNum(total_ivas));
  $('#total_iva').val(total_ivas);

  $('#retiva-format').html(util.darFormatoNum(total_retenciones));
  $('#total_retiva').val(total_retenciones);

  $('#totfac-format').html(util.darFormatoNum(total_factura));
  $('#total_totfac').val(total_factura);

  $('#total_letra').val(util.numeroToLetra.covertirNumLetras(total_factura.toString()))

}

function loadSerieFolio (ide) {
  var objselect = $('#dserie');
  loader.create();
    $.getJSON(base_url+'panel/facturacion/get_series/?ide='+ide,
      function(res){
          if(res.msg === 'ok') {
            var html_option = '<option value=""></option>';
            for (var i in res.data){
              html_option += '<option value="'+res.data[i].serie+'">'+res.data[i].serie+' - '+(res.data[i].leyenda || '')+'</option>';
            }
            objselect.html(html_option);

            $("#dfolio").val("");
            $("#dno_aprobacion").val("");
          } else {
            noty({"text":res.msg, "layout":"topRight", "type":res.ico});
          }
          loader.close();
      });
}

/**
 * Crea una cadena con la informacion del cliente para mostrarla
 * cuando se seleccione
 * @param item
 * @returns {String}
 */
function createInfoCliente(item){
  var info = '', info2 = '';

  info += item.calle!=''? item.calle: '';
  info += item.no_exterior!=''? ' #'+item.no_exterior: '';
  info += item.no_interior!=''? '-'+item.no_interior: '';
  info += item.colonia!=''? ', '+item.colonia: '';
  // info += item.localidad!=''? ', '+item.localidad: '';

  info2 += item.municipio!=''? item.municipio: '';
  info2 += item.estado!=''? ', '+item.estado: '';
  info2 += item.cp!=''? ', CP: '+item.cp: '';

  $("#dcliente_rfc").val(item.rfc);
  $("#dcliente_domici").val(info);
  $("#dcliente_ciudad").val(info2);
}


function autocompleteClasifi () {
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

function autocompleteClasifiLive () {
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

function valida_agregar ($tr) {
  // $tr.find("#prod_did_prod").val() === '' ||

  if ($tr.find("#prod_dmedida").val() === '' || $tr.find("#prod_dcantidad").val() == 0 ||
      $tr.find("#prod_dpreciou").val() == 0) {
    return false;
  }
  else return true;
}

/**
 * Modificacion del plugin autocomplete
 */
$.widget( "custom.catcomplete", $.ui.autocomplete, {
  _renderMenu: function( ul, items ) {
    var self = this,
      currentCategory = "";
    $.each( items, function( index, item ) {
      if(item.category != undefined){
        if ( item.category != currentCategory ) {
          ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
          currentCategory = item.category;
        }
      }
      self._renderItem( ul, item );
    });
  }
});

function trunc2Dec(num) {
  return Math.floor(num * 100) / 100;
}

function round2Dec(val) {
  return Math.round(val * 100) / 100;
}