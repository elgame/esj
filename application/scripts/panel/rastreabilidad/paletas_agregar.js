$(function(){

  $('#form-search').keyJump({
    'next': 13,
  });

  addpaletas.init();


});

var addpaletas = (function($){
  var objr = {}, tbody, tbodysel, total_cajas_sel, fcajas,
  fid_clasificacion, fclasificacion,
  funidad, fidunidad,
  fcalibre, fidcalibre,
  fetiqueta, fidetiqueta, contadorInputs;

  function init(){
    contadorInputs = 0;

    eventLigarBoletasSalida();
    setBoletasSel();
  }

  // Datos extras
  function eventLigarBoletasSalida() {
    $("#show-boletasSalidas").on('click', function(event) {
      $("#filBoleta").val("");

      getBoletas(['en', 'sa', 'p', 'b']);
      $("#modal-boletas").modal('show');
      $("#modal-boletas #BtnAddBoleta").addClass('entrada');
    });

    $("#filBoleta").on('change', function(event) {
      getBoletas(['en', 'sa', 'p', 'b']);
    });
  }

  function getBoletas(accion){
    var params = {
      tipoo: 'sa',
      accion: (accion? accion: ['en', 'p', 'b']),
      filtro: $("#filBoleta").val()
    };
    $.getJSON(base_url+"panel/compras_ordenes/ajaxGetBoletas/", params, function(json, textStatus) {
      var html = '';
      for (var i in json) {
        html += '<tr class="radioBoleta" data-id="'+json[i].id_bascula+'" data-folio="'+json[i].folio+'" '+
          'data-idempresa="'+json[i].id_empresa+'" data-empresa="'+json[i].empresa+'" style="cursor: pointer;">'+
        '  <td>'+json[i].fecha+'</td>'+
        '  <td>'+json[i].folio+'</td>'+
        '  <td>'+json[i].cliente+'</td>'+
        '  <td>'+json[i].area+'</td>'+
        '</tr>';
      }
      $("#table-boletas tbody").html(html);
    });
  }

  function setBoletasSel() {
    $("#table-boletas").on('dblclick', 'tr.radioBoleta', function(event) {
      var $this = $(this);

      $('#boletasSalidasFolio').val($this.attr('data-folio'));
      $('#boletasSalidasId').val($this.attr('data-id'));
      $('#empresa').val($this.attr('data-empresa'));
      $('#empresaId').val($this.attr('data-idempresa'));

      $("#modal-boletas").modal('hide');
    });
  }


  // Agregar clasificaciones
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

          $tr.find('#prod_dmedida').find('[data-id="'+ui.item.item.id_unidad+'"]').attr('selected', 'selected');
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

  function nuevoRegistro() {
    $('#table_prod').on('keypress', 'input#prod_dcantidad', function(event) {
      event.preventDefault();

      if (event.which === 13) {
        var $tr = $(this).parent().parent();

        if (valida_agregar($tr)) {
          $tr.find('td').not('.cporte').effect("highlight", {'color': '#99FF99'}, 500);
          addProducto($tr);
        } else {
          $tr.find('#prod_ddescripcion').focus();
          $tr.find('td').not('.cporte').effect("highlight", {'color': '#da4f49'}, 500);
          noty({"text": 'Verifique los datos del producto.', "layout":"topRight", "type": 'error'});
        }
      }
    });
  }

  function valida_agregar ($tr) {
    if ($tr.find("#prod_id_cliente").val() === '' || $tr.find("#prod_did_prod").val() == '' ||
      $tr.find("#prod_dcantidad").val() == '') {
      return false;
    }
    else return true;
  }

  var jumpIndex = 0;
function addProducto(unidades, prod) {
  // var importe   = trunc2Dec(parseFloat($('#dcantidad').val() * parseFloat($('#dpreciou').val()))),
  //     descuento = trunc2Dec((importe * parseFloat($('#ddescuento').val())) / 100),
  //     iva       = trunc2Dec(((importe - descuento) * parseFloat($('#diva option:selected').val())) / 100),
  //     retencion = trunc2Dec(iva * parseFloat($('#dreten_iva option:selected').val()));
  var $tabla = $('#table_prod'),
      trHtml    = '',
      indexJump = jumpIndex + 1,
      existe    = false,
      $tr, addInputPalletId = true;

  var prod_nombre = '', prod_id = '', pallet = '', remision = '', prod_cajas = 0,
      ivaSelected = '0', prod_kilos = 0, cantidad = 0, prod_certificado = false,
      prod_dcalidad = '', prod_did_calidad = '', prod_dtamanio = '', prod_did_tamanio = '', prod_ddescripcion2 = '';

  // Pasa los gastos a la otra tabla
  pasaGastosTabla();

  if (prod) {
    // console.log(prod);
    // Verificar si existe la clasificacion...
    var estaCertificado;
    $tabla.find('input#prod_did_prod').each(function(index, el) {
      var $prodIdInput = $(this), // input hidde prod id.
          $medidaInput; // input hidde medida.

      $tr = $prodIdInput.parents('tr'); // tr parent.
      $medidaInput = $tr.find('#prod_dmedida_id'); // input hidde medida.
      $idUnidadRendimiento = $tr.find('#id_unidad_rendimiento');
      $idSizeRendimiento = $tr.find('#id_size_rendimiento');
      estaCertificado = $tr.find('.is-cert-check').is(':checked') ? 't' : 'f';

      // console.log($prodIdInput.val(), prod.id, $idUnidadRendimiento.val(), prod.id_unidad, $idSizeRendimiento.val(), prod.id_size, estaCertificado, prod.certificado);
      if ($prodIdInput.val() == prod.id && $idUnidadRendimiento.val() == prod.id_unidad && $idSizeRendimiento.val() == prod.id_size && estaCertificado === prod.certificado) {
        existe = true;
        return false;
      }
    });

    prod_nombre = prod.nombre;
    prod_id     = prod.id;
    prod_cajas  = prod.cajas;
    prod_kilos  = prod.kilos;
    pallet      = prod.id_pallet;
    remision    = prod.id_remision ? prod.id_remision : '';
    idUnidad    = prod.id_unidad ? prod.id_unidad : ''; // id_unidad del rendimiento.
    idSize      = prod.id_size ? prod.id_size : ''; // id_size del rendimiento.
    unidad      = prod.unidad ? prod.unidad : ''; // nombre de la unidad del rendimiento.
    size        = prod.size ? prod.size : ''; // nombre de la size del rendimiento.
    prod_nombre += ' ' + unidad + ' ' + size; // le concatena la unidad del rendmiento a la descripcion.

    idUnidadClasificacion = prod.id_unidad_clasificacion ? prod.id_unidad_clasificacion : '';
    ivaSelected = prod.iva_clasificacion ? prod.iva_clasificacion : '';
    prod_certificado =  prod.certificado === 't' ? true : false;

    prod_dcalidad      = prod.areas_calidad;
    prod_did_calidad   = prod.id_calidad;
    prod_dtamanio      = prod.areas_tamanio;
    prod_did_tamanio   = prod.id_tamanio;
    prod_ddescripcion2 = prod.descripcion2;

  } else {
    idUnidad = unidades[0].id_unidad;
    unidad = unidades[0].nombre;
    idUnidadClasificacion = unidades[0].id_unidad;
    idSize = '';
    size = '';
  }

  // Si el producto existe en el listado.
  if (existe) {
    var $cantidadInput = $tr.find('#prod_dcantidad'), // input cantidad.
        $medidaInput = $tr.find('#prod_dmedida_id'), // input hidde medida.
        $kilosInput = $tr.find('#prod_dkilos'),
        $cajasInput = $tr.find('#prod_dcajas');

    // Le suma la cantidad de cajas a la clasificacion.

    // Si la unidad de medida de la clasificacion del rendimiento es la 19
    // Cambiar el id de los kilos por el q este en la bdd.
    if ($medidaInput.val() == '19') {
      $cantidadInput.val(parseFloat($cantidadInput.val()) + parseFloat(prod.kilos));
    } else {
      $cantidadInput.val(parseFloat($cantidadInput.val()) + parseFloat(prod.cajas));
    }

    // Le suma los kilos y las cajas a las que ya existen, para si switchea de
    // medida entonces cargue los kilos o las cajas.
    $kilosInput.val(parseFloat($kilosInput.val()) + parseFloat(prod.kilos));
    $cajasInput.val(parseFloat($cajasInput.val()) + parseFloat(prod.cajas));

    calculaTotalProducto($tr);

    var existe2 = false,
        palletsClasifi = $tr.attr('data-pallets').split('-');

    for (var i in palletsClasifi) {
      if (palletsClasifi[i] == prod.id_pallet) {
        existe2 = true;
        return false;
      }
    }

    if ( ! existe2) {
      var pallets = $tr.attr('data-pallets') + '-' + prod.id_pallet;
      $tr.attr('data-pallets', pallets);
      $tr.find('#pallets_id').val(pallets);
    }

    // Remision
    var existeRemision = false,
        remisiones = $tr.attr('data-remisiones').split('-');

    for (var r in remisiones) {
      if (remisiones[r] == prod.id_remision) {
        existeRemision = true;
        return false;
      }
    }

    if ( ! existeRemision) {
      var remisionesIds = $tr.attr('data-remisiones') + '-' + prod.id_remision;
      $tr.attr('data-remisiones', remisionesIds);
      $tr.find('#remisiones_id').val(remisionesIds);
    }

  } else {
    var unidadesHtml = '';
    for (var i in unidades) {
      unidadesHtml += '<option value="'+unidades[i].nombre+'" '+(unidades[i].id_unidad == idUnidadClasificacion ? 'selected' : '')+' data-id="'+unidades[i].id_unidad+'">'+unidades[i].nombre+'</option>';
    }

    // Si el id de unidad es la 19 osea de kilos entonces en cantidad coloca
    // los kilos en vez de las cajas.
    // Cambiar el id que le corresponda a los KILOS en las unidades.
    if (idUnidadClasificacion == '19') {
      cantidad = prod_kilos;
    } else {
      cantidad = prod_cajas;
    }

    var htmlCPorteClase = '', htmlCPortePeso = '';
    if ($('#es-carta-porte').is(':checked')) {
      htmlCPorteClase = '<td class="cporte">' +
                          '<input type="text" name="prod_dclase[]" value="" id="prod_dclase" class="span12" style="width: 50px;">' +
                        '</td>';
      htmlCPortePeso =  '<td class="cporte">' +
                          '<input type="text" name="prod_dpeso[]" value="" id="prod_dpeso" class="span12 vpositive" style="width: 80px;">' +
                        '</td>';
    }

    trHtml = '<tr data-pallets="'+pallet+'" data-remisiones="'+remision+'">' +
                '<td style="width:31px;">' +
                  '<div class="btn-group">' +
                    '<button type="button" class="btn ventasmore">' +
                      '<span class="caret"></span>' +
                    '</button>' +
                    '<ul class="dropdown-menu ventasmore">' +
                      '<li class="clearfix">'+
                        '<label class="pull-left"># ident:</label> <input type="text" name="no_identificacion[]" value="" id="no_identificacion" class="span9 pull-right jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">'+
                      '</li>'+
                      '<li class="clearfix">' +
                        '<label class="pull-left">Calidad:</label> <input type="text" name="prod_dcalidad[]" value="'+prod_dcalidad+'" id="prod_dcalidad" class="span9 pull-right jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                        '<input type="hidden" name="prod_did_calidad[]" value="'+prod_did_calidad+'" id="prod_did_calidad" class="span12">' +
                      '</li>' +
                      '<li class="divider"></li>' +
                      '<li class="clearfix">' +
                        '<label class="pull-left">Tamaño:</label> <input type="text" name="prod_dtamanio[]" value="'+prod_dtamanio+'" id="prod_dtamanio" class="span9 pull-right jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                        '<input type="hidden" name="prod_did_tamanio[]" value="'+prod_did_tamanio+'" id="prod_did_tamanio" class="span12">' +
                      '</li>' +
                      '<li class="divider"></li>' +
                      '<li class="clearfix">' +
                        '<label class="pull-left">Descripción:</label> <input type="text" name="prod_ddescripcion2[]" value="'+prod_ddescripcion2+'" id="prod_ddescripcion2" class="span9 pull-right jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                      '</li>' +
                    '</ul>' +
                  '</div>' +
                '</td>' +
                '<td>' +
                  '<input type="text" name="prod_ddescripcion[]" value="'+prod_nombre+'" id="prod_ddescripcion" class="span12 jump'+(jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" name="prod_did_prod[]" value="'+prod_id+'" id="prod_did_prod" class="span12">' +
                  '<input type="hidden" name="pallets_id[]" value="'+pallet+'" id="pallets_id" class="span12">' +
                  '<input type="hidden" name="remisiones_id[]" value="'+remision+'" id="remisiones_id" class="span12">' +
                  '<input type="hidden" name="id_unidad_rendimiento[]" value="'+idUnidad+'" id="id_unidad_rendimiento" class="span12">' +
                  '<input type="hidden" name="id_size_rendimiento[]" value="'+idSize+'" id="id_size_rendimiento" class="span12">' +
                '</td>' +
                (htmlCPorteClase !== '' ? $(htmlCPorteClase).find('#prod_dclase').addClass('jump'+(jumpIndex)).attr('data-next', 'jump'+(++jumpIndex)+'').parent().prop('outerHTML') : '')+
                (htmlCPortePeso !== '' ? $(htmlCPortePeso).find('#prod_dpeso').addClass('jump'+(jumpIndex)).attr('data-next', 'jump'+(++jumpIndex)+'').parent().prop('outerHTML') : '') +
                '<td>' +
                  '<select name="prod_dmedida[]" id="prod_dmedida" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                    unidadesHtml +
                    // '<option value="Pieza">Pieza</option>' +
                    // '<option value="Caja">Caja</option>' +
                    // '<option value="Kilos">Kilos</option>' +
                    // '<option value="No aplica">No aplica</option>' +
                  '</select>' +
                  '<input type="hidden" name="prod_dmedida_id[]" value="'+idUnidadClasificacion+'" id="prod_dmedida_id" class="span12 vpositive">' +

                  '<input type="text" name="pclave_unidad[]" class="span12 jump'+jumpIndex+'" id="pclave_unidad" value="" placeholder="Clave de Unidad" data-next="jump'+(++jumpIndex)+'">'+
                  '<input type="hidden" name="pclave_unidad_cod[]" class="span9" id="pclave_unidad_cod" value="">'+
                '</td>' +
                '<td>' +
                    '<input type="text" name="prod_dcantidad[]" value="'+cantidad+'" id="prod_dcantidad" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                    '<input type="hidden" name="prod_dkilos[]" value="'+prod_kilos+'" id="prod_dkilos" class="span12 vpositive">' +
                    '<input type="hidden" name="prod_dcajas[]" value="'+prod_cajas+'" id="prod_dcajas" class="span12 vpositive">' +
                '</td>' +
                '<td>' +
                  '<input type="text" name="prod_dpreciou[]" value="0" id="prod_dpreciou" class="span12 vnumeric jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                '</td>' +
                '<td>' +
                    '<select name="diva" id="diva" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                      '<option value="0"'+(ivaSelected == '0' ? 'selected' : '')+'>0%</option>' +
                      '<option value="8"'+(ivaSelected == '8' ? 'selected' : '')+'>8%</option>' +
                      '<option value="16"'+(ivaSelected == '16' ? 'selected' : '')+'>16%</option>' +
                    '</select>' +
                    // '<input type="hidden" name="prod_diva_total[]" value="0" id="prod_diva_total" class="span12">' +
                    '<input type="hidden" name="prod_diva_porcent[]" value="'+ivaSelected+'" id="prod_diva_porcent" class="span12">' +
                '</td>' +
                '<td style="width: 80px;">' +
                    '<input type="text" name="prod_diva_total[]" value="0" id="prod_diva_total" class="span12" readonly>' +
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
                '<td><input type="checkbox" class="is-cert-check" ' + (prod_certificado ? 'checked' : '')  + ' ><input type="hidden" name="isCert[]" value="' + (prod_certificado ? '1' : '0') + '" class="certificado"></td>' +
                '<td><button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button></td>' +
              '</tr>';

    $(trHtml).appendTo($tabla.find('tbody'));

    for (i = indexJump, max = jumpIndex; i <= max; i += 1)
      $.fn.keyJump.setElem($('.jump'+i));

    $('.jump'+(indexJump)).focus();
    $(".vpositive").numeric({ negative: false });
    $(".vnumeric").numeric();
  }
}


  objr.init = init;
  return objr;
})(jQuery);


