(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();

    autocompleteEmpresas();
    autocompleteProdProducir();
    // autocompleteTrabajador();
    autocompleteConcepto();

    eventCodigoBarras();
    eventBtnAddProducto();
    eventBtnDelProducto();
    eventCantKeypress();

    $("#table-productos").on('change', '#cantidad', function(event) {
      var $tr = $(this).parent().parent();
      calculaProductoTotal($tr);
    });
    $("#costo_adicional").change(function(event) {
      calculaTotal();
    });

    $("#id_almacen").change(function(event) {
      var $table = $('#table-productos');
      $table.find('button#btnDelProd').each(function(index, el) {
        $(this).click();
      });
    });

    autocompleteCultivo();
    autocompleteRanchos();
    autocompleteCentroCosto();

    // copyCodigoAll();
  });

  /*
   |------------------------------------------------------------------------
   | Autocompletes
   |------------------------------------------------------------------------
   */

  // Codigos
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
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
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

        quitProductos();
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#empresa").css("background-color", "#FFD071");
        $("#empresaId").val('');
        quitProductos();
      }
    });
  };

  var quitProductos = function () {
    $('#table-productos tbody tr').remove();
    $('#prod_producir').val('');
    $('#id_prod_producir').val('');
    calculaTotal();
  };

  // Autocomplete para los Clasificaciones.
  var autocompleteProdProducir = function() {
   $("#prod_producir").autocomplete({
      source: function (request, response) {
        if (isEmpresaSelected()) {
          $.ajax({
            url: base_url + 'panel/compras_ordenes/ajax_producto/',
            dataType: 'json',
            data: {
              term : request.term,
              ide: $('#empresaId').val(),
              id_almacen: $('#id_almacen').val(),
              tipo: 'p'
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
        var $prod_producir = $(this),
        $prod_producirId   = $('#id_prod_producir'),

        selectedClasif = ui.item;
        $prod_producir.css("background-color", "#B6E7FF");
        $prod_producirId.val(ui.item.id);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        selectedClasif = null;
        $(this).css("background-color", "#FDFC9A");
        $('#id_prod_producir').val('');
      }
    });
  };

  // // Autocomplete para los trabajadores.
  // var autocompleteTrabajador = function () {
  //   $("#ftrabajador").autocomplete({
  //     source: base_url + 'panel/usuarios/ajax_get_usuarios/?empleados=si',
  //     minLength: 1,
  //     selectFirst: true,
  //     select: function( event, ui ) {
  //       $("#fid_trabajador").val(ui.item.id);
  //       $("#ftrabajador").val(ui.item.label).css({'background-color': '#99FF99'});
  //     }
  //   }).keydown(function(e){
  //     if (e.which === 8) {
  //       $(this).css({'background-color': '#FFD9B3'});
  //       $('#fid_trabajador').val('');
  //     }
  //   });
  // };

  // Autocomplete para el codigo.
  var selectedClasif = null,
  autocompleteConcepto = function () {
    $("#fconcepto").autocomplete({
      source: function (request, response) {
        if (isEmpresaSelected()) {
          $.ajax({
            url: base_url + 'panel/compras_ordenes/ajax_producto/',
            dataType: 'json',
            data: {
              term : request.term,
              ide: $('#empresaId').val(),
              id_almacen: $('#id_almacen').val(),
              tipo: 'p'
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
        var $fconcepto    = $(this),
            $fcodigo          = $('#fcodigo'),
            $fconceptoId      = $('#fconceptoId'),
            $fcantidad        = $('#fcantidad'),
            $ftipoproducto    = $("#ftipoproducto"),
            $fprecio_unitario = $("#fprecio_unitario");

        selectedClasif = ui.item;
        $fconcepto.css("background-color", "#B6E7FF");
        $fcodigo.val(ui.item.item.codigo);
        $fconceptoId.val(ui.item.id);
        $ftipoproducto.val(ui.item.item.tipo_familia);
        $fprecio_unitario.val(ui.item.item.precio_unitario);
        $fcantidad.val('1');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        selectedClasif = null;
        $(this).css("background-color", "#FDFC9A");
        $("#fcodigo").val("");
        $('#fconceptoId').val('');
        $('#fcantidad').val('');
        $("#ftipoproducto").val('');
        $("#fprecio_unitario").val('');
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
          $.get(base_url + 'panel/compras_ordenes/ajax_producto_by_codigo/?ide=' + $('#empresaId').val() + '&cod=' + $codigo.val() + '&tipo=p', function(data) {
            if (data.length > 0) {

              producto = {
                'codigo': data[0].codigo,
                'concepto': data[0].nombre,
                'id': data[0].id_producto,
                'cantidad': '1',
                'tipo_prod': data[0].tipo_familia,
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
  }

  var eventBtnAddProducto = function () {
    $('#btnAddProd').on('click', function(event) {
      var $fcodigo     = $('#fcodigo').css({'background-color': '#FFF'}),
          $fconcepto   = $('#fconcepto').css({'background-color': '#FFF'}),
          $fconceptoId = $('#fconceptoId'),
          $fcantidad   = $('#fcantidad').css({'background-color': '#FFF'}),
          $ftipoproducto = $("#ftipoproducto"),
          $fprecio_unitario = $("#fprecio_unitario"),

          campos = [$fcantidad],
          producto,
          error = false;

      // Recorre los campos para verificar si alguno esta vacio. Si existen
      // campos vacios entonces los pinta de amarillo y manda una alerta.
      for (var i in campos) {
        if (campos[i].val() === '') {
          campos[i].css({'background-color': '#FDFC9A'})
          error = true;
        } else {
          campos[i].css({'background-color': '#FFF'})
        }
      }

      // Si el tipo de orden es producto entonces verifica si se selecciono
      // un producto, si no no deja agregar descripciones.
      if ($fconceptoId.val() == '' || $ftipoproducto.val() == '') {
        $fconcepto.css({'background-color': '#FDFC9A'});
        error = true;
      }

      // Valida si el campo cantida es 0.
      if ($fcantidad.val() === '0') {
        $fcantidad.css({'background-color': '#FDFC9A'})
        error = true;
      }

      // Si no hubo un error, es decir que no halla faltado algun campo de
      // completar.

      if ( ! error) {
        producto = {
          'codigo': $fcodigo.val(),
          'concepto': $fconcepto.val(),
          'id': $fconceptoId.val(),
          'cantidad': $fcantidad.val(),
          'tipo_prod': $ftipoproducto.val(),
          'precio_unitario': $fprecio_unitario.val(),
          'selectedClasif': selectedClasif,
        };

        addProducto(producto);

        // Recorre los campos para limpiarlos.
        for (var i in campos) {
          campos[i].val('').css({'background-color': '#FFF'});
        }

        $fconcepto.val('').css({'background-color': '#FFF'}).focus();
        $fconceptoId.val('').css({'background-color': '#FFF'});
        $fcodigo.val('');
      } else {
        noty({"text": 'Los campos marcados son obligatorios.', "layout":"topRight", "type": 'error'});
        $fconcepto.focus();
      }
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

  var eventCantKeypress = function () {
    $('#fcantidad').on('keypress', function(event) {
      if (event.which === 13) {
        $('#btnAddProd').click();
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
        indexJump = jumpIndex,
        exist     = false,
        inveario  = 0;
    if ( ! exist) {
      // inveario  = (parseFloat(producto.selectedClasif.item.inventario.saldo_anterior)+
      //             parseFloat(producto.selectedClasif.item.inventario.entradas)-
      //             parseFloat(producto.selectedClasif.item.inventario.salidas));
      inveario  = producto.selectedClasif.item.inventario;
      $trHtml = $('<tr>' +
                  '<td>'+
                    producto.selectedClasif.item.unidad+
                    '<input type="hidden" name="tipoProducto[]" id="tipoProducto" value="'+producto.tipo_prod+'">'+
                    '<input type="hidden" name="precioUnit[]" id="precioUnit" value="'+(producto.precio_unitario||'0')+'">'+
                    '<input type="hidden" name="unidad[]" id="unidad" value="'+producto.selectedClasif.item.unidad+'">'+
                  '</td>'+
                  '<td>' +
                    producto.concepto +
                    '<input type="hidden" name="concepto[]" value="'+producto.concepto+'" id="concepto" class="span12">' +
                    '<input type="hidden" name="productoId[]" value="'+producto.id+'" id="productoId" class="span12">' +
                    '<input type="hidden" name="inventario[]" value="'+inveario+'" id="inventario" class="span12">' +
                  '</td>' +
                  '<td>' +
                    inveario +
                  '</td>' +
                  '<td style="width: 150px;">' +
                      '<input type="number" step="any" name="cantidad[]" value="'+producto.cantidad+'" id="cantidad" class="span12 vpositive jump'+jumpIndex+'" min="0.01" data-next="jump'+(++jumpIndex)+'">' +
                  '</td>' +
                  '<td style="width: 85px;">' +
                      '<input type="number" step="any" name="importe[]" value="'+(parseFloat(producto.cantidad)||0)*(parseFloat(producto.precio_unitario)||0)+'" id="importe" class="span12 vpositive jump'+jumpIndex+'" min="0.01" data-next="jump'+(++jumpIndex)+'" readonly>' +
                  '</td>' +
                  '<td style="width: 35px;"><button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button></td>' +
                '</tr>');

      $($trHtml).appendTo($tabla.find('tbody'));

      for (i = indexJump, max = jumpIndex; i <= max; i += 1) {
        $.fn.keyJump.setElem($('.jump'+i));
      }

      $(".vnumeric").numeric(); //numero
      $(".vinteger").numeric({ decimal: false }); //Valor entero
      $(".vpositive").numeric({ negative: false }); //Numero positivo
      $(".vpos-int").numeric({ decimal: false, negative: false }); //Numero entero positivo

      calculaTotal();
    }
  }

  var calculaProductoTotal = function ($tr) {
    var total = (parseFloat($tr.find('#cantidad').val())||0)*(parseFloat($tr.find('#precioUnit').val())||0);

    $tr.find('#importe').val(total);
    calculaTotal();
  }

  var calculaTotal = function () {
    var total = 0;
    $("#table-productos #importe").each(function(index, el) {
      total += parseFloat($(this).val())||0;
    });

    $("#costo_materiap").val(total);
    $("#costo").val( (total+(parseFloat($("#costo_adicional").val())||0)) );
  }

  // var copyCodigoAll = function() {
  //   $("#chkcopydatos").on('click', function(event) {
  //     var obj = $("#table-productos tbody tr:first"),
  //     codigo = obj.find('#codigoArea'),
  //     codigoid = obj.find('#codigoAreaId');
  //     $("#table-productos tbody tr").each(function(index, el) {
  //       $(this).find('#codigoArea').val(codigo.val()).css('background-color', codigo.css('background-color'));
  //       $(this).find('#codigoAreaId').val(codigoid.val()).css('background-color', codigo.css('background-color'));
  //     });
  //   });
  // }

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