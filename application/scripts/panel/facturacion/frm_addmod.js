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

        loadSerieFolio(ui.item.id, true);
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
        $('#serie-selected').val('');
      }
  });

  autocompleteClasifi();
  autocompleteClasifiLive();

  if ($('#did_empresa').val() !== '') {
    loadSerieFolio($('#did_empresa').val());
  }

  //Carga el folio para la serie seleccionada
  $("#dserie").on('change', function(){
    var $serie = $(this),
        $empresa = $('#did_empresa');

    loadFolioAjax($serie.val(), $empresa.val(), true);

    // loader.create();
    // $.getJSON(base_url+'panel/facturacion/get_folio/?serie='+$(this).val()+'&ide='+$('#did_empresa').val(),
    // function(res){
    //   if(res.msg == 'ok'){
    //     $("#dfolio").val(res.data.folio);
    //     $("#dno_aprobacion").val(res.data.no_aprobacion);
    //     $("#dano_aprobacion").val(res.data.ano_aprobacion);
    //     $("#dimg_cbb").val(res.data.imagen);
    //   }else{
    //     $("#dfolio").val('');
    //     $("#dno_aprobacion").val('');
    //     $("#dano_aprobacion").val('');
    //     $("#dimg_cbb").val('');
    //     noty({"text":res.msg, "layout":"topRight", "type":res.ico});
    //   }
    //   loader.close();
    // });
  });

  // $('#addProducto').on('click', function(event) {
  //   if (!valida_agregar())
  //     alert('Los campos de arriba son necesarios.');
  //   else
  //     addProducto();
  // });

  // Elimina un prod del listado
  $(document).on('click', 'button#delProd', function(e) {
      var $this = $(this),
          $tr = $this.parent().parent(),
          palletsClasifi = $tr.attr('data-pallets'), // los pallets que tienen la clasificacion que se esta eliminando.
          $table = $('#table_prod');

      $tr.remove(); // elimina el tr padre.

      // Si palletsClasifi no es vacio, significa que se se esta eliminando
      // una clasificacion que se agrego de pallets.
      palletsClasifi = palletsClasifi.split('-');

      // auxiliar que indica si existe otra clasificacion con el mismo pallet,
      // este sirve para evitar eliminar el input que contiene el id del pallet.
      var existe = false;

      if (palletsClasifi !== '') {
        for (var i in palletsClasifi) {
          $table.find('tbody tr').each(function(index, el) {
            var $this = $(this);
            if ($this.attr('data-pallets') !== '') {
              var auxPallets = $this.attr('data-pallets').split('-');
              for (var ii in auxPallets) {
                if (auxPallets[ii] == palletsClasifi[i]) {
                  existe = true;
                }
              }
            }
          });

          // si no existe otra clasificacion que pertenesca al mismo pallet de la
          // que se esta eliminando, entonces elimina el input.
          if ( ! existe) {
            $('#pallet' + palletsClasifi[i]).remove();
          }

          existe = false;
        }
      }
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
          $.get(base_url + 'panel/facturacion/ajax_get_unidades', function(unidades) {
            addProducto(unidades);
          }, 'json');
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

    calculaTotalProducto ($tr);
  });

  loadPalletByFolio();

  EventKeyPressFolioPallet();
  EventOnClickSinCosto();
  EventOnChangeMedida();

  $('#show-pallets').on('click', function(event) {
    var $this = $(this), // boton
        $clienteId = $('#did_cliente'); // Input id del cliente.

    if ($clienteId.val() !== '') {
      $.ajax({
        url: base_url + 'panel/facturacion/ajax_get_pallets_cliente',
        type: 'GET',
        dataType: 'json',
        data: {id: $clienteId.val()},
      })
      .done(function(pallets) {
        // console.log(pallets);
        var $tablePalletsCliente = $('#table-pallets-cliente'),
            htmlTd = '',
            disabled = '',
            bgcolor = '',
            jsonStr = '',rnd_txt;

        if (pallets.length > 0) {
          for (var i in pallets) {
            if (pallets[i].info.status === 't') {
              // console.log(jQuery.parseJSON(JSON.stringify(pallets[i].rendimientos)));

              rnd_txt = '';
              disabled = '';
              bgcolor = '';
              $('.pallet-selected').each(function(index, el) {
                if ($(this).val() == pallets[i].info.id_pallet) {
                  disabled = 'disabled';
                  bgcolor = 'background-color: #FF9A9D;';
                }
              });

              for (var rnd in pallets[i].rendimientos) {
                rnd_txt += pallets[i].rendimientos[rnd].nombre+';'+pallets[i].rendimientos[rnd].size+' | ';
              };

              jsonStr = JSON.stringify(pallets[i].rendimientos).replace(/\"/g,'&quot;');
              htmlTd += '<tr style="'+bgcolor+'">'+
                            '<td><input type="checkbox" value="'+pallets[i].info.id_pallet+'" class="chk-cli-pallets" '+disabled+'><input type="hidden" id="jsonData" value="'+jsonStr+'" ></td>'+
                            '<td>'+pallets[i].info.folio+'</td>'+
                            '<td>'+pallets[i].info.cajas+'</td>'+
                            '<td>'+pallets[i].info.fecha+'</td>'+
                            '<td>'+rnd_txt+'</td>'+
                          '</tr>';
            }
          }

          $tablePalletsCliente.find('tbody').html(htmlTd);
        }

      })
      .fail(function() {
        noty({"text": 'Ocurrio un error vuelva a intentarlo', "layout":"topRight", "type": 'error'});
      });

      $('#modal-pallets').modal('show');
    } else {
      noty({"text": 'Seleccione un cliente para mostrar sus pallets disponibles', "layout":"topRight", "type": 'error'});
    }
  });

  $('#BtnAddClientePallets').on('click', function(event) {
    if ($('.chk-cli-pallets:checked').length > 0) {
      $.get(base_url + 'panel/facturacion/ajax_get_unidades', function(unidades) {
        $('.chk-cli-pallets:checked').each(function(index, el) {
          var $chkPallet = $(this);
              $parent = $chkPallet.parent(),
              jsonObj = jQuery.parseJSON($parent.find('#jsonData').val());

          $('#pallets-selected').append('<input type="hidden" value="' + $chkPallet.val() + '" name="palletsIds[]" class="pallet-selected" id="pallet' + $chkPallet.val() + '">');

          for (var i in jsonObj) {
            addProducto(unidades, {
              'id': jsonObj[i]['id_clasificacion'],
              'nombre': jsonObj[i]['nombre'],
              'cajas': jsonObj[i]['cajas'],
              'id_pallet': $chkPallet.val(),
              'id_unidad': jsonObj[i]['id_unidad'],
              'unidad': jsonObj[i]['unidad'],
              'id_unidad_clasificacion': jsonObj[i]['id_unidad_clasificacion'],
              'iva_clasificacion': jsonObj[i]['iva_clasificacion'],
              'kilos': jsonObj[i]['kilos'],
            });
          }
        });

        $('#modal-pallets').modal('hide');
      }, 'json');
    } else {
      noty({"text": 'Seleccione al menos un pallet para agregarlo al listado.', "layout":"topRight", "type": 'error'});
    }
  });

});

var EventKeyPressFolioPallet = function () {
  $('#folioPallet').on('keypress', function(event) {
    if (event.which === 13) {
      event.preventDefault();
      $('#loadPallet').trigger('click');
    }
  });
};

var EventOnClickSinCosto = function () {
  $('#dsincosto').on('click', function(event) {
    var $this = $(this);

    calculaTotal();
  });
};

function loadPalletByFolio() {
  $('#loadPallet').on('click', function(event) {
    var $folio = $('#folioPallet');

    if ($folio.val() !== '') {

      $.get(base_url + 'panel/facturacion/ajax_get_pallet_folio/?folio='+$folio.val(), function(data) {
        if (data) {
          // console.log(data);
          var existe = false;
          $('.pallet-selected').each(function(index, el) {
            if ($(this).val() == data['info']['id_pallet']) {
              existe = true;
              return false;
            }
          });

          // Verifica si el pallet ya esta cargado en el listado.
          if ( ! existe) {

            if (data['rendimientos'].length > 0) {
              $.get(base_url + 'panel/facturacion/ajax_get_unidades', function(unidades) {

                $('#pallets-selected').append('<input type="hidden" value="' + data['info']['id_pallet'] + '" name="palletsIds[]" class="pallet-selected" id="pallet' + data['info']['id_pallet'] + '">');

                for(var i in data['rendimientos']) {
                  addProducto(unidades, {
                    'id': data['rendimientos'][i]['id_clasificacion'],
                    'nombre': data['rendimientos'][i]['nombre'],
                    'cajas': data['rendimientos'][i]['cajas'],
                    'id_pallet': data['info']['id_pallet'],
                    'id_unidad': data['rendimientos'][i]['id_unidad'],
                    'unidad': data['rendimientos'][i]['unidad'],
                    'id_unidad_clasificacion': data['rendimientos'][i]['id_unidad_clasificacion'],
                    'iva_clasificacion': data['rendimientos'][i]['iva_clasificacion'],
                    'kilos': data['rendimientos'][i]['kilos'],
                  });
                }
              }, 'json');
            } else {
              noty({"text": 'El pallet no cuenta con cajas para agregar.', "layout":"topRight", "type": 'error'});
            }
            $folio.val('');
          } else {
            noty({"text": 'El pallet ya esta cargado en el listado o ya existe en una factura.', "layout":"topRight", "type": 'error'});
          }
        } else {
          noty({"text": 'No existe un pallet con el folio especificado.', "layout":"topRight", "type": 'error'});
        }
      }, 'json');
    } else {
      noty({"text": 'Especifique el folio de un Pallet', "layout":"topRight", "type": 'error'});
    }
  });
}

var EventOnChangeMedida = function () {
  $('#table_prod').on('change', 'select#prod_dmedida', function(event) {
    var $select = $(this),
        $parent = $select.parents('tr'),
        $medidaId = $parent.find('#prod_dmedida_id'),
        $cantidad = $parent.find('#prod_dcantidad'),
        $kilosInput = $parent.find('#prod_dkilos'),
        $cajasInput = $parent.find('#prod_dcajas');

    $medidaId.val($select.find('option:selected').attr('data-id'));

    // Si el id de medida es el 19 de los kilos entonces en el input de cantidad
    // carga el valor del input oculto de los kilos, si es cualquier otra
    // medida entonces carga las cajas.
    if ($medidaId.val() == '19') {
      $cantidad.val($kilosInput.val());
    } else {
      $cantidad.val($cajasInput.val());
    }

    calculaTotalProducto($parent);

  });
};

function calculaTotalProducto ($tr) {
  var $cantidad   = $tr.find('#prod_dcantidad'),
      $precio_uni = $tr.find('#prod_dpreciou'),
      $iva        = $tr.find('#diva'),
      $retencion  = $tr.find('#dreten_iva'),
      $importe    = $tr.find('#prod_importe'),

      $totalIva       = $tr.find('#prod_diva_total'),
      $totalRetencion = $tr.find('#prod_dreten_iva_total'),

      totalImporte   = trunc2Dec(parseFloat(($cantidad.val() || 0)) * parseFloat($precio_uni.val() || 0)),
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

  var prod_nombre = '', prod_id = '', pallet = '', prod_cajas = 0,
      ivaSelected = '0', prod_kilos = 0, cantidad = 0;

  if (prod) {
    // Verificar si existe la clasificacion...
    $tabla.find('input#prod_did_prod').each(function(index, el) {
      var $prodIdInput = $(this), // input hidde prod id.
          $medidaInput; // input hidde medida.

      $tr = $prodIdInput.parents('tr'); // tr parent.
      $medidaInput = $tr.find('#prod_dmedida_id'); // input hidde medida.
      $idUnidadRendimiento = $tr.find('#id_unidad_rendimiento');

      // console.log($prodIdInput.val(), prod.id, $medidaInput.val(), prod.id_unidad);
      if ($prodIdInput.val() == prod.id && $idUnidadRendimiento.val() == prod.id_unidad) {
        existe = true;
        return false;
      }
    });

    prod_nombre = prod.nombre;
    prod_id     = prod.id;
    prod_cajas  = prod.cajas;
    prod_kilos  = prod.kilos;
    pallet      = prod.id_pallet;
    idUnidad    = prod.id_unidad ? prod.id_unidad : ''; // id_unidad del rendimiento.
    unidad      = prod.unidad ? prod.unidad : ''; // nombre de la unidad del rendimiento.
    prod_nombre += ' ' + unidad; // le concatena la unidad del rendmiento a la descripcion.

    idUnidadClasificacion = prod.id_unidad_clasificacion ? prod.id_unidad_clasificacion : '';
    ivaSelected = prod.iva_clasificacion ? prod.iva_clasificacion : '';
  } else {
    idUnidad = unidades[0].id_unidad;
    unidad = unidades[0].nombre;
    idUnidadClasificacion = unidades[0].id_unidad;
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

    trHtml = '<tr data-pallets="'+pallet+'">' +
                '<td>' +
                  '<input type="text" name="prod_ddescripcion[]" value="'+prod_nombre+'" id="prod_ddescripcion" class="span12 jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">' +
                  '<input type="hidden" name="prod_did_prod[]" value="'+prod_id+'" id="prod_did_prod" class="span12">' +
                  '<input type="hidden" name="pallets_id[]" value="'+pallet+'" id="pallets_id" class="span12">' +
                  '<input type="hidden" name="id_unidad_rendimiento[]" value="'+idUnidad+'" id="id_unidad_rendimiento" class="span12">' +
                '</td>' +
                '<td>' +
                  '<select name="prod_dmedida[]" id="prod_dmedida" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                    unidadesHtml +
                    // '<option value="Pieza">Pieza</option>' +
                    // '<option value="Caja">Caja</option>' +
                    // '<option value="Kilos">Kilos</option>' +
                    // '<option value="No aplica">No aplica</option>' +
                  '</select>' +
                  '<input type="hidden" name="prod_dmedida_id[]" value="'+idUnidadClasificacion+'" id="prod_dmedida_id" class="span12 vpositive">' +
                '</td>' +
                '<td>' +
                    '<input type="text" name="prod_dcantidad[]" value="'+cantidad+'" id="prod_dcantidad" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                    '<input type="hidden" name="prod_dkilos[]" value="'+prod_kilos+'" id="prod_dkilos" class="span12 vpositive">' +
                    '<input type="hidden" name="prod_dcajas[]" value="'+prod_cajas+'" id="prod_dcajas" class="span12 vpositive">' +
                '</td>' +
                '<td>' +
                  '<input type="text" name="prod_dpreciou[]" value="0" id="prod_dpreciou" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                '</td>' +
                '<td>' +
                    '<select name="diva" id="diva" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                      '<option value="0"'+(ivaSelected == '0' ? 'selected' : '')+'>0%</option>' +
                      '<option value="11"'+(ivaSelected == '11' ? 'selected' : '')+'>11%</option>' +
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
                '<td><button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button></td>' +
              '</tr>';


    $(trHtml).appendTo($tabla.find('tbody'));

    for (i = indexJump, max = jumpIndex; i <= max; i += 1)
      $.fn.keyJump.setElem($('.jump'+i));

    $('.jump'+indexJump).focus();
  }
}

function calculaTotal () {
  var total_importes    = 0,
      total_descuentos  = 0,
      total_ivas        = 0,
      total_retenciones = 0,
      total_factura     = 0;

  var $sinCosto = $('#dsincosto'),
      isCheckedSinCosto = $sinCosto.is(':checked');

  $('input#prod_importe').each(function(i, e) {
    var $parent = $(this).parent().parent(), idProd;
    if ( ! isCheckedSinCosto) {
      total_importes += parseFloat($(this).val());
    } else {
      idProd = $parent.find('#prod_did_prod').val();
      if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
        total_importes += parseFloat($(this).val());
      }
    }
  });
  total_importes = trunc2Dec(total_importes);

  $('input#prod_ddescuento').each(function(i, e) {
    var $parent = $(this).parent().parent(), idProd;
    if ( ! isCheckedSinCosto) {
      total_descuentos += parseFloat($(this).val());
    } else {
      idProd = $parent.find('#prod_did_prod').val();
      if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
        total_descuentos += parseFloat($(this).val());
      }
    }
  });
  total_descuentos = trunc2Dec(total_descuentos);

  var total_subtotal = trunc2Dec(parseFloat(total_importes) - parseFloat(total_descuentos));

  $('input#prod_diva_total').each(function(i, e) {
    var $parent = $(this).parent().parent(), idProd;
    if ( ! isCheckedSinCosto) {
      total_ivas += parseFloat($(this).val());
    } else {
      idProd = $parent.find('#prod_did_prod').val();
      if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
        total_ivas += parseFloat($(this).val());
      }
    }
  });

  $('input#prod_dreten_iva_total').each(function(i, e) {
    var $parent = $(this).parent().parent(), idProd;
    if ( ! isCheckedSinCosto) {
      total_retenciones += parseFloat($(this).val());
    } else {
      idProd = $parent.find('#prod_did_prod').val();
      if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
        total_retenciones += parseFloat($(this).val());
      }
    }
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

  $('#total_letra').val(util.numeroToLetra.covertirNumLetras(total_factura.toString()));
}

function loadSerieFolio (ide, forceLoad) {
  var objselect = $('#dserie');
  loader.create();
    $.getJSON(base_url+'panel/facturacion/get_series/?ide='+ide,
      function(res){
        if(res.msg === 'ok') {
          var html_option = '<option value="void"></option>',
              selected = '', serieSelected = 'void',
              loadDefault = false;

          for (var i in res.data){
            selected = '';
            if ($('#serie-selected').val() !== 'void') {
              if (res.data[i].serie === $('#serie-selected').val()) {
                selected = 'selected';
                serieSelected = res.data[i].serie;
              }
            } else {
              if (res.data[i].serie === '') {
                loadDefault = true;
                selected = 'selected';
                serieSelected = res.data[i].serie;
              }
            }

            html_option += '<option value="'+res.data[i].serie+'" '+selected+'>'+res.data[i].serie+' - '+(res.data[i].leyenda || '')+'</option>';
          }
          objselect.html(html_option);

          if (serieSelected !== 'void' || forceLoad) {
            loadFolioAjax(serieSelected, ide, forceLoad);
          } else {
            // if ($('#serie-selected').val() === 'void') {
              // $("#dfolio").val("");
              // $("#dno_aprobacion").val("");
            // }
          }
        } else {
          noty({"text":res.msg, "layout":"topRight", "type":res.ico});
        }
        loader.close();
      });
}

// Carga el folio siguiente de la empresa y serie seleccionadas.
function loadFolioAjax(serie, ide, forceLoad) {
  loader.create();
  $.getJSON(base_url+'panel/facturacion/get_folio/?serie='+serie+'&ide='+ide,
  function(res){
    if(res.msg == 'ok'){

      if ($('#dfolio').val() === '' || forceLoad) {
        $("#dfolio").val(res.data.folio);
      }

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

      $tr.find('#prod_dmedida').find('[data-id="'+ui.item.item.id_unidad+'"]').attr('selected', 'selected');
      $tr.find('#prod_dmedida_id').val(ui.item.item.id_unidad);
      $tr.find('#diva').val(ui.item.item.iva).trigger('change');
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

        $tr.find('#prod_dmedida').find('[data-id="'+ui.item.item.id_unidad+'"]').attr('selected', 'selected');
        $tr.find('#diva').val(ui.item.item.iva).trigger('change');
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

function trunc2Dec(num, digits) {
  digits = digits? digits: 2;
  var result = Math.round(num*Math.pow(10,digits))/Math.pow(10,digits);
  return result;
  
  var numS = num.toString(),
      decPos = numS.indexOf('.'),
      result;
  if(decPos > -1)
    result = numS.substr(0, 1 + decPos + digits);
  else
    result = numS;

  if (isNaN(result)) {
    result = 0;
  }

  return parseFloat(result);
  // return Math.floor(num * 100) / 100;
}

function round2Dec(val) {
  return Math.round(val * 100) / 100;
}