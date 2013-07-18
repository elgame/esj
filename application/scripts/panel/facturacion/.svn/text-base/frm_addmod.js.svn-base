$(function(){

  $("#dfecha").datepicker({
       dateFormat: 'yy-mm-dd', //formato de la fecha - dd,mm,yy=dia,mes,año numericos  DD,MM=dia,mes en texto
       //minDate: '-2Y', maxDate: '+1M +10D', //restringen a un rango el calendario - ej. +10D,-2M,+1Y,-3W(W=semanas) o alguna fecha
       changeMonth: true, //permite modificar los meses (true o false)
       changeYear: true, //permite modificar los años (true o false)
       //yearRange: (fecha_hoy.getFullYear()-70)+':'+fecha_hoy.getFullYear(),
       numberOfMonths: 1 //muestra mas de un mes en el calendario, depende del numero
     });

  $("#dcliente").autocomplete({
      source: base_url+'panel/clientes/ajax_get_clientes',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_cliente").val(ui.item.id);
        createInfoCliente(ui.item.item);
        $("#dcliente").css("background-color", "#B0FFB0");
        reAutocomplete();
      }
  }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $("#dcliente").val("").css("background-color", "#FFD9B3");
        $("#did_cliente").val("");
        $("#dcliente_rfc").val("");
        $("#dcliente_domici").val("");
        $("#dcliente_ciudad").val("");
        reAutocomplete();
      }
  });

  $("#dempresa").autocomplete({
      source: base_url+'panel/facturacion/ajax_get_empresas',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_empresa").val(ui.item.id);
        $("#dempresa").css("background-color", "#B0FFB0");

        loadSerieFolio(ui.item.id);
      }
  }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $("#dempresa").val("").css("background-color", "#FFD9B3");
        $("#did_empresa").val("");
        $('#dserie').html('');
        $("#dfolio").val("");
        $("#dno_aprobacion").val("");
      }
  });

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

  $('#addProducto').on('click', function(event) {
    if (!valida_agregar())
      alert('Los campos de arriba son necesarios.');
    else
      addProducto();
  });

  $(document).on('click', 'button#delProd', function(e) {
      $(this).parent().parent().remove();
      calculaTotal();
  });

  $('.prod').on('keydown', function(event) {
    if (event.which === 13) {
      $('#addProducto').click();
    }
  });

  $("form :input").on("keypress", function(e) {
      return e.keyCode != 13;
  });

});

function addProducto() {
  var importe   = trunc2Dec(parseFloat($('#dcantidad').val() * parseFloat($('#dpreciou').val()))),
      descuento = trunc2Dec((importe * parseFloat($('#ddescuento').val())) / 100),
      iva       = trunc2Dec(((importe - descuento) * parseFloat($('#diva option:selected').val())) / 100),
      retencion = trunc2Dec(iva * parseFloat($('#dreten_iva option:selected').val()));

  var html_td = '<tr><td><input type="hidden" name="prod_did_prod[]" value="'+$('#did_prod').val()+'" id="prod_did_prod">' +
                         '<input type="hidden" name="prod_dcantidad[]" value="'+$('#dcantidad').val()+'" id="prod_dcantidad">'+$('#dcantidad').val()+'</td>' +
                '<td><input type="hidden" name="prod_ddescripcion[]" value="'+$('#ddescripcion').val()+'" id="prod_ddescripcion">'+$('#ddescripcion').val()+'</td>' +
                '<td><input type="hidden" name="prod_ddescuento[]" value="'+descuento+'" id="prod_ddescuento"><input type="hidden" name="prod_ddescuento_porcent[]" value="'+$('#ddescuento').val()+'" id="prod_ddescuento_porcent">'+$('#ddescuento').val()+'%</td>' +
                '<td><input type="hidden" name="prod_dpreciou[]" value="'+$('#dpreciou').val()+'" id="prod_dpreciou">'+util.darFormatoNum($('#dpreciou').val())+'</td>' +
                '<td><input type="hidden" name="prod_importe[]" value="'+importe+'" id="prod_importe">'+util.darFormatoNum(importe)+'</td>' +
                '<td><input type="hidden" name="prod_diva_total[]" value="'+iva+'" id="prod_diva_total"> ' +
                    '<input type="hidden" name="prod_dreten_iva_total[]" value="'+retencion+'" id="prod_dreten_iva_total">' +
                    '<input type="hidden" name="prod_dreten_iva_porcent[]" value="'+$('#dreten_iva option:selected').val()+'" id="prod_dreten_iva_porcent">' +
                    '<input type="hidden" name="prod_diva_porcent[]" value="'+$('#diva').val()+'" id="prod_diva_porcent">'+$('#diva').val()+'%</td>' +
                '<td><input type="hidden" name="prod_dmedida[]" value="'+$('#dmedida').val()+'">'+$('#dmedida').val()+'</td>' +
                '<td><button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button></td></tr>';

  $('#table_prod').find('tbody').append(html_td);
  calculaTotal();
  limpiar();
}

function calculaTotal () {
  var total_importes = 0,
      total_descuentos = 0,
      total_ivas = 0,
      total_retenciones = 0,
      total_factura = 0;

  $('input#prod_importe').each(function(i, e) {
    total_importes += parseFloat($(this).val());
  });

  $('input#prod_ddescuento').each(function(i, e) {
    total_descuentos += parseFloat($(this).val());
  });

  var total_subtotal = parseFloat(total_importes) - parseFloat(total_descuentos);

  $('input#prod_diva_total').each(function(i, e) {
    total_ivas += parseFloat($(this).val());
  });

  $('input#prod_dreten_iva_total').each(function(i, e) {
    total_retenciones += parseFloat($(this).val());
  });

  total_factura = parseFloat(total_subtotal) + (parseFloat(total_ivas) - parseFloat(total_retenciones));

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
              html_option += '<option value="'+res.data[i].serie+'">'+res.data[i].serie+' - '+res.data[i].leyenda+'</option>';
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
  info += (item.localidad!=''? ', '+item.localidad: '');

  info2 += item.municipio!=''? item.municipio: '';
  info2 += item.estado!=''? ', '+item.estado: '';
  info2 += item.cp!=''? ', CP: '+item.cp: '';

  $("#dcliente_rfc").val(item.rfc);
  $("#dcliente_domici").val(info);
  $("#dcliente_ciudad").val(info2);
}


function reAutocomplete () {
 $("#ddescripcion").autocomplete({
    source: base_url+'panel/productos/ajax_get_productos?cliente=' + $('#did_cliente').val(),
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {

      $("#did_prod").val(ui.item.id);
      $("#dpreciou").val(ui.item.item.precio);
      $("#dcantidad").val(1);
      $("#dmedida").val(ui.item.item.nombre_unidad);
      $("#ddescuento").val(0);
      $("#ddescripcion").css("background-color", "#B0FFB0");
    }
  });
}

function limpiar () {
  $("#ddescripcion").val("").css("background-color", "#FFF");
  $("#did_prod").val('');
  $("#dpreciou").val('');
  $("#diva").val('');
  $("#ddescuento").val('');
  $("#dreten_iva").val('');
  $("#dcantidad").val('');
  $("#dmedida").val('');
}

function valida_agregar () {
  if ($("#ddescripcion").val() === '' || $("#dpreciou").val() === '' || $("#dcantidad").val() === '' || $("#dmedida").val() === '' || $("#ddescuento").val() === '') {
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