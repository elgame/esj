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

    eventCodigoBarras();
    eventBtnAddProducto();
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
    eventLigarBoletasEntrada();

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

    $("#BtnImprimir").on('click', function(event) {
      var datos = '?folio='+$(this).attr('data-folio')+'&ide='+$(this).attr('data-ide')+'&ruta='+$("#lista_impresoras").val();
      $.get(base_url+'panel/compras_ordenes/ajax_imprimir_recibo/'+datos,
      function() {
        console.log("dd");
      });
    });

  });

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

   // Obtiene el siguiente folio segun el tipo de orden.
  var eventOnChangeTipoOrden = function () {
    var tipoOrderActual = $('#tipoOrden').find('option:selected').val();

    $('#tipoOrden').on('change', function(event) {
      var $this      = $(this),
          $folio     = $('#folio'),
          $tableProd = $('#table-productos');

      if ($tableProd.find('tbody tr').length > 0) {
        noty({"text": 'Ya tiene productos para un tipo de orden, si desea cambiar de tipo de orden elimine los productos del listado', "layout":"topRight", "type": 'error'});
        console.log('test', tipoOrderActual);
        $this.val(tipoOrderActual);
      } else {
        $.get(base_url + 'panel/compras_ordenes/ajax_get_folio/?tipo=' + $this.find('option:selected').val(), function(folio) {
          $folio.val(folio);
          tipoOrderActual = $this.find('option:selected').val();
          if(tipoOrderActual == 'f')
            $("#fletesFactura").show();
          else
            $("#fletesFactura").hide();

          if(tipoOrderActual == 'd')
            $("#verVehiculoChk").show();
          else
            $("#verVehiculoChk").hide();
        });
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
        $('#proveedor').val('');
        $('#proveedorId').val('');
        $('#groupCatalogos').show();
        $('#area').val('');
        $('#areaId').val('');
        $('#rancho').val('');
        $('#ranchoId').val('');
        $('#activos').val('');
        $('#activoId').val('');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#empresa").css("background-color", "#FFD071");
        $("#empresaId").val('');
        $('#proveedor').val('');
        $('#proveedorId').val('');
        $('#area').val('');
        $('#areaId').val('');
        $('#rancho').val('');
        $('#ranchoId').val('');
        $('#activos').val('');
        $('#activoId').val('');
        $('#groupCatalogos').show();
      }
    });
  };

  // Autocomplete para los Proveedores.
  var autocompleteProveedores = function () {
    $("#proveedor").autocomplete({
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

        $proveedor.val(ui.item.id);
        $("#proveedorId").val(ui.item.id);
        $proveedor.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#proveedor").css("background-color", "#FFD071");
        $("#proveedorId").val('');
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

  var autocompleteAutorizo = function () {
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
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
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

        // $area.val(ui.item.id);
        // $("#areaId").val(ui.item.id);
        // $area.css("background-color", "#A1F57A");
        addAreaTag(ui.item);
        setTimeout(function () {
          $area.val('');
        }, 200);

        $("#rancho").val('').css("background-color", "#FFD071");
        $('#tagsRanchoIds').html('');
        // $("#ranchoId").val('');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        // $("#area").css("background-color", "#FFD071");
        // $("#areaId").val('');
        // $("#rancho").val('').css("background-color", "#FFD071");
        // $('#tagsRanchoIds').html('');
        // // $("#ranchoId").val('');
      }
    });

    function addAreaTag(item) {
      $('#tagsAreaIds').html('');
      if ($('#tagsAreaIds .areaId[value="'+item.id+'"]').length === 0) {
        $('#tagsAreaIds').append('<li><span class="tag">'+item.value+'</span>'+
          '<input type="hidden" name="areaId[]" class="areaId" value="'+item.id+'">'+
          '<input type="hidden" name="areaText[]" class="areaText" value="'+item.value+'">'+
          '</li>');
      } else {
        noty({"text": 'Ya esta agregada el Areas, Ranchos o Lineas.', "layout":"topRight", "type": 'error'});
      }
    };

    $('#tagsAreaIds').on('click', 'li:not(.disable)', function(event) {
      $(this).remove();
      $("#area").css("background-color", "#FFD071");
      $("#areaId").val('');
      $("#rancho").val('').css("background-color", "#FFD071");
      $('#tagsRanchoIds').html('');
    });
  };

  var autocompleteRanchos = function () {
    $("#rancho").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();

        if(parseInt($('#tagsAreaIds .areaId').first().val()) > 0)
          params.area = $('#tagsAreaIds .areaId').first().val();
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
    $("#activos").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        // if(parseInt($("#empresaId").val()) > 0)
        //   params.did_empresa = $("#empresaId").val();
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
        var $activos =  $(this);

        $activos.val(ui.item.id);
        $("#activoId").val(ui.item.id);
        $activos.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#activos").css("background-color", "#FFD071");
        $("#activoId").val('');
      }
    });
  };


  // Autocomplete para el codigo.
  var autocompleteCodigo = function () {
    $("#fcodigo").autocomplete({
      source: function (request, response) {
        if (isEmpresaSelected()) {
          $.ajax({
            url: base_url + 'panel/compras_ordenes/ajax_producto_by_codigo/',
            dataType: 'json',
            data: {
              term : request.term,
              ide: $('#empresaId').val(),
              tipo: $('#tipoOrden').find('option:selected').val()
            },
            success: function (data) {
              response(data)
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
            $fconcepto     = $('#fconcepto'),
            $fconceptoId   = $('#fconceptoId'),
            $fcantidad     = $('#fcantidad'),
            $fprecio       = $('#fprecio'),
            $fpresentacion = $('#fpresentacion'),
            $funidad       = $('#funidad'),
            $ftraslado     = $('#ftraslado');

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

        addProducto(producto);

        $fcodigo.val('');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $(this).css("background-color", "#FDFC9A");
        $("#fconcepto").val("");
        $('#fconceptoId').val('');
        $('#fcantidad').val('');
        $('#fprecio').val('');
        $('#funidad').val('');
        $('#ftraslado').val('');
        $('#fpresentacion').html('');
      }
    });
  };

  // Autocomplete para el codigo.
  var autocompleteConcepto = function () {
    $("#fconcepto").autocomplete({
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
            $fcodigo     = $('#fcodigo'),
            $fconceptoId   = $('#fconceptoId'),
            $fcantidad     = $('#fcantidad'),
            $fprecio       = $('#fprecio'),
            $fpresentacion = $('#fpresentacion'),
            $funidad       = $('#funidad'),
            $fieps         = $('#fieps'),
            $ftraslado     = $('#ftraslado');

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
          // saldo_anterior = parseFloat(ui.item.item.inventario.saldo_anterior),
          // existencias = saldo_anterior+entradas-salidas;
          existencias = ui.item.item.inventario;
          $("#show_info_prod").show().find('span').text('Existencia: '+util.darFormatoNum(existencias, '')+' | Stock min: '+util.darFormatoNum(ui.item.item.stock_min, ''));
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
        $(this).css("background-color", "#FDFC9A");
        $("#fcodigo").val("");
        $('#fconceptoId').val('');
        $('#fcantidad').val('');
        $('#fprecio').val('');
        $('#funidad').val('');
        $('#ftraslado').val('');
        $('#fpresentacion').html('');
        $("#show_info_prod").show().find('span').text('');
      }
    });
  };

  /*
   |------------------------------------------------------------------------
   | Events
   |------------------------------------------------------------------------
   */
  var eventCodigoBarras = function () {
    $('#fcodigo').on('keypress', function(event) {
      var $codigo = $(this);
      if (isEmpresaSelected()) {
        if (event.which === 13 && $codigo.val() !== '') {
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

              addProducto(producto);

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
    $('#ftipo_cambio').on('keypress', function(event) {
      if (event.which === 13) {
        $('#btnAddProd').click();
      }
    });
  };

  var eventTipoMonedaChange = function () {
    $("#ftipo_moneda").on('change', function(event) {
      // Se obtiene el tipo de cambio de Banxico
      if ($(this).val() == 'dolar') {
        $.ajax({
          url      : document.location.protocol + '//ajax.googleapis.com/ajax/services/feed/load?v=1.0&num=10&callback=?&q=' +
                      encodeURIComponent("http://www.banxico.org.mx/rsscb/rss?BMXC_canal=fix&BMXC_idioma=es"),
          dataType : 'json',
          success  : function (data) {
            if (data.responseData.feed && data.responseData.feed.entries) {
              $.each(data.responseData.feed.entries, function (i, e) {
                $("#ftipo_cambio").val(parseFloat(e.title.substr(3, 9)));
              });
            }
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

  var eventLigarBoletas = function () {
    $("#show-boletas").on('click', function(event) {
      // $(".filTipoFacturas").removeAttr('checked');
      // $(".filTipoFacturas:first").attr('checked', 'checked');
      $("#filBoleta").val("");

      getBoletas();
      $("#modal-boletas").modal('show');
      $("#modal-boletas #BtnAddBoleta").removeClass('entrada');
    });

    // $(".filTipoFacturas").on('change', function(event) {
    //   getBoletas($(this).val());
    // });
    $("#filBoleta").on('change', function(event) {
      getBoletas();
    });
    setBoletasSel();
    $("#boletasLigada").on('click', function(event) {
      $(this).html("");
    });
  };

  var setBoletasSel = function () {
    $("#BtnAddBoleta").on('click', function(event) {
      var selected = $(".radioBoleta:checked"), facts = '', folios = '';

      selected.each(function(index, el) {
        var $this = $(this);
        facts += $this.val()+'|';
        folios += $this.attr("data-folio")+' | ';
      });

      if ($("#BtnAddBoleta").is('.entrada')) {
        $("#boletasEntrada").html(folios+' <input type="hidden" name="boletasEntradaId" value="'+facts+'"><input type="hidden" name="boletasEntradaFolio" value="'+folios+'">');
      } else {
        $("#boletasLigada").html(folios+' <input type="hidden" name="boletas" value="'+facts+'"><input type="hidden" name="boletas_folio" value="'+folios+'">');
      }

      $("#modal-boletas").modal('hide');
    });
  };

  // Datos extras
  var eventLigarBoletasEntrada = function () {
    $("#show-boletasEntrada").on('click', function(event) {
      // $(".filTipoFacturas").removeAttr('checked');
      // $(".filTipoFacturas:first").attr('checked', 'checked');
      $("#filBoleta").val("");

      getBoletas(['en', 'sa', 'p', 'b']);
      $("#modal-boletas").modal('show');
      $("#modal-boletas #BtnAddBoleta").addClass('entrada');
    });

    $("#filBoleta").on('change', function(event) {
      getBoletas(['en', 'sa', 'p', 'b']);
    });
    $("#boletasEntrada").on('click', function(event) {
      $(this).html("");
    });
  };

  var getBoletas = function(accion){
    var params = {
      // clienteId: $("#clienteId").val(),
      accion: (accion? accion: ['en', 'p', 'b']),
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
        '  <td>'+json[i].area+'</td>'+
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
    $('#btnAddProd').on('click', function(event) {
      var $fcodigo     = $('#fcodigo').css({'background-color': '#FFF'}),
          $fconcepto     = $('#fconcepto').css({'background-color': '#FFF'}),
          $fconceptoId   = $('#fconceptoId'),
          $fcantidad     = $('#fcantidad').css({'background-color': '#FFF'}),
          $fprecio       = $('#fprecio').css({'background-color': '#FFF'}),
          $fpresentacion = $('#fpresentacion'),
          $funidad       = $('#funidad'),
          $fieps         = $('#fieps'),
          $ftipo_moneda  = $('#ftipo_moneda'),
          $ftipo_cambio  = $('#ftipo_cambio'),
          $ftraslado     = $('#ftraslado'),

          campos = [$fcantidad, $fprecio],
          producto,
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
      if ($ftipo_moneda.val() === 'dolar' && $ftipo_cambio.val() == '') {
        $ftipo_cambio.css({'background-color': '#FDFC9A'});
        error = true;
      }

      // Valida si el campo precio es 0.
      // if ($fprecio.val() === '0') {
      //   $fprecio.css({'background-color': '#FDFC9A'})
      //   error = true;
      // }

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
        };

        addProducto(producto);

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
        $("#show_info_prod").show().find('span').text('');
      } else {
        noty({"text": 'Los campos marcados son obligatorios.', "layout":"topRight", "type": 'error'});
        $fconcepto.focus();
      }
    });
  };

  // Evento key up para los campos cantidad, valor unitario, descuento en la tabla.
  var eventKeyUpCantPrecio = function () {
    $('#table-productos').on('keyup', '#cantidad, #valorUnitario, #iepsPorcent, #faltantes', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalProducto($tr);
      }
    }).on('change', '#cantidad, #valorUnitario, #iepsPorcent, #faltantes', function(event) {
      var $tr = $(this).parent().parent();
      calculaTotalProducto($tr);
    });

    $("input.chkproducto").on('click', function(event) {
      var $tr = $(this).parent().parent();
      calculaTotalProducto($tr);
    });
  };

  // Evento onchange del select iva en la tabla.
  var eventOnChangeTraslado = function () {
    $('#table-productos').on('change', '#traslado, #ret_iva, #ret_isrPorcent', function(event) {
      var $this = $(this),
          $tr   = $this.parent().parent();

      if ($this.attr('id') == 'traslado')
        $tr.find('#trasladoPorcent').val($this.find('option:selected').val());
      calculaTotalProducto($tr);
    });
  };

  // Evento click para el boton eliminar producto.
  var eventBtnDelProducto = function () {
    var $table = $('#table-productos');

    $table.on('click', 'button#btnDelProd', function(event) {
      var $parent = $(this).parent().parent();
      $parent.remove();

      calculaTotal();
    });
  };

  var eventCheckboxProducto = function () {
    $('.prodOk').on('click', function(event) {
      var $parent = $(this).parents('tr');

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
          $parent = $select.parents('tr');

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
  function addProducto(producto) {
    var $tabla    = $('#table-productos'),
        trHtml    = '',
        indexJump = jumpIndex + 1,
        exist     = false;

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

      $trHtml = $('<tr>' +
                  '<td style="width: 70px;">' +
                    producto.codigo +
                    '<input type="hidden" name="codigo[]" value="'+producto.codigo+'" class="span12">' +
                    '<input type="hidden" name="tipo_cambio[]" value="'+(producto.tipo_cambio || 0)+'" class="span12">' +
                    '<input type="hidden" name="prodIdOrden[]" value="'+(producto.id_orden || 0)+'" class="span12">' +
                    '<input type="hidden" name="prodIdNumRow[]" value="'+(producto.num_row || 0)+'" class="span12">' +
                  '</td>' +
                  '<td>' +
                    producto.concepto +
                    '<input type="hidden" name="concepto[]" value="'+producto.concepto+'" id="concepto" class="span12">' +
                    '<input type="hidden" name="productoId[]" value="'+producto.id+'" id="productoId" class="span12">' +
                  '</td>' +
                  '<td style="width: 160px;">' +
                    $(producto.presentacion).addClass('jump'+(jumpIndex)).attr('data-next', "jump"+(++jumpIndex)).get(0).outerHTML +
                    '<input type="hidden" name="presentacionCant[]" value="'+producto.presentacionCantidad+'" id="presentacionCant" class="span12">' +
                    '<input type="hidden" name="presentacionText[]" value="'+$(producto.presentacion).find('option:selected').text()+'" id="presentacionText" class="span12">' +
                  '</td>' +
                  '<td style="width: 150px;">' +
                    $(htmlUnidad).addClass('jump'+(jumpIndex)).attr('data-next', "jump"+(++jumpIndex)).get(0).outerHTML +
                  '</td>' +
                  '<td style="width: 65px;">' +
                      '<input type="number" step="any" name="cantidad[]" value="'+producto.cantidad+'" id="cantidad" class="span12 vpositive jump'+jumpIndex+'" min="0" data-next="jump'+(++jumpIndex)+'">' +
                  '</td>' +
                  '<td style="width: 65px;">' +
                      '<input type="number" name="faltantes[]" value="0" id="faltantes" class="span12 vpositive jump'+jumpIndex+'" min="0" data-next="jump'+(++jumpIndex)+'">' +
                  '</td>' +
                  '<td style="width: 90px;">' +
                    '<input type="text" name="valorUnitario[]" value="'+producto.precio_unitario+'" id="valorUnitario" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                  '</td>' +
                  '<td style="width: 66px;">' +
                      '<select name="traslado[]" id="traslado" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                        '<option value="0" '+(producto.traslado === '0' ? "selected" : "")+'>0%</option>' +
                        '<option value="8" '+(producto.traslado === '8' ? "selected" : "")+'>8%</option>' +
                        '<option value="16" '+(producto.traslado === '16' ? "selected" : "")+'>16%</option>' +
                      '</select>' +
                      '<input type="hidden" name="trasladoTotal[]" value="" id="trasladoTotal" class="span12">' +
                      '<input type="hidden" name="trasladoPorcent[]" value="'+producto.traslado+'" id="trasladoPorcent" class="span12">' +
                  '</td>' +
                  '<td style="width: 66px;">' +
                      '<input type="text" name="iepsPorcent[]" value="'+(producto.ieps || 0)+'" id="iepsPorcent" class="span12">' +
                      '<input type="hidden" name="iepsTotal[]" value="0" id="iepsTotal" class="span12">' +
                  '</td>' +
                  '<td style="width: 66px;">'+
                      '<select name="ret_iva[]" id="ret_iva" class="span12">'+
                        '<option value="0" '+(producto.ret_iva === '0' ? "selected" : '')+'>No retener</option>'+
                        '<option value="4" '+(producto.ret_iva === '4' ? "selected" : '' )+'>4%</option>'+
                        '<option value="10.6667" '+(producto.ret_iva === '10.6667' ? "selected" : '' )+'>2 Terceras</option>'+
                        '<option value="16" '+(producto.ret_iva === '16' ? "selected" : '')+'>100 %</option>'+
                        '<option value="6" '+(producto.ret_iva === '6' ? "selected" : "")+'>6 %</option>'+
                        '<option value="8" '+(producto.ret_iva === '8' ? "selected" : "")+'>8 %</option>'+
                      '</select>'+
                      '<input type="text" name="retTotal[]" value="0" id="retTotal" class="span12" readonly>'+
                  '</td>'+
                  '<td style="width: 66px;">'+
                      '<input type="text" name="ret_isrPorcent[]" value="'+(producto.ret_isr || 0)+'" id="ret_isrPorcent" class="span12 vpositive">'+
                      '<input type="text" name="ret_isrTotal[]" value="0" id="ret_isrTotal" class="span12">'+
                  '</td>'+
                  '<td>' +
                    '<span>'+util.darFormatoNum('0')+'</span>' +
                    '<input type="hidden" name="importe[]" value="0" id="importe" class="span12 vpositive">' +
                    '<input type="hidden" name="total[]" value="0" id="total" class="span12 vpositive">' +
                  '</td>' +
                  '<td>' +
                    '<input type="text" name="observacion[]" value="" id="observacion" class="span12 jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">' +
                  '</td>' +
                  '<td style="width: 35px;"><button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button></td>' +
                '</tr>');

      $($trHtml).appendTo($tabla.find('tbody'));
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
  function calculaTotal () {
    var total_importes = 0,
        total_ivas     = 0,
        total_ieps     = 0,
        total_ret      = 0,
        ret_isrTotal   = 0,
        total_orden    = 0,
        chkproducto    = $('.chkproducto');

     $('input#importe').each(function(i, e) {
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

     $('input#trasladoTotal').each(function(i, e) {
        var $tr = $(this).parents("tr");
        if(chkproducto.length > 0){
          if($tr.find('.chkproducto').is(':checked')){
            total_ivas += parseFloat($(this).val());
          }
        }else
          total_ivas += parseFloat($(this).val());
     });
     total_ivas = util.trunc2Dec(total_ivas);

     $('input#iepsTotal').each(function(i, e) {
        var $tr = $(this).parents("tr");
        if(chkproducto.length > 0){
          if($tr.find('.chkproducto').is(':checked')){
            total_ieps += parseFloat($(this).val());
          }
        }else
          total_ieps += parseFloat($(this).val());
     });
     total_ieps = util.trunc2Dec(total_ieps);

     $('input#retTotal').each(function(i, e) {
        var $tr = $(this).parents("tr");
        if(chkproducto.length > 0){
          if($tr.find('.chkproducto').is(':checked')){
            total_ret += parseFloat($(this).val());
          }
        }else
          total_ret += parseFloat($(this).val());
     });
     total_ret = util.trunc2Dec(total_ret);

     $('input#ret_isrTotal').each(function(i, e) {
        var $tr = $(this).parents("tr");
        if(chkproducto.length > 0){
          if($tr.find('.chkproducto').is(':checked')){
            ret_isrTotal += parseFloat($(this).val());
          }
        }else
          ret_isrTotal += parseFloat($(this).val());
     });
     ret_isrTotal = util.trunc2Dec(ret_isrTotal);

     total_orden = parseFloat(total_subtotal) + parseFloat(total_ivas) + parseFloat(total_ieps) - parseFloat(total_ret) - parseFloat(ret_isrTotal);

    $('#importe-format').html(util.darFormatoNum(total_subtotal));
     $('#totalImporte').val(total_subtotal);

     $('#traslado-format').html(util.darFormatoNum(total_ivas));
     $('#totalImpuestosTrasladados').val(total_ivas);

     $('#ieps-format').html(util.darFormatoNum(total_ieps));
     $('#totalIeps').val(total_ieps);

     $('#retencion-format').html(util.darFormatoNum(total_ret));
     $('#totalRetencion').val(total_ret);

     $('#retencionisr-format').html(util.darFormatoNum(ret_isrTotal));
     $('#totalRetencionIsr').val(ret_isrTotal);

     $('#total-format').html(util.darFormatoNum(total_orden));
     $('#totalOrden').val(total_orden);

     $('#totalLetra').val(util.numeroToLetra.covertirNumLetras(total_orden.toString()));
  }

  // Realiza los calculos del producto: iva, importe total.
  function calculaTotalProducto ($tr) {
    var $cantidad     = $tr.find('#cantidad'), // Input cantidad
        $precio_uni   = $tr.find('#valorUnitario'), // Input precio u.
        $iva          = $tr.find('#traslado'), // Select iva
        $ret_iva      = $tr.find('#ret_iva'), // Select ret_iva
        $ret_isrPorcent = $tr.find('#ret_isrPorcent'), // Select ret_isrPorcent
        $importe      = $tr.find('#importe'), // Input hidden importe
        $totalIva     = $tr.find('#trasladoTotal'), // Input hidden iva total
        $totalRet     = $tr.find('#retTotal'), // Input hidden iva total
        $ret_isrTotal = $tr.find('#ret_isrTotal'), // Input hidden ret_isrTotal
        $total        = $tr.find('#total'), // Input hidden iva total
        $ieps         = $tr.find('#iepsPorcent'), // Input hidden iva total
        $iepsTotal    = $tr.find('#iepsTotal'), // Input hidden iva total
        $faltantes    = $tr.find('#faltantes'),

        totalImporte = util.trunc2Dec( ( parseFloat($cantidad.val() || 0) - parseFloat($faltantes.val() || 0) ) * parseFloat($precio_uni.val() || 0)),
        totalIva     = util.trunc2Dec(((totalImporte) * parseFloat($iva.find('option:selected').val())) / 100),
        totalIeps    = util.trunc2Dec(((totalImporte) * parseFloat($ieps.val() || 0)) / 100),
        totalRet     = util.trunc2Dec(totalImporte * (parseFloat($ret_iva.find('option:selected').val()) / 100) ),
        totalRetIsr  = util.trunc2Dec(totalImporte * (parseFloat($ret_isrPorcent.val()) / 100) ),
        total        = util.trunc2Dec(totalImporte + totalIva + totalIeps);

    if ($('#tipoOrden').find('option:selected').val() === 'f' || $tr.find('#prodTipoOrden').val() === 'f' || totalRet >= 0) {
      total -= parseFloat(totalRet);
      $totalRet.val(totalRet);
    }

    if (totalRetIsr >= 0) {
      total -= parseFloat(totalRetIsr);
      $ret_isrTotal.val(totalRetIsr);
    }

    $totalIva.val(totalIva);
    $iepsTotal.val(totalIeps);
    $importe.parent().find('span').text(util.darFormatoNum(totalImporte));
    $importe.val(totalImporte);
    $total.val(total);

    calculaTotal();
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

});