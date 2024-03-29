(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();

    autocompleteEmpresas();
    autocompleteProveedores();
    autocompleteAutorizo();
    // autocompleteCodigo();
    autocompleteConcepto();
    autocompleteClientes();
    autocompleteCultivo();
    autocompleteRanchos();
    autocompleteCentroCosto();
    autocompleteActivos();

    if ($('.editando').length == 0) {
      getProyectos();
    }
    getSucursales();

    eventOtros();

    eventCodigoBarras();
    eventBtnAddProducto();
    eventBtnListaOtros();
    eventBtnListaActivos();
    eventTipoCambioKeypress();
    eventKeyUpCantPrecio();
    eventOnChangeTraslado();
    eventBtnDelProducto();
    eventCheckboxProducto();
    eventOnChangePresentacionTable();
    eventOnChangeTipoOrden();
    //Ligar ordenes
    eventOnChangeCondicionPago();
    eventOnChangeMetodoPago();

    eventTipoMonedaChange();

    eventLigarFacturas();
    eventLigarBoletas();
    eventLigarCompras();
    eventLigarSalidasAlmacen();
    eventLigarGastosCaja();

    btnAutorizarClick();

    if($("#form-modif").length > 0)
    {
      $('#productos #table-productos .bodyproducs tr.rowprod').each(function() {
        calculaTotalProducto($(this));
      });

      // calculaTotal(1);
      // calculaTotal(2);
      // calculaTotal(3);
    }

    // Autocomplete para los Vehiculos.
    $("#vehiculo").autocomplete({
      source: base_url + 'panel/vehiculos/ajax_get_vehiculos/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $vehiculo =  $(this);

        $vehiculo.val(ui.item.id);
        $("#vehiculoId").val(ui.item.id);
        $vehiculo.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#vehiculo").css("background-color", "#FFD071");
        $("#vehiculoId").val('');
      }
    });

    $("#es_vehiculo").on('change', function(event) {
      var $this = $(this);
      if($this.is(":checked")){
        $("#groupVehiculo").show();
        if($("#tipo_vehiculo").val() != 'ot')
          $("#group_gasolina").show();
      }else{
        $("#groupVehiculo").hide();
        $("#group_gasolina").hide();
      }
    });

    $("#tipo_vehiculo").on('change', function(event) {
      var $this = $(this);
      if($this.val() !== 'ot')
        $("#group_gasolina").show();
      else
        $("#group_gasolina").hide();
    });
  });


  var btnAutorizarClick = function(){
    $("#btnAutorizar").on('click', function(e) {
      var passes = true;
      // $(".prodSelOrden:checked").each(function(index, el) {
      //   if (($(this).val() != $('#proveedorId1').val() &&
      //       $(this).val() != $('#proveedorId2').val() &&
      //       $(this).val() != $('#proveedorId3').val()) || $(this).val() == '') {
      //     passes = false;
      //     noty({"text": 'Esta seleccionado un producto sin proveedor asignado', "layout":"topRight", "type": 'error'});
      //   }
      // });

      $(".prodIdOrden").each(function(index, el) {
        if ($(this).val() == '' || $(this).val() == '0') {
          passes = false;
          noty({"text": 'Hay nuevos productos, guarde la orden y despues la autoriza', "layout":"topRight", "type": 'error'});
        }
      });

      if($("#autorizoId").val() == ''){
        noty({"text": 'Tiene que seleccionar la persona que autoriza', "layout":"topRight", "type": 'error'});
        passes = false;
        $("#autorizo").focus();
      }


      // Envia el form para autorizar
      if (passes){
        msb.confirm('Estas seguro de Autorizar y crear la(s) ordenes?', 'Ordenes de Compras', this, function(){
          $("#txtBtnAutorizar").val('true');
          setTimeout(function(){ $("#form").submit(); }, 100);
        });
      }
    });
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

   // Obtiene el siguiente folio segun el tipo de orden.
  var tipoOrderActual = $('#tipoOrden').find('option:selected').val();
  var eventOnChangeTipoOrden = function () {
    $('#tipoOrden').on('change', function(event) {
      var $this      = $(this),
          $folio     = $('#folio'),
          $tableProd = $('#table-productos');

      if ($tableProd.find('tbody tr').length > 0) {
        noty({"text": 'Ya tiene productos para un tipo de orden, si desea cambiar de tipo de orden elimine los productos del listado', "layout":"topRight", "type": 'error'});

        $this.val(tipoOrderActual);
      } else {
        $.get(base_url + 'panel/compras_requisicion/ajax_get_folio/?tipo=' + $this.find('option:selected').val(), function(folio) {
          $folio.val(folio);
          tipoOrderActual = $this.find('option:selected').val();
          if(tipoOrderActual == 'f') {
            $("#grpFleteDe").show();
          } else {
            $("#fletesFactura").hide();
            $("#grpFleteDe").hide();
          }
          $("#fleteDe").change();

          if(tipoOrderActual == 'd'){
            $("#verVehiculoChk").show();
            $("#serCompras").show();
            $("#serSalidasAlmacen").show();
            $("#serGastosCaja").show();
            $("#serComprasProvee").show();
          }
          else {
            $("#verVehiculoChk").hide();
            $("#serCompras").hide();
            $("#serSalidasAlmacen").hide();
            $("#serGastosCaja").hide();
            $("#serComprasProvee").hide();
          }

          if(tipoOrderActual == 'oc' || tipoOrderActual == 'd') {
            $('.classProyecto').show();
          } else {
            $('.classProyecto').hide();
          }


          if (tipoOrderActual == 'p') {
            $('.grpes_receta').show();
          } else {
            $('.grpes_receta').hide();
            $('#es_receta').attr("checked", false);
          }

          $("#area, #areaId, #rancho, #ranchoId, #centroCosto, #centroCostoId, #activos, #activoId").val("");
          if (tipoOrderActual != 'a') {
            $('#groupCatalogos').show();
            $('#ranchosGrup, #centrosCostosGrup, #activosGrup, #cultivosGrup').show();

            if (tipoOrderActual == 'f') {
              $('#ranchosGrup, #centrosCostosGrup, #activosGrup').hide();
            }
          } else {
            $('#groupCatalogos').hide();
          }
        });
      }
    });

    $('#fleteDe').on('change', function(event) {
      var $this = $(this),
          de = $this.find('option:selected').val();
      if (de == 'v') {
        $("#fletesFactura").show();
        $("#fletesBoletas").hide();
        $("#boletasLigada").html('');
      } else {
        $("#fletesBoletas").show();
        $("#fletesFactura").hide();
        $("#facturaLigada").html('');
      }
    });
  };

  /*
   |------------------------------------------------------------------------
   | Autocompletes
   |------------------------------------------------------------------------
   */

  // Autocomplete para las empresas.
  var autocompleteEmpresas = function () {
    $("#empresa").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $empresa =  $(this);

        $empresa.val(ui.item.id);
        $("#empresaId").val(ui.item.id);
        $empresa.css("background-color", "#A1F57A");
        $('#productos #table-productos tbody.bodyproducs tr').remove();
        $("#proveedor1, #proveedor2, #proveedor3").val("");
        $("#proveedorId1, #proveedorId2, #proveedorId3").val("");
        $("#area, #areaId, #rancho, #ranchoId, #centroCosto, #centroCostoId, #activos, #activoId").val("").css("background-color", "#A1F57A");
        getProyectos();
        getSucursales();
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#empresa").css("background-color", "#FFD071");
        $("#empresaId").val('');
        $("#area, #areaId, #rancho, #ranchoId, #centroCosto, #centroCostoId, #activos, #activoId").val("").css("background-color", "#A1F57A");
        getProyectos();
        getSucursales();
      }
    });

    $("#empresaAp").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $empresaAp =  $(this);

        $empresaAp.val(ui.item.id);
        $("#empresaApId").val(ui.item.id);
        $empresaAp.css("background-color", "#A1F57A");
        $("#area, #areaId, #rancho, #ranchoId, #centroCosto, #centroCostoId, #activos, #activoId").val("").css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#empresaAp").css("background-color", "#FFD071");
        $("#empresaApId").val('');
        $("#area, #areaId, #rancho, #ranchoId, #centroCosto, #centroCostoId, #activos, #activoId").val("").css("background-color", "#A1F57A");
      }
    });

    $("#serEmpresaSA").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $empresa =  $(this);

        $empresa.val(ui.item.id);
        $("#serEmpresaSAId").val(ui.item.id);
        $empresa.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#serEmpresaSA").css("background-color", "#FFD071");
        $("#serEmpresaSAId").val('');
      }
    });

    $("#serEmpresaGC").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $empresa =  $(this);

        $empresa.val(ui.item.id);
        $("#serEmpresaGCId").val(ui.item.id);
        $empresa.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#serEmpresaGC").css("background-color", "#FFD071");
        $("#serEmpresaGCId").val('');
      }
    });
  };

  // Autocomplete para los Proveedores.
  var autocompleteProveedores = function () {
    $("#fproveedor").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
        $.ajax({
            url: base_url + 'panel/proveedores/ajax_get_proveedores/',
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
        var $proveedor =  $(this);

        $("#fproveedorId").val(ui.item.id);
        $proveedor.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        var $proveedor =  $(this);
        $proveedor.css("background-color", "#FFD071");
        $("#fproveedorId").val('');
      }
    });

    $("#serProveedor").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
        $.ajax({
            url: base_url + 'panel/proveedores/ajax_get_proveedores/',
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
        var $proveedor =  $(this);

        $("#serProveedorId").val(ui.item.id);
        $proveedor.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        var $proveedor =  $(this);
        $proveedor.css("background-color", "#FFD071");
        $("#serProveedorId").val('');
      }
    });
  };

  // Autocomplete para los Clientes.
  var autocompleteClientes = function () {
    $("#cliente").autocomplete({
      source: base_url + 'panel/clientes/ajax_get_proveedores/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $cliente =  $(this);

        $cliente.val(ui.item.id);
        $("#clienteId").val(ui.item.id);
        $cliente.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#cliente").css("background-color", "#FFD071");
        $("#clienteId").val('');
      }
    });
  };

  var autocompleteAutorizo= function () {
    $("#autorizo").autocomplete({
      source: base_url + 'panel/usuarios/ajax_get_usuarios/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $autorizo =  $(this);

        $autorizo.css("background-color", "#A1F57A");
        $("#autorizoId").val(ui.item.id);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {$("#autorizo").css("background-color", "#FFD071");
        $("#autorizoId").val('');
      }
    });
  };

  var autocompleteCultivo = function () {
    $("#area").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaApId").val()) > 0)
          params.did_empresa = $("#empresaApId").val();
        $.ajax({
            url: base_url + 'panel/areas/ajax_get_areas/',
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
        var $area =  $(this);

        $area.val(ui.item.id);
        $("#areaId").val(ui.item.id);
        $area.css("background-color", "#A1F57A");

        $("#rancho").val('').css("background-color", "#FFD071");
        $('#tagsRanchoIds').html('');
        // $("#ranchoId").val('');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#area").css("background-color", "#FFD071");
        $("#areaId").val('');
        $("#rancho").val('').css("background-color", "#FFD071");
        $('#tagsRanchoIds').html('');
        // $("#ranchoId").val('');
      }
    });
  };

  var autocompleteRanchos = function () {
    $("#rancho").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaApId").val()) > 0)
          params.did_empresa = $("#empresaApId").val();
        if(parseInt($("#areaId").val()) > 0)
          params.area = $("#areaId").val();
        $.ajax({
            url: base_url + 'panel/ranchos/ajax_get_ranchos/',
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
        var $rancho =  $(this);

        addRanchoTag(ui.item);
        setTimeout(function () {
          $rancho.val('');
        }, 200);
        // $rancho.val(ui.item.id);
        // $("#ranchoId").val(ui.item.id);
        // $rancho.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#rancho").css("background-color", "#FFD071");
        // $("#ranchoId").val('');
      }
    });

    function addRanchoTag(item) {
      if ($('#tagsRanchoIds .ranchoId[value="'+item.id+'"]').length === 0) {
        $('#tagsRanchoIds').append('<li><span class="tag">'+item.value+'</span>'+
          '<input type="hidden" name="ranchoId[]" class="ranchoId" value="'+item.id+'">'+
          '<input type="hidden" name="ranchoText[]" class="ranchoText" value="'+item.value+'">'+
          '</li>');
      } else {
        noty({"text": 'Ya esta agregada el Areas, Ranchos o Lineas.', "layout":"topRight", "type": 'error'});
      }
    };

    $('#tagsRanchoIds').on('click', 'li:not(.disable)', function(event) {
      $(this).remove();
    });
  };

  var autocompleteCentroCosto = function () {
    $("#centroCosto").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};

        params.tipo = ['gasto'];
        if ($('#tipoOrden').find('option:selected').val() == 'd') {
          params.tipo = ['servicio'];
        } else if ($('#tipoOrden').find('option:selected').val() == 'p') {
          params.tipo = ['gasto', 'melga', 'tabla', 'seccion'];
        }

        $.ajax({
            url: base_url + 'panel/centro_costo/ajax_get_centro_costo/',
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
        var $centroCosto =  $(this);

        addCCTag(ui.item);
        setTimeout(function () {
          $centroCosto.val('');
        }, 200);
        // $centroCosto.val(ui.item.id);
        // $("#centroCostoId").val(ui.item.id);
        // $centroCosto.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#centroCosto").css("background-color", "#FFD071");
        // $("#centroCostoId").val('');
      }
    });

    function addCCTag(item) {
      if ($('#tagsCCIds .centroCostoId[value="'+item.id+'"]').length === 0) {
        $('#tagsCCIds').append('<li><span class="tag">'+item.value+'</span>'+
          '<input type="hidden" name="centroCostoId[]" class="centroCostoId" value="'+item.id+'">'+
          '<input type="hidden" name="centroCostoText[]" class="centroCostoText" value="'+item.value+'">'+
          '</li>');
      } else {
        noty({"text": 'Ya esta agregada el Centro de costo.', "layout":"topRight", "type": 'error'});
      }
    };

    $('#tagsCCIds').on('click', 'li:not(.disable)', function(event) {
      $(this).remove();
    });
  };

  var autocompleteActivos = function () {
    $("#table-productos").on('focus', 'input.clsActivos:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: function(request, response) {
          var params = {term : request.term};
          // if(parseInt($("#empresaApId").val()) > 0)
          //   params.did_empresa = $("#empresaApId").val();
          params.tipo = 'a'; // activos
          $.ajax({
              url: base_url + 'panel/productos/ajax_aut_productos/',
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
          var $activos =  $(this),
          $parent = $activos.parents('.popover-content');

          addActivoTag(ui.item, $parent);
          setTimeout(function () {
            $activos.val('');
          }, 200);
        }
      }).css('z-index', 1011).on("keydown", function(event) {
        if(event.which == 8 || event.which == 46) {
          $(this).css("background-color", "#FFD071");
        }
      });
    });

    function addActivoTag(item, $parent) {
      var jsonn = [];
      try {
        jsonn = JSON.parse($('.activosP', $parent).val());
      } catch (e) {
        jsonn = {};
      }

      if (!jsonn[item.id]) {
        $('.tagsActivosIds', $parent).append('<li data-id="'+item.id+'"><span class="tag">'+item.value+'</span>'+
          '</li>');

        jsonn[item.id] = { id: item.id, text: item.value };
        $('.activosP', $parent).val(JSON.stringify(jsonn));
      } else {
        noty({"text": 'Ya esta agregada el Activo a ese producto.', "layout":"topRight", "type": 'error'});
      }
    };

    $('#table-productos').on('click', '.tagsActivosIds li:not(.disable)', function(event) {
      var id = $(this).attr('data-id'),
        $parent = $(this).parents('.popover-content');
      var jsonn = [];
      try {
        jsonn = JSON.parse($('.activosP', $parent).val().replace(/”/g, '"'));
      } catch (e) {
        jsonn = {};
      }
      delete jsonn[id];
      $('.activosP', $parent).val(JSON.stringify(jsonn));
      $(this).remove();
    });
  };

  // Autocomplete para el codigo.
  var autocompleteCodigo = function () {
    $("#productos #fcodigo").autocomplete({
      source: function (request, response) {
        if (isEmpresaSelected()) {
          $.ajax({
            url: base_url + 'panel/compras_ordenes/ajax_producto_by_codigo/',
            dataType: 'json',
            data: {
              term : request.term,
              ide: $('#empresaId').val(),
              tipo: ($('#tipoOrden').find('option:selected').val()=='oc'? 'd': $('#tipoOrden').find('option:selected').val()),
            },
            success: function (data) {
              response(data);
            }
          });
        } else {
          noty({"text": 'Seleccione una empresa para mostrar sus productos.', "layout":"topRight", "type": 'error'});
        }
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {

        var $fcodigo    = $(this),
            idval = $fcodigo.parents("div[id^=productos]").attr('id').replace("productos", ""),
            $fconcepto     = $('#productos #fconcepto'),
            $fconceptoId   = $('#productos #fconceptoId'),
            $fcantidad     = $('#productos #fcantidad'),
            $fprecio       = $('#productos #fprecio'),
            $fpresentacion = $('#productos #fpresentacion'),
            $funidad       = $('#productos #funidad'),
            $ftraslado     = $('#productos #ftraslado');

        var presentaciones = ui.item.item.presentaciones,
            selectHtml = '<select name="presentacion[]" id="presentacion"><option value=""></option>';

        if (ui.item.item.presentaciones.length > 0) {
          for(var i in presentaciones) {
            selectHtml += '<option value="'+presentaciones[i].id_presentacion+'" data-cantidad="'+presentaciones[i].cantidad+'">'+presentaciones[i].nombre+' '+presentaciones[i].cantidad+' '+ui.item.item.unidad_abreviatura+'</option>';
          }
        }
        selectHtml += '</select>';

        producto = {
          'codigo': ui.item.codigo,
          'concepto': ui.item.item.nombre,
          'id': ui.item.id,
          'cantidad': '1',
          'precio_unitario': '0',
          'presentacion': selectHtml,
          // 'presentacionId': $fpresentacion.find('option:selected').val() || '',
          'presentacionCantidad': '',
          'unidad': ui.item.item.id_unidad,
          'traslado': '0',
        };

        addProducto(producto, idval);

        $fcodigo.val('');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $(this).css("background-color", "#FDFC9A");
        var idval = $(this).parents("div[id^=productos]").attr('id').replace("productos", "");
        $("#productos #fconcepto").val("");
        $('#productos #fconceptoId').val('');
        $('#productos #fcantidad').val('');
        $('#productos #fprecio').val('');
        $('#productos #funidad').val('');
        $('#productos #ftraslado').val('');
        $('#productos #fpresentacion').html('');
      }
    });
  };

  // Autocomplete para el codigo.
  var autocompleteConcepto = function () {
    $("#productos #fconcepto").autocomplete({
      source: function (request, response) {
        if (isEmpresaSelected()) {
          $.ajax({
            url: base_url + 'panel/compras_ordenes/ajax_producto/',
            dataType: 'json',
            data: {
              term : request.term,
              ide: $('#empresaId').val(),
              tipo: ($('#tipoOrden').find('option:selected').val()=='oc'? 'd': $('#tipoOrden').find('option:selected').val()),
            },
            success: function (data) {
              response(data);
            }
          });
        } else {
          noty({"text": 'Seleccione una empresa para mostrar sus productos.', "layout":"topRight", "type": 'error'});
        }
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $fconcepto    = $(this),
            idval = $fconcepto.parents("div[id^=productos]").attr('id').replace("productos", ""),
            $fcodigo     = $('#productos #fcodigo'),
            $fconceptoId   = $('#productos #fconceptoId'),
            $fcantidad     = $('#productos #fcantidad'),
            $fprecio       = $('#productos #fprecio'),
            $fpresentacion = $('#productos #fpresentacion'),
            $funidad       = $('#productos #funidad'),
            $fieps         = $('#productos #fieps'),
            $ftraslado     = $('#productos #ftraslado');


        $fconcepto.css("background-color", "#B6E7FF");
        $fcodigo.val(ui.item.item.codigo);
        $fconceptoId.val(ui.item.id);
        $fcantidad.val('1');
        $fprecio.val(ui.item.item.precio_unitario);
        $funidad.val(ui.item.item.id_unidad);
        $fieps.val(ui.item.item.ieps);

        if (ui.item.item.inventario) {
          // var entradas = parseFloat(ui.item.item.inventario.entradas),
          // salidas = parseFloat(ui.item.item.inventario.salidas),
          // saldo_anterior = parseFloat(ui.item.item.inventario.saldo_anterior);
          // existencias = saldo_anterior+entradas-salidas;
          existencias = ui.item.item.inventario;
          $("#productos #show_info_prod").show().find('span').text('Existencia: '+util.darFormatoNum(existencias, '')+' | Stock min: '+util.darFormatoNum(ui.item.item.stock_min, ''));
        }

        var presentaciones = ui.item.item.presentaciones,
            html = '<option value=""></option>';

        if (ui.item.item.presentaciones.length > 0) {
          for(var i in presentaciones) {
            html += '<option value="'+presentaciones[i].id_presentacion+'" data-cantidad="'+presentaciones[i].cantidad+'">'+presentaciones[i].nombre+' '+presentaciones[i].cantidad+' '+ui.item.item.unidad_abreviatura+'</option>';
          }
        }
         $fpresentacion.html(html);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        var $fconcepto = $(this),
        idval = $fconcepto.parents("div[id^=productos]").attr('id').replace("productos", "");

        $(this).css("background-color", "#FDFC9A");
        $("#productos #fcodigo").val("");
        $('#productos #fconceptoId').val('');
        $('#productos #fcantidad').val('');
        $('#productos #fprecio').val('');
        $('#productos #funidad').val('');
        $('#productos #ftraslado').val('');
        $('#productos #fpresentacion').html('');
        $("#productos #show_info_prod").show().find('span').text('');
      }
    });
  };

  /*
   |------------------------------------------------------------------------
   | Events
   |------------------------------------------------------------------------
   */
  var eventOtros = function () {
    $('#es_receta').on('change', function(event) {
      var checked = $(this).is(':checked');
      $('#no_recetas').attr('readonly', !checked).val('');
    });
  };

  var eventCodigoBarras = function () {
    $('#productos #fcodigo').on('keypress', function(event) {
      var $codigo = $(this);
      if (isEmpresaSelected()) {
        if (event.which === 13 && $codigo.val() !== '') {
          var idval = $codigo.parents("div[id^=productos]").attr('id').replace("productos", "");
          $.get(base_url + 'panel/compras_ordenes/ajax_producto_by_codigo/?ide=' + $('#empresaId').val() + '&cod=' + $codigo.val() + '&tipo=' + $('#tipoOrden').find('option:selected').val(), function(data) {
            if (data.length > 0) {
              var presentaciones = data[0].presentaciones,
                  selectHtml = '<select name="presentacion[]" id="presentacion"><option value=""></option>';

              if (presentaciones.length > 0) {
                for(var i in presentaciones) {
                  selectHtml += '<option value="'+presentaciones[i].id_presentacion+'" data-cantidad="'+presentaciones[i].cantidad+'">'+presentaciones[i].nombre+' '+presentaciones[i].cantidad+' '+data[0].unidad_abreviatura+'</option>';
                }
              }
              selectHtml += '</select>';

              producto = {
                'codigo': data[0].codigo,
                'concepto': data[0].nombre,
                'id': data[0].id_producto,
                'cantidad': '1',
                'precio_unitario': '0',
                'presentacion': selectHtml,
                'presentacionCantidad': '',
                'unidad': data[0].id_unidad,
                'traslado': '0',
              };

              addProducto(producto, idval);

              $codigo.val('');
            } else {
              noty({"text": 'No se encontro el codigo.', "layout":"topRight", "type": 'error'});
            }
          }, 'json');
        }
      } else {
        noty({"text": 'Favor de Seleccionar una empresa.', "layout":"topRight", "type": 'error'});
      }
    });
  };

  var eventTipoCambioKeypress = function () {
    $('#productos #fproveedor').on('keypress', function(event) {
      if (event.which === 13) {
        var idval = $(this).parents("div[id^=productos]").attr('id').replace("productos", "");

        $('#productos #btnAddProd').click();
      }
    });
  };

  var eventTipoMonedaChange = function () {
    $("#ftipo_moneda").on('change', function(event) {
      // Se obtiene el tipo de cambio de Banxico
      if ($(this).val() == 'dolar') {
        $.ajax({
          url      : base_url+"panel/compras_requisicion/ajax_get_tipo_cambio/",
          dataType : 'json',
          success  : function (data) {
            console.log(data);
            $("#ftipo_cambio").val(parseFloat(data));
          }
        });
      }else
        $("#ftipo_cambio").val('');
    });
  };

  var eventLigarFacturas = function () {
    $("#show-facturas").on('click', function(event) {
      $(".filTipoFacturas").removeAttr('checked');
      $(".filTipoFacturas:first").attr('checked', 'checked');
      $("#filFolio").val("");

      getFacturasRem();
      $("#modal-facturas").modal('show');
    });

    $(".filTipoFacturas").on('change', function(event) {
      getFacturasRem($(this).val());
    });
    $("#filFolio").on('change', function(event) {
      getFacturasRem($(".filTipoFacturas:checked").val());
    });
    $("#BtnAddFactura").on('click', function(event) {
      var selected = $(".radioFactura:checked"), facts = '', folios = '';
      selected.each(function(index, el) {
        var $this = $(this);
        facts += $this.attr("data-tipo")+':'+$this.val()+'|';
        folios += $this.attr("data-folio")+' | ';
      });

      $("#facturaLigada").html(folios+' <input type="hidden" name="remfacs" value="'+facts+'"><input type="hidden" name="remfacs_folio" value="'+folios+'">');
      $("#modal-facturas").modal('hide');
    });
    $("#facturaLigada").on('click', function(event) {
      $(this).html("");
    });
  };

  var getFacturasRem = function(tipo){
    if($("#clienteId").val() !== '')
    {
      var params = {
        clienteId: $("#clienteId").val(),
        tipo: (tipo? tipo: 'f'),
        filtro: $("#filFolio").val()
      };
      $.getJSON(base_url+"panel/compras_ordenes/ajaxGetFactRem/", params, function(json, textStatus) {
        var html = '';
        for (var i in json) {
          html += '<tr>'+
          '  <td><input type="checkbox" name="radioFactura" value="'+json[i].id_factura+'" class="radioFactura" data-tipo="'+json[i].is_factura+'" data-folio="'+json[i].serie+json[i].folio+'"></td>'+
          '  <td>'+json[i].fecha+'</td>'+
          '  <td>'+json[i].serie+json[i].folio+'</td>'+
          '  <td>'+json[i].cliente+'</td>'+
          '</tr>';
        }
        $("#table-facturas tbody").html(html);
      });
    }else
      noty({"text": 'Selecciona un cliente', "layout":"topRight", "type": 'error'});
  };

  var eventLigarCompras = function () {
    $("#show-compras").on('click', function(event) {
      $("#filFolioCompras").val("");

      getCompras();
      $("#modal-compras").modal('show');
    });

    $("#filFolioCompras, #serProveedor").on('change', function(event) {
      getCompras();
    });

    $("#BtnAddCompra").on('click', function(event) {
      var selected = $(".radioCompra:checked"), facts = ($('#comprasLigada input[name="compras"]').val()||''),
        folios = ($('#comprasLigada input[name="compras_folio"]').val()||'');
      selected.each(function(index, el) {
        var $this = $(this);
        facts += $this.val()+'|';
        folios += $this.attr("data-folio")+' | ';
      });

      $("#comprasLigada").html(folios+' <input type="hidden" name="compras" value="'+facts+'"><input type="hidden" name="compras_folio" value="'+folios+'">');
      $("#modal-compras").modal('hide');
    });
    $("#comprasLigada").on('click', function(event) {
      $(this).html("");
    });
  };

  var eventLigarSalidasAlmacen = function () {
    $("#show-salidasAlmacen").on('click', function(event) {
      $("#filFolioSalidasAlmacen").val("");

      $('#serEmpresaSA').val($('#empresa').val());
      $('#serEmpresaSAId').val($('#empresaId').val());
      getSalidasAlmacen();
      $("#modal-salidas-almacen").modal('show');
    });

    $("#filFolioSalidasAlmacen, #serEmpresaSA").on('change', function(event) {
      getSalidasAlmacen();
    });

    $("#BtnAddSalidaAlmacen").on('click', function(event) {
      var selected = $(".radioSalidaAlmacen:checked"), facts = ($('#salidasAlmacenLigada input[name="salidasAlmacen"]').val()||''),
        folios = ($('#salidasAlmacenLigada input[name="salidasAlmacen_folio"]').val()||'');
      selected.each(function(index, el) {
        var $this = $(this);
        facts += $this.val()+'|';
        folios += $this.attr("data-folio")+' | ';
      });

      $("#salidasAlmacenLigada").html(folios+' <input type="hidden" name="salidasAlmacen" value="'+facts+'"><input type="hidden" name="salidasAlmacen_folio" value="'+folios+'">');
      $("#modal-salidas-almacen").modal('hide');
    });
    $("#salidasAlmacenLigada").on('click', function(event) {
      $(this).html("");
    });
  };

  var eventLigarGastosCaja = function () {
    $("#show-gastosCaja").on('click', function(event) {
      $("#filFolioGastosCaja, #filCajaGastosCaja").val("");

      $('#serEmpresaGC').val($('#empresa').val());
      $('#serEmpresaGCId').val($('#empresaId').val());
      getGastosCaja();
      $("#modal-gastos-caja").modal('show');
    });

    $("#filFolioGastosCaja, #serEmpresaGC, #filCajaGastosCaja").on('change', function(event) {
      getGastosCaja();
    });

    $("#BtnAddGastosCaja").on('click', function(event) {
      var selected = $(".radioGastosCaja:checked"), facts = ($('#gastosCajaLigada input[name="gastosCaja"]').val()||''),
        folios = ($('#gastosCajaLigada input[name="gastosCaja_folio"]').val()||'');
      selected.each(function(index, el) {
        var $this = $(this);
        facts += $this.val()+'|';
        folios += $this.attr("data-folio")+' | ';
      });

      $("#gastosCajaLigada").html(folios+' <input type="hidden" name="gastosCaja" value="'+facts+'"><input type="hidden" name="gastosCaja_folio" value="'+folios+'">');
      $("#modal-gastos-caja").modal('hide');
    });
    $("#gastosCajaLigada").on('click', function(event) {
      $(this).html("");
    });
  };

  var getCompras = function(tipo){
    // if($("#serProveedorId").val() !== '')
    // {
      var params = {
        empresaId: $("#empresaId").val(),
        proveedorId: $("#serProveedorId").val(),
        filtro: $("#filFolioCompras").val()
      };
      $.getJSON(base_url+"panel/compras_ordenes/ajaxGetCompras/", params, function(json, textStatus) {
        var html = '';
        for (var i in json) {
          html += '<tr>'+
          '  <td><input type="checkbox" name="radioCompra" value="'+json[i].id_compra+'" class="radioCompra" data-folio="'+json[i].serie+json[i].folio+'"></td>'+
          '  <td>'+json[i].fecha+'</td>'+
          '  <td>'+json[i].serie+json[i].folio+'</td>'+
          '  <td>'+json[i].proveedor+'</td>'+
          '</tr>';
        }
        $("#table-facturas tbody").html(html);
      });
    // }else
    //   noty({"text": 'Selecciona un proveedor', "layout":"topRight", "type": 'error'});
  };

  var getSalidasAlmacen = function(tipo){
    if($("#serEmpresaSAId").val() !== '')
    {
      var params = {
        empresaId: $("#serEmpresaSAId").val(),
        filtro: $("#filFolioSalidasAlmacen").val()
      };
      $.getJSON(base_url+"panel/compras_ordenes/ajaxGetSalidasAlmacen/", params, function(json, textStatus) {
        var html = '';
        for (var i in json) {
          html += '<tr>'+
          '  <td><input type="checkbox" name="radioSalidaAlmacen" value="'+json[i].id_salida+'" class="radioSalidaAlmacen" data-folio="'+json[i].folio+'"></td>'+
          '  <td>'+json[i].fecha+'</td>'+
          '  <td>'+json[i].folio+'</td>'+
          '  <td>'+json[i].empresa+'</td>'+
          '  <td>'+json[i].concepto+'</td>'+
          '</tr>';
        }
        $("#table-salidas-almacen tbody").html(html);
      });
    }else
      noty({"text": 'Selecciona una empresa', "layout":"topRight", "type": 'error'});
  };

  var getGastosCaja = function(tipo){
    if($("#serEmpresaGCId").val() !== '')
    {
      var params = {
        empresaId: $("#serEmpresaGCId").val(),
        caja: $('#filCajaGastosCaja').val(),
        filtro: $("#filFolioGastosCaja").val()
      };
      $.getJSON(base_url+"panel/compras_ordenes/ajaxGetGastosCaja/", params, function(json, textStatus) {
        var html = '';
        for (var i in json) {
          html += '<tr>'+
          '  <td><input type="checkbox" name="radioGastosCaja" value="'+json[i].id_gasto+'" class="radioGastosCaja" data-folio="'+json[i].folio_sig+'"></td>'+
          '  <td>'+json[i].fecha+'</td>'+
          '  <td>'+json[i].folio_sig+'</td>'+
          '  <td>'+json[i].empresa+'</td>'+
          '  <td>'+json[i].no_caja+'</td>'+
          '  <td>'+json[i].monto+'</td>'+
          '</tr>';
        }
        $("#table-gastos-caja tbody").html(html);
      });
    }else
      noty({"text": 'Selecciona una empresa', "layout":"topRight", "type": 'error'});
  };

  var eventLigarBoletas = function () {
    $("#show-boletas").on('click', function(event) {
      // $(".filTipoFacturas").removeAttr('checked');
      // $(".filTipoFacturas:first").attr('checked', 'checked');
      $("#filBoleta").val("");

      getBoletas();
      $("#modal-boletas").modal('show');
    });

    // $(".filTipoFacturas").on('change', function(event) {
    //   getBoletas($(this).val());
    // });
    $("#filBoleta").on('change', function(event) {
      getBoletas();
    });
    $("#BtnAddBoleta").on('click', function(event) {
      var selected = $(".radioBoleta:checked"), facts = '', folios = '';
      selected.each(function(index, el) {
        var $this = $(this);
        facts += $this.val()+'|';
        folios += $this.attr("data-folio")+' | ';
      });

      $("#boletasLigada").html(folios+' <input type="hidden" name="boletas" value="'+facts+'"><input type="hidden" name="boletas_folio" value="'+folios+'">');
      $("#modal-boletas").modal('hide');
    });
    $("#boletasLigada").on('click', function(event) {
      $(this).html("");
    });
  };

  var getBoletas = function(tipo){
    var params = {
      // clienteId: $("#clienteId").val(),
      // tipo: (tipo? tipo: 'f'),
      filtro: $("#filBoleta").val()
    };
    $.getJSON(base_url+"panel/compras_ordenes/ajaxGetBoletas/", params, function(json, textStatus) {
      var html = '';
      for (var i in json) {
        html += '<tr>'+
        '  <td><input type="checkbox" name="radioBoleta" value="'+json[i].id_bascula+'" class="radioBoleta" data-folio="'+json[i].folio+'"></td>'+
        '  <td>'+json[i].fecha+'</td>'+
        '  <td>'+json[i].folio+'</td>'+
        '  <td>'+json[i].proveedor+'</td>'+
        '</tr>';
      }
      $("#table-boletas tbody").html(html);
    });
    // if($("#clienteId").val() !== '')
    // {
    // }else
    //   noty({"text": 'Selecciona un cliente', "layout":"topRight", "type": 'error'});
  };

  var eventBtnAddProducto = function () {
    $('#productos #btnAddProd').on('click', function(event) {
      var idval = $(this).parents("div[id^=productos]").attr('id').replace("productos", ""),
          $fcodigo       = $('#productos #fcodigo').css({'background-color': '#FFF'}),
          $fconcepto     = $('#productos #fconcepto').css({'background-color': '#FFF'}),
          $fconceptoId   = $('#productos #fconceptoId'),
          $fcantidad     = $('#productos #fcantidad').css({'background-color': '#FFF'}),
          $fprecio       = $('#productos #fprecio').css({'background-color': '#FFF'}),
          $fpresentacion = $('#productos #fpresentacion'),
          $funidad       = $('#productos #funidad'),
          $fieps         = $('#productos #fieps'),
          $ftipo_moneda  = $('#productos #ftipo_moneda'),
          $ftipo_cambio  = $('#productos #ftipo_cambio'),
          $ftraslado     = $('#productos #ftraslado'),
          $fretencionIva = $('#productos #fretencionIva'),
          $fIsrPercent   = $('#productos #fIsrPercent'),
          $fproveedor    = $('#productos #fproveedor'),
          $fproveedorId  = $('#productos #fproveedorId'),

          campos = [$fcantidad, $fprecio],
          producto = {},
          error = false;

      // Recorre los campos para verificar si alguno esta vacio. Si existen
      // campos vacios entonces los pinta de amarillo y manda una alerta.
      for (var i in campos) {
        if (campos[i].val() === '') {
          campos[i].css({'background-color': '#FDFC9A'});
          error = true;
        } else {
          campos[i].css({'background-color': '#FFF'});
        }
      }

      // Si el tipo de orden es producto entonces verifica si se selecciono
      // un producto, si no no deja agregar descripciones.
      // if ($('#tipoOrden').find('option:selected').val() === 'p') {
        if ($fconceptoId.val() === '') {
          $fconcepto.css({'background-color': '#FDFC9A'});
          error = true;
        }
      // }

      // Valida si el campo cantida es 0.
      if ($fcantidad.val() === '0') {
        $fcantidad.css({'background-color': '#FDFC9A'});
        error = true;
      }

      // Valida el tipo de cambio
      $ftipo_cambio.css({'background-color': '#FFF'});
      if ($ftipo_moneda.val() === 'dolar' && $ftipo_cambio.val() === '') {
        $ftipo_cambio.css({'background-color': '#FDFC9A'});
        error = true;
      }

      // Valida el proveedor
      if ($fproveedorId.val() == '') {
        $fproveedor.css({'background-color': '#FDFC9A'})
        error = true;
      }

      // Si no hubo un error, es decir que no halla faltado algun campo de
      // completar.

      var selectHtml = '<select name="presentacion[]" id="presentacion">',
          selected = $fpresentacion.find('option:selected').val(),
          existOpt = false;

      $fpresentacion.find('option').each(function(index, el) {
        selectHtml += '<option value="'+$(this).val()+'" data-cantidad="'+($(this).attr('data-cantidad') || '')+'" '+($(this).val() == selected ? 'selected' : '')+'>'+$(this).text()+'</option>';
        existOpt = true;
      });

      if ( ! existOpt) {
        selectHtml += '<option value="" data-cantidad=""></option>';
      }

      selectHtml += '</select>';

      if ( ! error) {
        producto = {
          'codigo': $fcodigo.val(),
          'concepto': $fconcepto.val(),
          'id': $fconceptoId.val(),
          'cantidad': $fcantidad.val(),
          'precio_unitario': $fprecio.val(),
          'presentacion': selectHtml,
          'presentacionCantidad': $fpresentacion.find('option:selected').attr('data-cantidad') || '',
          'unidad': $funidad.find('option:selected').val(),
          'ieps': $fieps.val(),
          'traslado': $ftraslado.find('option:selected').val(),
          'tipo_moneda': $ftipo_moneda.val(),
          'tipo_cambio': $ftipo_cambio.val(),
          'ret_iva': $fretencionIva.find('option:selected').val(),
          'ret_isr': $fIsrPercent.val(),
          'proveedor_id': $fproveedorId.val(),
          'proveedor': $fproveedor.val(),
        };

        addProducto(producto, idval);

        // Recorre los campos para limpiarlos.
        for (var i in campos) {
          campos[i].val('').css({'background-color': '#FFF'});
        }

        $fconcepto.val('').css({'background-color': '#FFF'}).focus();
        $fconceptoId.val('').css({'background-color': '#FFF'});
        $funidad.val('');
        $ftraslado.val('0');
        $fpresentacion.html('');
        $fcodigo.val('');
        $ftipo_cambio.val('');
        $fretencionIva.val('0');
        $fIsrPercent.val('')
        $("#productos #show_info_prod").show().find('span').text('');
      } else {
        noty({"text": 'Los campos marcados son obligatorios.', "layout":"topRight", "type": 'error'});
        $fconcepto.focus();
      }
    });
  };

  var eventBtnListaOtros = function () {
    $('#productos').on('click', "#btnListOtros", function(event) {
      var $this = $(this), $parent = $this.parents("div:first");
      if ($parent.find(".popover").is(":hidden"))
        $parent.find(".popover").show(80);
      else
        $parent.find(".popover").hide(80);
    });
  };

  var eventBtnListaActivos = function () {
    $('#productos').on('click', "#btnListActivos", function(event) {
      var $this = $(this), $parent = $this.parents("div:first");
      if ($parent.find(".popover").is(":hidden")){
        $parent.find(".popover").show(80);
        $parent.find('.clsActivos').focus();
      }
      else
        $parent.find(".popover").hide(80);
    });
  };

  // Evento key up para los campos cantidad, valor unitario, descuento en la tabla.
  var eventKeyUpCantPrecio = function () {
    $('#productos #table-productos').on('keyup', '#cantidad, #valorUnitario1, #valorUnitario2, #valorUnitario3, #iepsPorcent', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parents("tr.rowprod");

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalProducto($tr);
      }
    }).on('change', '#cantidad, #valorUnitario1, #valorUnitario2, #valorUnitario3, #iepsPorcent', function(event) {
      var $tr = $(this).parents("tr.rowprod");
      calculaTotalProducto($tr);
    });

    $("input.chkproducto").on('click', function(event) {
      var $tr = $(this).parents("tr.rowprod");
      calculaTotalProducto($tr);
    });
  };

  // Evento onchange del select iva en la tabla.
  var eventOnChangeTraslado = function () {
    $('#productos #table-productos').on('change', '#traslado, #ret_iva, #ret_isrPorcent', function(event) {
      var $this = $(this),
          $tr   = $this.parents("tr.rowprod");
      if ($this.attr('id') == 'traslado')
        $tr.find('#trasladoPorcent').val($this.find('option:selected').val());
      calculaTotalProducto($tr);
    });
  };

  // Evento click para el boton eliminar producto.
  var eventBtnDelProducto = function () {
    var $table = $('#productos #table-productos');

    $table.on('click', 'button#btnDelProd', function(event) {
      var idval = $(this).parents("div[id^=productos]").attr('id').replace("productos", ""),
      $parent = $(this).parents("tr.rowprod");
      $parent.remove();

      calculaTotal(1);
      calculaTotal(2);
      calculaTotal(3);
    });
  };

  var eventCheckboxProducto = function () {
    $('.prodOk').on('click', function(event) {
      var $parent = $(this).parents("tr.rowprod");

      if ($(this).is(':checked')) {
        $parent.find('#idProdOk').val('1');
      } else {
        $parent.find('#idProdOk').val('0');
      }
    });
  };

  var eventOnChangePresentacionTable = function () {
    $('#table-productos').on('change', 'select#presentacion', function(event) {
      var $select = $(this),
          $parent = $select.parents("tr.rowprod");

      $parent.find('#presentacionCant').val($select.find('option:selected').attr('data-cantidad') || '');
      $parent.find('#presentacionText').val($select.find('option:selected').text() || '');
    });
  };

  /*
   | Ligar ordenes
   */
  // Evento onchange del select condicion de pago.
  var eventOnChangeCondicionPago = function () {
    $('#condicionPago').on('change', function(event) {
      var $this = $(this);
      if ($this.val() == 'cr')
      {
        $("#grup_plazo_credito").show();
        $("#group_pago_contado").hide();
      }
      else
      {
        $("#grup_plazo_credito").hide();
        $("#group_pago_contado").show();
      }
    });
  };
  // Evento onchange del select metodo de pago.
  var eventOnChangeMetodoPago = function () {
    $('#fmetodo_pago').on('change', function(event) {
      var $this = $(this);
      if ($this.val() == 'transferencia')
      {
        $("#cuenta_proveedor").show();
      }
      else
      {
        $("#cuenta_proveedor").hide();
      }
    });
  };


  /*
   |------------------------------------------------------------------------
   | HTML builders
   |------------------------------------------------------------------------
   */

  var jumpIndex = 0;
  function addProducto(producto, idval) {
    var $tabla            = $('#productos #table-productos'),
        trHtml            = '',
        indexJump         = jumpIndex + 1,
        exist             = false,
        $autorizar_active = $("#btnAutorizar").length>0?true:false;

    // Si el dato "id" es diferente de nada entonces es un producto seleccionado
    // del catalogo.
    // if (producto.id !== '') {

    //   // Recorre los productos existentes para ver si el que se quiere agregar
    //   // ya existe en la tabla y si existe le suma 1 a la cantidad.
    //   var check = productoIsSelected(producto.id);
    //   if (check[0]) {
    //     var $parent = check[1].parent().parent(),
    //         $cantidad = $parent.find('input#cantidad');

    //     exist = true;
    //     $cantidad.val(parseFloat($cantidad.val()) + 1);

    //     calculaTotalProducto($parent);
    //   }
    // }

    // Si el producto a agregar no existe en el listado los agrega por primera
    // vez.
    if ( ! exist) {

      // var htmlPresen = '<select name="presentacion[]" class="span12" id="presentacion">';
      // $('#fpresentacion').find('option').each(function(index, el) {
      //   var selected = $(this).val() == producto.presentacionId ? 'selected' : '';
      //   if (selected != '') {
      //     htmlPresen += '<option value="'+$(this).val()+'" '+selected+'>'+$(this).text()+'</option>';
      //   }
      // });
      // htmlPresen += '</select>';

      // Si el Tipo de moneda es Dolar hace la conversion
      if (producto.tipo_moneda == 'dolar')
      {
        producto.precio_unitario = parseFloat(producto.precio_unitario)*parseFloat(producto.tipo_cambio);
      }

      var htmlUnidad = '<select name="unidad[]" class="span12" id="unidad">';
      $('#funidad').find('option').each(function(index, el) {
        var selected = $(this).val() == producto.unidad ? 'selected' : '';

        htmlUnidad += '<option value="'+$(this).val()+'" '+selected+'>'+$(this).text()+'</option>';
      });
      htmlUnidad += '</select>';

      $trHtml = $('<tr class="rowprod">' +
                  '<td style="">' +
                    producto.proveedor+
                    '<input type="hidden" name="proveedor[]" value="'+producto.proveedor+'" id="proveedor" class="span12" >' +
                    '<input type="hidden" name="proveedorId[]" value="'+producto.proveedor_id+'" id="proveedorId" class="span12" readonly>' +
                  '</td>' +
                  '<td style="width: 60px;">' +
                    '<input type="text" name="codigoArea[]" value="" id="codigoArea" class="span12 showCodigoAreaAuto" >' +
                    '<input type="hidden" name="codigoAreaId[]" value="" id="codigoAreaId" class="span12" readonly>' +
                    '<input type="hidden" name="codigoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12">' +
                    '<i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>'+
                  '</td>' +
                  '<td style="width: 60px;">' +
                    producto.codigo +
                    '<input type="hidden" name="codigo[]" value="'+producto.codigo+'" class="span12">' +
                    '<input type="hidden" name="tipo_cambio[]" value="'+(producto.tipo_cambio || 0)+'" class="span12">' +
                    '<input type="hidden" name="prodIdOrden[]" value="'+(producto.id_orden || 0)+'" class="span12 prodIdOrden">' +
                    '<input type="hidden" name="prodIdNumRow[]" value="'+(producto.num_row || 0)+'" class="span12">' +
                  '</td>' +
                  '<td style="width: 120px;">' +
                      '<input type="number" step="any" name="cantidad[]" value="'+producto.cantidad+'" id="cantidad" class="span12 vpositive jump'+jumpIndex+'" min="0" data-next="jump'+(++jumpIndex)+'">' +
                  '</td>' +
                  '<td style="width: 70px;">' +
                    $(htmlUnidad).addClass('jump'+(jumpIndex)).attr('data-next', "jump"+(++jumpIndex)).get(0).outerHTML +
                    $(producto.presentacion).addClass('span12 jump'+(jumpIndex)).attr({
                      'name': 'presentacion[]',
                      'data-next': "jump"+(++jumpIndex)
                    }).get(0).outerHTML +
                    '<input type="hidden" name="presentacionCant[]" value="'+producto.presentacionCantidad+'" id="presentacionCant" class="span12">' +
                    '<input type="hidden" name="presentacionText[]" value="'+$(producto.presentacion).find('option:selected').text()+'" id="presentacionText" class="span12">' +
                  '</td>' +
                  '<td>' +
                    producto.concepto +
                    '<input type="hidden" name="concepto[]" value="'+producto.concepto+'" id="concepto" class="span12">' +
                    '<input type="hidden" name="productoId[]" value="'+producto.id+'" id="productoId" class="span12">' +
                  '</td>' +
                  ($autorizar_active? '<td style="width: 10px;"></td>': '')+
                  '<td style="width: 120px;">' +
                    '<input type="text" name="valorUnitario1[]" value="'+producto.precio_unitario+'" id="valorUnitario1" class="span12 provvalorUnitario vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                  '</td>' +
                  '<td>' +
                    '<span>'+util.darFormatoNum('0')+'</span>' +
                    '<input type="hidden" name="importe1[]" value="0" id="importe1" class="span12 provimporte vpositive">' +
                    '<input type="hidden" name="total1[]" value="0" id="total1" class="span12 provtotal vpositive">' +
                    '<input type="hidden" name="trasladoTotal1[]" value="" id="trasladoTotal1" class="span12">' +
                    '<input type="hidden" name="iepsTotal1[]" value="0" id="iepsTotal1" class="span12">' +
                    '<input type="hidden" name="retTotal1[]" value="0" id="retTotal1" class="span12" readonly>' +
                    '<input type="hidden" name="retIsrTotal1[]" value="0" id="retIsrTotal1" class="span12" readonly>' +
                  '</td>' +
                  // ($autorizar_active? '<td style="width: 10px;"></td>': '')+
                  // '<td style="width: 90px;">' +
                  //   '<input type="text" name="valorUnitario2[]" value="" id="valorUnitario2" class="span12 provvalorUnitario vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                  // '</td>' +
                  // '<td>' +
                  //   '<span>'+util.darFormatoNum('0')+'</span>' +
                  //   '<input type="hidden" name="importe2[]" value="0" id="importe2" class="span12 provimporte vpositive">' +
                  //   '<input type="hidden" name="total2[]" value="0" id="total2" class="span12 provtotal vpositive">' +
                  //   '<input type="hidden" name="trasladoTotal2[]" value="" id="trasladoTotal2" class="span12">' +
                  //   '<input type="hidden" name="iepsTotal2[]" value="0" id="iepsTotal2" class="span12">' +
                  //   '<input type="hidden" name="retTotal2[]" value="0" id="retTotal2" class="span12" readonly>' +
                  //   '<input type="hidden" name="retIsrTotal2[]" value="0" id="retIsrTotal2" class="span12" readonly>' +
                  // '</td>' +
                  // ($autorizar_active? '<td style="width: 10px;"></td>': '')+
                  // '<td style="width: 90px;">' +
                  //   '<input type="text" name="valorUnitario3[]" value="" id="valorUnitario3" class="span12 provvalorUnitario vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                  // '</td>' +
                  // '<td>' +
                  //   '<span>'+util.darFormatoNum('0')+'</span>' +
                  //   '<input type="hidden" name="importe3[]" value="0" id="importe3" class="span12 provimporte vpositive">' +
                  //   '<input type="hidden" name="total3[]" value="0" id="total3" class="span12 provtotal vpositive">' +
                  //   '<input type="hidden" name="trasladoTotal3[]" value="" id="trasladoTotal3" class="span12">' +
                  //   '<input type="hidden" name="iepsTotal3[]" value="0" id="iepsTotal3" class="span12">' +
                  //   '<input type="hidden" name="retTotal3[]" value="0" id="retTotal3" class="span12" readonly>' +
                  //   '<input type="hidden" name="retIsrTotal3[]" value="0" id="retIsrTotal3" class="span12" readonly>' +
                  // '</td>' +
                  '<td style="width: 35px;">'+
                    '<div style="position:relative;"><button type="button" class="btn btn-inverse" id="btnListActivos"><i class="icon-font"></i></button>'+
                      '<div class="popover fade left in" style="top:-55.5px;left:-411px;margin-right: 43px;">'+
                        '<div class="arrow"></div><h3 class="popover-title">Activos</h3>'+
                        '<div class="popover-content">'+
                          '<div class="control-group" style="width: 375px;">'+
                            '<input type="text" name="observacionesP[]" class="span11 observacionesP" value="" placeholder="Observaciones">'+
                          '</div>'+
                          '<div class="control-group activosGrup" style="width: 375px;display: '+ ($('#tipoOrden').find('option:selected').val() !== 'f'? 'block' : 'none') +';">'+
                            '<div class="input-append span12">'+
                              '<input type="text" class="span11 clsActivos" value="" placeholder="Nissan FRX, Maquina limon">'+
                            '</div>'+
                            '<ul class="tags tagsActivosIds">'+
                            '</ul>'+
                            '<input type="hidden" name="activosP[]" class="activosP" value="{}">'+
                          '</div>'+
                        '</div>'+
                      '</div>'+
                    '</div>'+
                    '<div style="position:relative;"><button type="button" class="btn btn-info" id="btnListOtros"><i class="icon-list"></i></button>'+
                      '<div class="popover fade left in" style="top:-55.5px;left:-411px;margin-right: 43px;">'+
                        '<div class="arrow"></div><h3 class="popover-title">Otros</h3>'+
                        '<div class="popover-content">'+
                          '<table>'+
                            '<tr>'+
                              '<td style="width: 66px;">IVA</td>' +
                              '<td style="width: 66px;">Ret IVA</td>' +
                              '<td style="width: 66px;">Ret ISR</td>' +
                              '<td style="width: 66px;">IEPS</td>' +
                              '<td>DESCRIP</td>' +
                            '</tr>'+
                            '<tr>'+
                              '<td style="width: 66px;">' +
                                  '<select name="traslado[]" id="traslado" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                                    '<option value="0" '+(producto.traslado === '0' ? "selected" : "")+'>0%</option>' +
                                    '<option value="8" '+(producto.traslado === '8' ? "selected" : "")+'>8%</option>' +
                                    '<option value="16" '+(producto.traslado === '16' ? "selected" : "")+'>16%</option>' +
                                  '</select>' +
                                  '<input type="hidden" name="trasladoPorcent[]" value="'+producto.traslado+'" id="trasladoPorcent" class="span12">' +
                              '</td>' +
                              '<td style="width: 66px;">' +
                                  '<select name="ret_iva[]" id="ret_iva" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                                    '<option value="0" '+(producto.ret_iva === '0' ? "selected" : "")+'>No retener</option>'+
                                    '<option value="4" '+(producto.ret_iva === '4' ? "selected" : "")+'>4%</option>'+
                                    '<option value="10.6667" '+(producto.ret_iva === '10.6667' ? "selected" : "")+'>2 Terceras</option>'+
                                    '<option value="16" '+(producto.ret_iva === '16' ? "selected" : "")+'>100 %</option>'+
                                    '<option value="6" '+(producto.ret_iva === '6' ? "selected" : "")+'>6 %</option>'+
                                    '<option value="8" '+(producto.ret_iva === '8' ? "selected" : "")+'>8 %</option>'+
                                  '</select>' +
                              '</td>' +
                              '<td style="width: 66px;">' +
                                  '<input type="text" name="ret_isrPorcent[]" value="'+(producto.ret_isr || 0)+'" id="ret_isrPorcent" class="span12">' +
                              '</td>' +
                              '<td style="width: 66px;">' +
                                  '<input type="text" name="iepsPorcent[]" value="'+(producto.ieps || 0)+'" id="iepsPorcent" class="span12">' +
                              '</td>' +
                              '<td>' +
                                '<input type="text" name="observacion[]" value="" id="observacion" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                              '</td>' +
                            '</tr>'+
                          '</table>'+
                        '</div>'+
                      '</div>'+
                    '</div>'+
                    '<button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button>'+
                  '</td>' +
                '</tr>');

      $($trHtml).appendTo($tabla.find('tbody.bodyproducs'));
      calculaTotalProducto($trHtml);

      for (i = indexJump, max = jumpIndex; i <= max; i += 1) {
        $.fn.keyJump.setElem($('.jump'+i));
      }

      $(".vnumeric").numeric(); //numero
      $(".vinteger").numeric({ decimal: false }); //Valor entero
      $(".vpositive").numeric({ negative: false }); //Numero positivo
      $(".vpos-int").numeric({ decimal: false, negative: false }); //Numero entero positivo

      // $('.jump'+indexJump).focus();
    }
  }

  /*
   |------------------------------------------------------------------------
   | Totales
   |------------------------------------------------------------------------
   */

  // Calcula el subtotal(importe),  iva y total de la orden de compra.
  function calculaTotal (idval) {
    var total_importes = 0,
        total_ivas     = 0,
        total_ieps     = 0,
        total_ret      = 0,
        total_retisr   = 0,
        total_orden    = 0,
        chkproducto    = $('.chkproducto');

     $('#productos input#importe'+idval).each(function(i, e) {
        var $tr = $(this).parents("tr");
        if(chkproducto.length > 0){
          if($tr.find('.chkproducto').is(':checked')){
            total_importes += parseFloat($(this).val());
          }
        }else
          total_importes += parseFloat($(this).val());
     });
     total_importes = util.trunc2Dec(total_importes);

     var total_subtotal = util.trunc2Dec(parseFloat(total_importes));

     $('#productos input#trasladoTotal'+idval).each(function(i, e) {
        var $tr = $(this).parents("tr");
        if(chkproducto.length > 0){
          if($tr.find('.chkproducto').is(':checked')){
            total_ivas += parseFloat($(this).val());
          }
        }else
          total_ivas += parseFloat($(this).val());
     });
     total_ivas = util.trunc2Dec(total_ivas);

     $('#productos input#iepsTotal'+idval).each(function(i, e) {
        var $tr = $(this).parents("tr");
        if(chkproducto.length > 0){
          if($tr.find('.chkproducto').is(':checked')){
            total_ieps += parseFloat($(this).val());
          }
        }else
          total_ieps += parseFloat($(this).val());
     });
     total_ieps = util.trunc2Dec(total_ieps);

     $('#productos input#retTotal'+idval).each(function(i, e) {
        var $tr = $(this).parents("tr");
        if(chkproducto.length > 0){
          if($tr.find('.chkproducto').is(':checked')){
            total_ret += parseFloat($(this).val());
          }
        }else
          total_ret += parseFloat($(this).val());
        console.log(total_ret);
     });
     total_ret = util.trunc2Dec(total_ret);

     $('#productos input#retIsrTotal'+idval).each(function(i, e) {
        var $tr = $(this).parents("tr");
        if(chkproducto.length > 0){
          if($tr.find('.chkproducto').is(':checked')){
            total_retisr += parseFloat($(this).val());
          }
        }else
          total_retisr += parseFloat($(this).val());
     });
     total_retisr = util.trunc2Dec(total_retisr);

     total_orden = parseFloat(total_subtotal) + parseFloat(total_ivas) + parseFloat(total_ieps) - parseFloat(total_ret) - parseFloat(total_retisr);

    $('#productos #importe-format'+idval).html(util.darFormatoNum(total_subtotal));
    $('#productos #totalImporte'+idval).val(total_subtotal);

    $('#productos #traslado-format'+idval).html(util.darFormatoNum(total_ivas));
    $('#productos #totalImpuestosTrasladados'+idval).val(total_ivas);

    $('#productos #ieps-format'+idval).html(util.darFormatoNum(total_ieps));
    $('#productos #totalIeps'+idval).val(total_ieps);

    $('#productos #retencion-format'+idval).html(util.darFormatoNum(total_ret));
    $('#productos #totalRetencion'+idval).val(total_ret);

    $('#productos #retencionisr-format'+idval).html(util.darFormatoNum(total_retisr));
    $('#productos #totalRetencionIsr'+idval).val(total_retisr);

    $('#productos #total-format'+idval).html(util.darFormatoNum(total_orden));
    $('#productos #totalOrden'+idval).val(total_orden);

    $('#productos #totalLetra'+idval).val(util.numeroToLetra.covertirNumLetras(total_orden.toString()));
  }

  // Realiza los calculos del producto: iva, importe total.
  function calculaTotalProducto ($tr) {
    var idval = $tr.parents("div[id^=productos]").attr('id').replace("productos", "");
        $cantidad    = $tr.find('#cantidad'), // Input cantidad
        $precio_uni  = [$tr.find('#valorUnitario1'), $tr.find('#valorUnitario2'), $tr.find('#valorUnitario3')], // Input precio u.
        $importe     = [$tr.find('#importe1'), $tr.find('#importe2'), $tr.find('#importe3')], // Input hidden importe
        $total       = [$tr.find('#total1'), $tr.find('#total2'), $tr.find('#total3')], // Input hidden iva total
        $totalIva    = [$tr.find('#trasladoTotal1'), $tr.find('#trasladoTotal2'), $tr.find('#trasladoTotal3')], // Input hidden iva total
        $totalRet    = [$tr.find('#retTotal1'), $tr.find('#retTotal2'), $tr.find('#retTotal3')], // Input hidden ret iva total
        $iepsTotal   = [$tr.find('#iepsTotal1'), $tr.find('#iepsTotal2'), $tr.find('#iepsTotal3')], // Input hidden ieps total
        $retIsrTotal = [$tr.find('#retIsrTotal1'), $tr.find('#retIsrTotal2'), $tr.find('#retIsrTotal3')], // Input hidden ieps total
        $iva         = $tr.find('#traslado'), // Select iva
        $ieps        = $tr.find('#iepsPorcent'), // Input hidden iva total
        $ret_iva     = $tr.find('#ret_iva'), // Select ret_iva
        $ret_isrPorcent = $tr.find('#ret_isrPorcent'), // ret_isrPorcent
        totalImporte = 0,
        totalIva     = 0,
        totalIeps    = 0,
        totalRet     = 0,
        totalRetIsr  = 0,
        total        = 0;

    for (var i = 0; i < $precio_uni.length; i++) {

      totalImporte = util.trunc2Dec(parseFloat(($cantidad.val() || 0) * parseFloat($precio_uni[i].val() || 0)));
      totalIva     = util.trunc2Dec(((totalImporte) * parseFloat($iva.find('option:selected').val())) / 100);
      totalIeps    = util.trunc2Dec(((totalImporte) * parseFloat($ieps.val() || 0)) / 100);
      totalRet     = util.trunc2Dec(totalImporte * (parseFloat($ret_iva.find('option:selected').val()) / 100) );
      totalRetIsr  = util.trunc2Dec(totalImporte * (parseFloat($ret_isrPorcent.val()) / 100) );
      total        = util.trunc2Dec(totalImporte + totalIva + totalIeps);

      if ($('#tipoOrden').find('option:selected').val() === 'f' || $tr.find('#prodTipoOrden').val() === 'f' || totalRet >= 0) {
        total -= parseFloat(totalRet);
        $totalRet[i].val(totalRet);
      }

      if (totalRetIsr >= 0) {
        total -= parseFloat(totalRetIsr);
        $retIsrTotal[i].val(totalRetIsr);
      }

      $totalIva[i].val(totalIva);
      $iepsTotal[i].val(totalIeps);
      $importe[i].parent().find('span').text(util.darFormatoNum(totalImporte));
      $importe[i].val(totalImporte);
      $total[i].val(total);

      calculaTotal(i+1);
    }

  }
  /*
   |------------------------------------------------------------------------
   | Helpers
   |------------------------------------------------------------------------
   */

  // Regresa true si esta seleccionada una empresa si no false.
  var isEmpresaSelected = function () {
    return $('#empresaId').val() !== '';
  };

  var getProyectos = function () {
    var params = {
      did_empresa: $('#empresaId').val()
    };

    hhtml = '<option value=""></option>';
    if (params.did_empresa > 0) {
      $.ajax({
          url: base_url + 'panel/proyectos/ajax_get_proyectos/',
          dataType: "json",
          data: params,
          success: function(data) {
            for (var i = 0; i < data.length; i++) {
              hhtml += '<option value="'+data[i].id+'">'+data[i].value+'</option>';
            }

            $('#proyecto').html(hhtml);
          }
      });
    } else {
      $('#proyecto').html(hhtml);
    }
  };

  var getSucursales = function () {
    var params = {
      did_empresa: $('#empresaId').val()
    };

    hhtml = '<option value=""></option>';
    if (params.did_empresa > 0) {
      $.ajax({
          url: base_url + 'panel/empresas/ajax_get_sucursales/',
          dataType: "json",
          data: params,
          success: function(data) {
            if(data.length > 0) {
              let idSelected = $('#sucursalId').data('selected'), selected = '';
              for (var i = 0; i < data.length; i++) {
                selected = (idSelected == data[i].id_sucursal? ' selected': '');
                hhtml += '<option value="'+data[i].id_sucursal+'" '+selected+'>'+data[i].nombre_fiscal+'</option>';
              }

              $('#sucursalId').html(hhtml).attr('required', 'required');
              $('.sucursales').show();
            } else {
              $('#sucursalId').html(hhtml).removeAttr('required');
              $('.sucursales').hide();
            }
          }
      });
    } else {
      $('#sucursalId').html(hhtml).removeAttr('required');
      $('.sucursales').hide();
    }
  };

});