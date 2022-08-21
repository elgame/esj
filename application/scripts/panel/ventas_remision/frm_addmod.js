$(function(){

  $('#form').keyJump();
  $('#modal-seguro').keyJump();
  $('#modal-certificado51').keyJump();
  $('#modal-certificado52').keyJump();
  $('#modal-supcarga').keyJump();

  pasaGastosTabla();

  if ($("#did_empresa").val() == '2' || $("#did_empresa").val() == '7') {
    $("#modal-produc-marcar").modal('show');
  }

  $("#form").submit(function(){
    var result = validaProductosEspecials();
    console.log(result, $("#privAddDescripciones").length);
    if(result == false)
    {
      event.preventDefault();
      return false;
    }else if($("#privAddDescripciones").length == 0 && $("#isNotaCredito").length == 0)
    {
    // Valida agregar descripciones
      result = validaPrivDescripciones();
      if(result == false)
      {
        noty({"text": 'No tienes permiso para agregar Descripciones, Selecciona los productos que salen en el listado.', "layout":"topRight", "type": 'error'});
        event.preventDefault();
        return false;
      }
    }

  });

  $("#dcliente").autocomplete({
      source: function(request, response) {
          $.ajax({
              url: base_url+'panel/facturacion/ajax_get_clientes_vr/',
              dataType: "json",
              data: {
                  term : request.term,
                  did_empresa : $("#did_empresa").val()
              },
              success: function(data) {
                  response(data);
              }
          });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_cliente").val(ui.item.id);
        createInfoCliente(ui.item.item);
        $("#dcliente").css("background-color", "#B0FFB0");

        $('#dplazo_credito').val(ui.item.item.dias_credito);

        $('#dmetodo_pago').val(ui.item.item.metodo_pago);
        $('#dmetodo_pago_digitos').val(ui.item.item.ultimos_digitos);
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
      source: base_url+'panel/facturacion/ajax_get_empresas_fac/?all=1',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_empresa").val(ui.item.id);
        $("#dempresa").css("background-color", "#B0FFB0");

        $('#dversion').val(ui.item.item.cfdi_version);
        $('#dcer_caduca').val(ui.item.item.cer_caduca);

        $('#dno_certificado').val(ui.item.item.no_certificado);

        loadSerieFolio(ui.item.id, true, ui.item);

        // si es bodega boton imprimir
        $("#guardar_imp").hide();
        if (/bodega/i.test(ui.item.label))
          $("#guardar_imp").show();

        $("#modal-produc-marcar .mpromarcsel").removeAttr('checked');
        if (ui.item.id == '2' || ui.item.id == '7') {
          $("#modal-produc-marcar").modal('show');
        }

        // Borra cliente
        var e = jQuery.Event("keydown");
        e.which = 8; // # Some key code value
        $("#dcliente").trigger(e).val("");
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
        $('#serie-selected').val('void');
      }
  });

  autocompleteClasifi();
  autocompleteClasifiLive();

  autocompleteClaveUnidadLive();

  autocompleteCategoriasLive();

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

  $('#table_prod').on('keyup', '#prod_dcantidad, #prod_dpreciou, #dieps, #disr', function(e) {
    var key = e.which,
        $this = $(this),
        $tr = $this.parents('tr');

    if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
      calculaTotalProducto($tr);
    }
  }).on('change', '#prod_dcantidad, #prod_dpreciou, #dieps, #disr', function(e) {
    var $this = $(this),
        $tr = $this.parents('tr');

    calculaTotalProducto($tr);
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
            jsonStr = '';

        if (pallets.length > 0) {
          for (var i in pallets) {
            if (pallets[i].info.status === 't') {
              // console.log(jQuery.parseJSON(JSON.stringify(pallets[i].rendimientos)));

              disabled = '';
              bgcolor = '';
              $('.pallet-selected').each(function(index, el) {
                if ($(this).val() == pallets[i].info.id_pallet) {
                  disabled = 'disabled';
                  bgcolor = 'background-color: #FF9A9D;';
                }
              });

              jsonStr = JSON.stringify(pallets[i].rendimientos).replace(/\"/g,'&quot;');
              htmlTd += '<tr style="'+bgcolor+'">'+
                            '<th><input type="checkbox" value="'+pallets[i].info.id_pallet+'" class="chk-cli-pallets" '+disabled+'><input type="hidden" id="jsonData" value="'+jsonStr+'" ></th>'+
                            '<th>'+pallets[i].info.folio+'</th>'+
                            '<th>'+pallets[i].info.cajas+'</th>'+
                            '<th>'+pallets[i].info.fecha+'</th>'+
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
              'certificado': jsonObj[i]['certificado'],
            });
          }
        });

        $('#modal-pallets').modal('hide');
      }, 'json');
    } else {
      noty({"text": 'Seleccione al menos un pallet para agregarlo al listado.', "layout":"topRight", "type": 'error'});
    }
  });

  EventOnChangeMoneda();

  $('#modal-seguro, #modal-certificado51, #modal-certificado52').modal({
    backdrop: 'static',
    keyboard: false,
    show: false
  });

  autocompleteProveedores();
  enabledCloseModal('#modal-seguro');
  enabledCloseModal('#modal-certificado51');
  enabledCloseModal('#modal-certificado52');
  enabledCloseModal('#modal-supcarga');

  $('#table_prod').on('click', '.is-cert-check', function(event) {
    var $this = $(this),
        $td = $this.parent();

    if ($this.is(':checked')) {
      $td.find('.certificado').val('1');
    } else {
      $td.find('.certificado').val('0');
    }
  });

  // CALCULA TOTALES DE LA TABLA DE GASTOS
  $('#table_prod2').on('keyup', '#prod_dcantidad, #prod_dpreciou, #dieps', function(e) {
    var key = e.which,
        $this = $(this),
        $tr = $this.parents('tr');

    if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
      calculaTotalProducto($tr);
    }
  }).on('change', '#prod_dcantidad, #prod_dpreciou, #dieps, #diva, #dreten_iva', function(e) {
    var $this = $(this),
        $tr = $this.parents('tr');

    calculaTotalProducto($tr);
  });
});

var EventOnChangeMoneda = function () {
  $('#moneda').on('change', function(event) {
    if($(this).val() !== 'M.N.')
      $("#tipoCambio").show().focus();
    else
      $("#tipoCambio").val().hide();
  });
};

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
    if ($this.is(':checked')) {
      $("#dsincosto_novergrup").show();
    } else {
      $("#dsincosto_novergrup").hide();
      $("#dsincosto_nover").prop('checked', false);
    }

    recalculaCosto();
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
                    'certificado': data['rendimientos'][i]['certificado'],
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
      $cantidad.val( parseFloat($kilosInput.val()) * parseFloat($cajasInput.val()) );
    } else {
      $cantidad.val($cajasInput.val());
    }

    calculaTotalProducto($parent);

  });
};

function calculaTotalProducto ($tr, $calculaT) {
  $calculaT = $calculaT!=undefined? $calculaT: true;
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

      totalImporte   = trunc2Dec(parseFloat($cantidad.val() || 0) * parseFloat($precio_uni.val() || 0) ),
      // totalIva       = trunc2Dec(((totalImporte) * (parseFloat($iva.find('option:selected').val()) || 0) ) / 100),
      totalRetencion = trunc2Dec(totalImporte * parseFloat($retencion.find('option:selected').val())),
      totalIeps      = trunc2Dec(((totalImporte) * (parseFloat($ieps.val())||0) ) / 100),
      totalIsr       = trunc2Dec(((totalImporte) * (parseFloat($isr.val())||0) ) / 100)
      ;
      // totalRetencion = trunc2Dec(totalIva * parseFloat($retencion.find('option:selected').val()));
  console.log('iva con el ieps', $iepsSub.val());
  if($iepsSub.val() == 't') {
    totalIva = trunc2Dec(((totalImporte+totalIeps) * (parseFloat($iva.find('option:selected').val()) || 0) ) / 100)
  } else {
    totalIva = trunc2Dec(((totalImporte) * (parseFloat($iva.find('option:selected').val()) || 0) ) / 100);
  }

  $totalIva.val(totalIva);
  $totalIeps.val(totalIeps);
  $totalIsr.val(totalIsr);
  $totalRetencion.val(totalRetencion);
  $importe.val(totalImporte);

  if ($calculaT)
    calculaTotal($calculaT);
  // var importe   = trunc2Dec(parseFloat($('#dcantidad').val() * parseFloat($('#dpreciou').val()))),
  //     iva       = trunc2Dec(((importe - descuento) * parseFloat($('#diva option:selected').val())) / 100),
  //     retencion = trunc2Dec(iva * parseFloat($('#dreten_iva option:selected').val()));
}

function pasaGastosTabla () {
  // Pasa los gastos a la otra tabla
  $("#table_prod #prod_did_prod").each(function(index, el) {
    var $this = $(this), $tr = $this.parent().parent();
    // if ($this.val() == '49' || $this.val() == '50' || $this.val() == '51' || $this.val() == '52' || $this.val() == '53') {
    if ( searchGastosProductos($this.val()) ) {
      $tr.appendTo('#table_prod2 thead');
    }
  });
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
      ivaSelected = '0', prod_kilos = 0, cantidad = 0, prod_certificado = false,
      prod_dcalidad = '', prod_did_calidad = '', prod_dtamanio = '', prod_did_tamanio = '', prod_ddescripcion2 = '',
      prod_dtamanio_prod = '', prod_did_tamanio_prod = '',
      prod_ieps_subtotal = 'f';

  // Pasa los gastos a la otra tabla
  pasaGastosTabla();

  if (prod) {
    // Verificar si existe la clasificacion...
    var estaCertificado;
    $tabla.find('input#prod_did_prod').each(function(index, el) {
      var $prodIdInput = $(this), // input hidde prod id.
          $medidaInput; // input hidde medida.

      $tr = $prodIdInput.parents('tr'); // tr parent.
      $medidaInput = $tr.find('#prod_dmedida_id'); // input hidde medida.
      $idUnidadRendimiento = $tr.find('#id_unidad_rendimiento');
      estaCertificado = $tr.find('.is-cert-check').is(':checked') ? 't' : 'f';

      // console.log($prodIdInput.val(), prod.id, $medidaInput.val(), prod.id_unidad);
      if ($prodIdInput.val() == prod.id && $idUnidadRendimiento.val() == prod.id_unidad && estaCertificado === prod.certificado) {
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
    unidad      = prod.unidad ? prod.unidad : '';
    prod_nombre += ' ' + unidad; // le concatena la unidad del rendmiento al la descripcion.
    prod_ieps_subtotal = prod.ieps_subtotal;

    idUnidadClasificacion = prod.id_unidad_clasificacion ? prod.id_unidad_clasificacion : '';
    ivaSelected = prod.iva_clasificacion ? prod.iva_clasificacion : '';
    prod_certificado =  prod.certificado === 't' ? true : false;

    prod_dcalidad         = prod.areas_calidad;
    prod_did_calidad      = prod.id_calidad;
    prod_dtamanio         = prod.areas_tamanio;
    prod_did_tamanio      = prod.id_tamanio;
    prod_dtamanio_prod    = prod['areas_tamanio_prod']? prod.areas_tamanio_prod: '';
    prod_did_tamanio_prod = prod['id_tamanio_prod']? prod.id_tamanio_prod: '';
    prod_ddescripcion2    = prod.descripcion2;

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
                '<td style="width:31px;">' +
                  '<div class="btn-group">' +
                    '<button type="button" class="btn ventasmore">' +
                      '<span class="caret"></span>' +
                    '</button>' +
                    '<ul class="dropdown-menu ventasmore">' +
                      '<li class="clearfix">'+
                        '<label class="pull-left">Categoría:</label> <input type="text" name="prod_dcategoria[]" value="" id="prod_dcategoria" class="span9 gasto-cargo pull-right jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">'+
                        '<input type="hidden" name="prod_dcategoria_id[]" value="" id="prod_dcategoria_id" class="span12 gasto-cargo-id">'+
                      '</li>'+
                      '<li class="divider"></li>'+
                      '<li class="clearfix">' +
                        '<label class="pull-left">Calidad:</label> <input type="text" name="prod_dcalidad[]" value="'+prod_dcalidad+'" id="prod_dcalidad" class="span9 pull-right jump'+(++jumpIndex)+'" data-next="jump'+(++jumpIndex)+'">' +
                        '<input type="hidden" name="prod_did_calidad[]" value="'+prod_did_calidad+'" id="prod_did_calidad" class="span12">' +
                      '</li>' +
                      '<li class="divider"></li>' +
                      '<li class="clearfix">' +
                        '<label class="pull-left">Tamaño:</label> <input type="text" name="prod_dtamanio[]" value="'+prod_dtamanio+'" id="prod_dtamanio" class="span9 pull-right jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                        '<input type="hidden" name="prod_did_tamanio[]" value="'+prod_did_tamanio+'" id="prod_did_tamanio" class="span12">' +
                      '</li>' +
                      '<li class="divider"></li>' +
                      '<li class="clearfix">' +
                        '<label class="pull-left">TamañoProd</label> <input type="text" name="prod_dtamanio_prod[]" value="'+prod_dtamanio_prod+'" id="prod_dtamanio_prod" class="span9 pull-right jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                        '<input type="hidden" name="prod_did_tamanio_prod[]" value="'+prod_did_tamanio_prod+'" id="prod_did_tamanio_prod" class="span12">' +
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
                  '<input type="hidden" name="id_unidad_rendimiento[]" value="'+idUnidad+'" id="id_unidad_rendimiento" class="span12">' +
                  '<input type="hidden" name="prod_ieps_subtotal[]" value="'+prod_ieps_subtotal+'" id="prod_ieps_subtotal" class="span12">' +
                  // '<input type="hidden" name="id_size_rendimiento[]" value="'+idSize+'" id="id_size_rendimiento" class="span12">' +
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

                  '<input type="text" name="pclave_unidad[]" class="span12 jump'+jumpIndex+'" id="pclave_unidad" value="" placeholder="Clave de Unidad" data-next="jump'+(++jumpIndex)+'">'+
                  '<input type="hidden" name="pclave_unidad_cod[]" class="span9" id="pclave_unidad_cod" value="">'+
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
                      '<option value="8"'+(ivaSelected == '8' ? 'selected' : '')+'>8%</option>' +
                      '<option value="16"'+(ivaSelected == '16' ? 'selected' : '')+'>16%</option>' +
                      '<option value="exento"'+(ivaSelected == 'exento' ? 'selected' : '')+'>Exento</option>'+
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
                '<td>' +
                  '<div class="btn-group">' +
                    '<button type="button" class="btn impuestosEx">' +
                      '<span class="caret"></span>' +
                    '</button>' +
                    '<ul class="dropdown-menu impuestosEx">' +
                      '<li class="clearfix">' +
                        '<label class="pull-left">% IEPS:</label> <input type="number" name="dieps[]" value="0" id="dieps" max="100" min="0" class="span9 pull-right vpositive">' +
                        '<input type="hidden" name="dieps_total[]" value="0" id="dieps_total" class="span12">' +
                      '</li>' +
                      '<li class="clearfix">'+
                        '<label class="pull-left">% Ret ISR:</label> <input type="number" name="disr[]" value="" id="disr" max="100" min="0" class="span9 pull-right vpositive">'+
                        '<input type="hidden" name="disr_total[]" value="0" id="disr_total" class="span12">'+
                      '</li>'+
                    '</ul>' +
                  '</div>' +
                  '<button type="button" class="btn btn-danger" id="delProd">' +
                  '<i class="icon-remove"></i></button>' +
                '</td>' +
              '</tr>';

    $(trHtml).appendTo($tabla.find('tbody'));

    for (i = indexJump, max = jumpIndex; i <= max; i += 1)
      $.fn.keyJump.setElem($('.jump'+i));

    // openGroupMore('.jump'+indexJump);
    $('.jump'+(indexJump)).focus();
    $(".vpositive").numeric({ negative: false });
    $(".vnumeric").numeric();
  }
}

function recalculaCosto () {
  var isCheckedSinCosto = $('#dsincosto').is(':checked'),
  num_cantidad       = 0,
  total_repartir     = 0,
  repartir_costo     = 0;

  $('input#prod_did_prod').each(function(i, e) {
    var $this = $(this), $parent = $this.parent().parent(), idProd;
    idProd = $this.val();
    // if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
    if ( !searchGastosProductos(idProd) ) {
      num_cantidad += parseFloat($parent.find('#prod_dcantidad').val());
    } else {
      total_repartir += parseFloat($parent.find('#prod_importe').val()) +
                        parseFloat($parent.find('#prod_diva_total').val());
    }
  });
  // if(isCheckedSinCosto)
    repartir_costo = total_repartir / (num_cantidad>0? num_cantidad: 1);

  $('#table_prod input#prod_dpreciou').each(function(i, e) {
    var $this = $(this), $parent = $this.parent().parent(), idProd;
    if (parseFloat($this.val()) > 0) {
      idProd = $parent.find('#prod_did_prod').val();
      // if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
      if ( !searchGastosProductos(idProd) ) {
        $this.val( (parseFloat($this.val()) + (parseFloat(repartir_costo)*(isCheckedSinCosto? 1: -1))).toFixed(4) );
        calculaTotalProducto($parent, false);
      }
    }
  });
}

function calculaTotal ($calculaT) {
  $calculaT = $calculaT? $calculaT: true;

  var total_importes    = 0,
      total_descuentos  = 0,
      total_ivas        = 0,
      total_ieps        = 0,
      total_isr         = 0,
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
      // if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
      if ( !searchGastosProductos(idProd) ) {
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
      // if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
      if ( !searchGastosProductos(idProd) ) {
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
      // if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
      if ( !searchGastosProductos(idProd) ) {
        total_ivas += parseFloat($(this).val());
      }
    }
  });
  total_ivas = trunc2Dec(total_ivas);

  $('input#dieps_total').each(function(i, e) {
    var $parent = $(this).parent().parent(), idProd;
    if ( ! isCheckedSinCosto) {
      total_ieps += parseFloat($(this).val());
    } else {
      idProd = $parent.find('#prod_did_prod').val();
      // if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
      if ( !searchGastosProductos(idProd) ) {
        total_ieps += parseFloat($(this).val());
      }
    }
  });
  total_ieps = trunc2Dec((total_ieps||0));

  $('input#disr_total').each(function(i, e) {
    var $parent = $(this).parent().parent(), idProd;
    if ( ! isCheckedSinCosto) {
      total_isr += parseFloat($(this).val());
    } else {
      idProd = $parent.find('#prod_did_prod').val();
      // if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
      if ( !searchGastosProductos(idProd) ) {
        total_isr += parseFloat($(this).val());
      }
    }
  });
  total_isr = trunc2Dec((total_isr||0));

  $('input#prod_dreten_iva_total').each(function(i, e) {
    var $parent = $(this).parent().parent(), idProd;
    if ( ! isCheckedSinCosto) {
      total_retenciones += parseFloat($(this).val());
    } else {
      idProd = $parent.find('#prod_did_prod').val();
      // if (idProd != '49' && idProd != '50' && idProd != '51' && idProd != '52' && idProd != '53') {
      if ( !searchGastosProductos(idProd) ) {
        total_retenciones += parseFloat($(this).val());
      }
    }
  });
  total_retenciones = trunc2Dec(total_retenciones);

  total_factura = trunc2Dec(parseFloat(total_subtotal) + parseFloat(total_ivas) + parseFloat(total_ieps) - parseFloat(total_retenciones) - parseFloat(total_isr));

  $('#importe-format').html(util.darFormatoNum(total_importes));
  $('#total_importe').val(total_importes);

  $('#descuento-format').html(util.darFormatoNum(total_descuentos));
  $('#total_descuento').val(total_descuentos);

  $('#subtotal-format').html(util.darFormatoNum(total_subtotal));
  $('#total_subtotal').val(total_subtotal);

  $('#iva-format').html(util.darFormatoNum(total_ivas));
  $('#total_iva').val(total_ivas);

  $('#ieps-format').html(util.darFormatoNum(total_ieps));
  $('#total_ieps').val(total_ieps);

  $('#isr-format').html(util.darFormatoNum(total_isr));
  $('#total_isr').val(total_isr);

  $('#retiva-format').html(util.darFormatoNum(total_retenciones));
  $('#total_retiva').val(total_retenciones);

  $('#totfac-format').html(util.darFormatoNum(total_factura));
  $('#total_totfac').val(total_factura);

  $('#total_letra').val(util.numeroToLetra.covertirNumLetras(total_factura.toString(), $('#moneda').val()) );
}

function loadSerieFolio (ide, forceLoad) {
  var objselect = $('#dserie');

  var url = 'panel/facturacion/get_series/?tipof=r&ide=';
  if ($('#isNotaCredito').length > 0) {
    url = 'panel/notas_credito/ajax_get_series_folio/?ide=';
  }

  loader.create();
    $.getJSON(base_url+url+ide,
      function(res){
        if(res.data) {
          var html_option = '<option value="void"></option>',
              selected = '', serieSelected = 'void',
              loadDefault = false;

          for (var i in res.data){
            selected = '';
            if ($('#serie-selected').val() !== '') {
              if (res.data[i].serie === $('#serie-selected').val()) {
                selected = 'selected';
                serieSelected = res.data[i].serie;
                $("#dcliente").focus();
              }
            } else {
              if (res.data[i].default_serie === 't') {
                loadDefault = true;
                selected = 'selected';
                serieSelected = res.data[i].serie;
                $("#dcliente").focus();
              } else if (res.data[i].serie === 'R') {
                loadDefault = true;
                selected = 'selected';
                serieSelected = res.data[i].serie;
                $("#dcliente").focus();
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
  $.getJSON(base_url+'panel/ventas/get_folio/?serie='+serie+'&ide='+ide,
  function(res){
    if(res.msg == 'ok'){

      // console.log(res);

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
      $tr.find('#prod_ieps_subtotal').val(ui.item.item.ieps_subtotal);

      $tr.find('#prod_dmedida').find('[data-id="'+ui.item.item.id_unidad+'"]').attr('selected', 'selected');
      $tr.find('#prod_dmedida_id').val(ui.item.item.id_unidad);
      $tr.find('#diva').val(ui.item.item.iva).trigger('change');

      loadModalSegCert(ui.item.item.id_clasificacion);

      setTimeout(function(){
        let parts = $this.val().split(' - ');
        $this.val((parts.length > 1? parts[0]: $this.val()));
      }, 300);
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
        $tr.find('#prod_dmedida_id').val(ui.item.item.id_unidad);
        $tr.find('#diva').val(ui.item.item.iva).trigger('change');

        loadModalSegCert(ui.item.item.id_clasificacion);

        setTimeout(function(){
          let parts = $this.val().split(' - ');
          $this.val((parts.length > 1? parts[0]: $this.val()));
        }, 300);
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

function autocompleteClaveUnidadLive () {
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

var autocompleteCategoriasLive = function () {
  $('body').on('focus', '.gasto-cargo:not(.ui-autocomplete-input)', function(event) {
    $(this).autocomplete({
      source: base_url+'panel/caja_chica/ajax_get_categorias/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $(this).parents('tr').find(".gasto-cargo-id").val(ui.item.id);
        $(this).css("background-color", "#B0FFB0");
      }
    }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $(this).parents('tr').find(".gasto-cargo-id").val("");
        $(this).val("").css("background-color", "#FFD9B3");
      }
    });
  });
};

function valida_agregar ($tr) {
  // $tr.find("#prod_did_prod").val() === '' ||
  if($("#privAddDescripciones").length == 0 && $("#isNotaCredito").length == 0)
  {
    if ($tr.find("#prod_did_calidad").val() === '' || $tr.find("#prod_did_tamanio").val() == '' ||
      $tr.find("#pclave_unidad").val() == '') {
      return false;
    }
  // Valida agregar descripciones
    result = validaPrivDescripciones();
    if(result == false)
    {
      noty({"text": 'No tienes permiso para agregar Descripciones, Selecciona los productos que salen en el listado.', "layout":"topRight", "type": 'error'});
      event.preventDefault();
      return false;
    }
  }

  if ($tr.find("#prod_dmedida").val() === '' || $tr.find("#prod_dcantidad").val() == 0 ||
      $tr.find("#prod_dpreciou").val() < 0) {
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


var loadModalSegCert = function (idClasificacion) {
  // Si la clasificacion es el seguro muestra el seguro para agregar
  // sus datos.
  if (idClasificacion === '49') {
    $('#modal-seguro').modal('show');
    $("#pproveedor_seguro").focus();
  }

  // Si la clasificacion es el supervisor de carga abre modal
  if (idClasificacion === '53') {
    $('#modal-supcarga').modal('show');
  }

  // Si la clasificacion es el certificado de origin o fitosanitario.
  // muestra el modal para agregar sus datos.
  if (idClasificacion === '51' || idClasificacion === '52') {
    $('#modal-certificado'+idClasificacion).modal('show');
    $("#pproveedor_certificado"+idClasificacion).focus();
  }
};

// Autocomplete Proveedor
var autocompleteProveedores = function () {
  $('#form input.pproveedor_seguro').each(function () {
    $(this).one('focus', setAutocompleteProveedores);
  })
  $('#form input.pproveedor_certificado51').each(function () {
    $(this).one('focus', setAutocompleteProveedores);
  })
  $('#form input.pproveedor_certificado52').each(function () {
    $(this).one('focus', setAutocompleteProveedores);
  })
  $('#form input.pproveedor_supcarga').each(function () {
    $(this).one('focus', setAutocompleteProveedores);
  })
};
var setAutocompleteProveedores = function (event) {
  $(this).autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val(), 10) > 0)
        params.did_empresa = $("#did_empresa").val();
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
      var $this = $(this);
      $this.val(ui.item.label).css({'background-color': '#99FF99'}),
      grup_datos = $this.parents('.grup_datos');

      if ($this[0].id === 'pproveedor_seguro') {
        grup_datos.find("#seg_id_proveedor").val(ui.item.id).trigger('keyup');
      }else if ($this[0].id === 'pproveedor_supcarga') {
        grup_datos.find("#supcarga_id_proveedor").val(ui.item.id).trigger('keyup');
      } else {
        grup_datos.find('#cert_id_proveedor'+$this.attr('id').replace('pproveedor_certificado', '')).val(ui.item.id).trigger('keyup');
      }
    }
  }).keydown(function(e){
    if (e.which === 8) {
      var $this = $(this),
      grup_datos = $this.parents('.grup_datos');

      $this.css({'background-color': '#FFD9B3'});

      if ($this[0].id === 'pproveedor_seguro') {
        grup_datos.find('#seg_id_proveedor').val('');
      }else if ($this[0].id === 'pproveedor_supcarga') {
        grup_datos.find("#supcarga_id_proveedor").val('').trigger('keyup');
      } else {
        grup_datos.find('#cert_id_proveedor'+$this.attr('id').replace('pproveedor_certificado', '')).val('');
      }
    }
  });
};

// Verifica si los campos del modal estan todos llenos
// si es true entonces habilita el boton para cerrarlo.
var enabledCloseModal = function (idModal) {
  var $modal = $(idModal),
      $fields = $modal.find('.field-check');

  $fields.keyup(function(event) {
    var close = true;

    $fields.each(function(index, el) {
      if ($(this).val() === '') {
        close = false;
      }
    });

    if (close) {
      $modal.find('#btnClose').prop('disabled', '');
    } else {
      $modal.find('#btnClose').prop('disabled', 'disabled');
    }
  });
};

var validaProductosEspecials = function() {
  var result = true, prods_required = {};

  $("#modal-produc-marcar .mpromarcsel:checked").each(function(){
    prods_required['d'+$(this).val()] = false;
  });

  $("#table_prod #prod_did_prod").each(function(index, el) {
    if ($(this).val() === '49' && $('#seg_id_proveedor').val() == '') {
      //Seguro
      noty({"text": 'Seguro incompleto, no se ha capturado los datos de proveedor, seleccione nuevamente el concepto.', "layout":"topRight", "type": 'error'});
      result = false;
    }else if (($(this).val() === '51' && $('#cert_id_proveedor51').val() == "") || ($(this).val() === '52' && $('#cert_id_proveedor52').val() == "")) {
      //certificados
      noty({"text": 'Certificado incompleto, no se ha capturado los datos de proveedor, seleccione nuevamente el concepto.', "layout":"topRight", "type": 'error'});
      result = false;
    }else if ($(this).val() === '53' && $('#supcarga_id_proveedor').val() == "") {
      //supervisor carga
      noty({"text": 'Supervisor de carga incompleto, no se ha capturado los datos de proveedor, seleccione nuevamente el concepto.', "layout":"topRight", "type": 'error'});
      result = false;
    }

    if (prods_required['d'+$(this).val()] != undefined) {
      prods_required['d'+$(this).val()] = true;
    }

  });

  for (var i in prods_required) {
    if (prods_required[i] == false) {
      noty({"text": getMsgDatos(i), "layout":"topRight", "type": 'error'});
      result = false;
    }
  }

  return result;
};

var getMsgDatos = function(id){
  var msgs = {
    'd49': 'El Seguro no esta agregado en los productos.',
    'd50': 'El Flete no esta agregado en los productos.',
    'd51': 'El Certificado fitosanitario no esta agregado en los productos.',
    'd52': 'El Certificado de origen no esta agregado en los productos.',
    'd53': 'El Supervisor de carga no esta agregado en los productos.',
  };
  return msgs[id];
};

var validaPrivDescripciones = function() {
  var result = true;

  $("#table_prod #prod_did_prod").each(function(index, el) {
    var $td = $(this).parent();
    if ($(this).val() === '' && $.trim($td.find('#prod_ddescripcion').val()) != '') {
      result = false;
    }
  });
  return result;
};