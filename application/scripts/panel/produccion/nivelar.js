(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();

    autocompleteEmpresas();
    autocompleteClasificaciones();

    eventBtnAddProducto();
    eventBtnDelProducto();
    eventCantKeypress();

    $("#id_almacen").change(function(event) {
      var $table = $('#table-productos');
      $table.find('button#btnDelProd').each(function(index, el) {
        $(this).click();
      });
    });

  });

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

        if ($("#empresaId").val() != ui.item.id) {
          $('#table-productos tbody tr').remove();
          calculaTotal();
        }
        $empresa.val(ui.item.id);
        $("#empresaId").val(ui.item.id);
        $empresa.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#empresa").css("background-color", "#FFD071");
        $("#empresaId").val('');
      }
    });
  };

  // Autocomplete para el codigo.
  var selectedClasif = null,
  autocompleteClasificaciones = function () {
    $("#fconcepto").autocomplete({
      source: function(request, response) {
        $.ajax({
          url: base_url+'panel/facturacion/ajax_get_clasificaciones/',
          dataType: "json",
          data: {
            inventario  : 't',
            term        : request.term,
            did_empresa : $("#empresaId").val(),
            dempresa    : $("#empresa").val(),
          },
          success: function(data) {
            response(data);
          }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $fconcepto    = $(this),
            $fconceptoId      = $('#fconceptoId'),
            $fcantidad        = $('#fcantidad'),
            $fprecio_unitario = $("#fprecio_unitario");
            $fexistencia      = $("#fexistencia");

        selectedClasif = ui.item;
        $fconcepto.css("background-color", "#B6E7FF");
        $fconceptoId.val(ui.item.id);
        $fprecio_unitario.val(ui.item.item.inventario.costo_prom);
        $fexistencia.val(ui.item.item.inventario.existencia)
        $fcantidad.val('');
        eventNuevaExistencia();
      }
    }).keydown(function(event){
        if(event.which == 8 || event.which == 46) {
          selectedClasif = null;
          $(this).css("background-color", "#FDFC9A");
          $('#fconceptoId').val('');
          $('#fcantidad').val('');
          $("#fprecio_unitario").val('');
          $("#fexistencia").val('');
        }
    });

    $('#fnewexistencia').on('keyup', eventNuevaExistencia);
  };

  /*
   |------------------------------------------------------------------------
   | Events
   |------------------------------------------------------------------------
   */
  var eventNuevaExistencia = function (event = null) {
    if (event) {
      event.preventDefault();
    }
    var existencia = (parseFloat($("#fexistencia").val())||0) - (parseFloat($("#fnewexistencia").val())||0);
    $('#tipo_movimiento').val('');
    if (existencia > 0) {
      $('#tipo_movimiento').val('f');
    } else if (existencia < 0) {
      $('#tipo_movimiento').val('t');
    }
    $('#fcantidad').val( Math.abs(existencia) );
  };

  var eventBtnAddProducto = function () {
    $('#btnAddProd').on('click', function(event) {
      var $fconcepto      = $('#fconcepto').css({'background-color': '#FFF'}),
        $fconceptoId      = $('#fconceptoId'),
        $fcantidad        = $('#fcantidad').css({'background-color': '#FFF'}),
        $tipo_movimiento  = $("#tipo_movimiento"),
        $fprecio_unitario = $("#fprecio_unitario").css({'background-color': '#FFF'}),
        $fexistencia      = $("#fexistencia").css({'background-color': '#FFF'}),
        $fnewexistencia   = $("#fnewexistencia").css({'background-color': '#FFF'}),

          campos = [$fcantidad, $fprecio_unitario, $fexistencia, $fnewexistencia],
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
      if ($fconceptoId.val() == '') {
        $fconcepto.css({'background-color': '#FDFC9A'});
        error = true;
      }

      // // Valida si el campo cantida es 0.
      // if ($fcantidad.val() === '0') {
      //   $fcantidad.css({'background-color': '#FDFC9A'})
      //   error = true;
      // }

      // Si no hubo un error, es decir que no halla faltado algun campo de
      // completar.

      if ( ! error) {
        producto = {
          'concepto'        : $fconcepto.val(),
          'id'              : $fconceptoId.val(),
          'tipo_movimiento' : $tipo_movimiento.val(),
          'precio_unitario' : $fprecio_unitario.val(),
          'cantidad'        : $fcantidad.val(),
          'existencia'      : $fexistencia.val(),
          'newexistencia'   : $fnewexistencia.val(),
          'importe'         : ((parseFloat($fprecio_unitario.val())||0) * (parseFloat($fcantidad.val())||0)),
          'selectedClasif'  : selectedClasif,
        };

        addProducto(producto);

        // Recorre los campos para limpiarlos.
        for (var i in campos) {
          campos[i].val('').css({'background-color': '#FFF'});
        }
        $fconcepto.val('').css({'background-color': '#FFF'}).focus();
        $fconceptoId.val('').css({'background-color': '#FFF'});
        $tipo_movimiento.val('');
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
    $('#fnewexistencia').on('keypress', function(event) {
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
      inveario  = producto.selectedClasif.item.inventario;
      $trHtml = $('<tr>' +
                  '<td>' +
                    producto.concepto +
                    '<input type="hidden" name="concepto[]" value="'+producto.concepto+'" id="concepto" class="span12">' +
                    '<input type="hidden" name="productoId[]" value="'+producto.id+'" id="productoId" class="span12">' +
                    '<input type="hidden" name="cantidad[]" value="'+producto.cantidad+'" id="cantidad" class="span12">' +
                    '<input type="hidden" name="existencia[]" value="'+producto.existencia+'" id="existencia" class="span12">' +
                    '<input type="hidden" name="newexistencia[]" value="'+producto.newexistencia+'" id="newexistencia" class="span12">' +
                    '<input type="hidden" name="tipoMovimiento[]" id="tipoMovimiento" value="'+producto.tipo_movimiento+'">'+
                    '<input type="hidden" name="precioUnit[]" id="precioUnit" value="'+(producto.precio_unitario||'0')+'">'+
                    '<input type="hidden" name="importe[]" id="importe" value="'+producto.importe+'">'+
                  '</td>' +
                  '<td>' + producto.existencia + '</td>' +
                  '<td>' + producto.newexistencia + '</td>' +
                  '<td>' + producto.cantidad + '</td>' +
                  '<td>' + producto.precio_unitario + '</td>' +
                  '<td>' + producto.importe + '</td>' +
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

  var calculaTotal = function () {
    var total = 0;
    $("#table-productos #importe").each(function(index, el) {
      total += parseFloat($(this).val())||0;
    });

    $("#costo").val( total );
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