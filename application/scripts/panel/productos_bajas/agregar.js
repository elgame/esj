(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();

    autocompleteEmpresas();
    autocompleteConcepto();

    eventCodigoBarras();
    eventBtnAddProducto();
    eventBtnDelProducto();
    eventCantKeypress();
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
            $fcodigo     = $('#fcodigo'),
            $fconceptoId   = $('#fconceptoId'),
            $fcantidad     = $('#fcantidad');

        $fconcepto.css("background-color", "#B6E7FF");
        $fcodigo.val(ui.item.item.codigo);
        $fconceptoId.val(ui.item.id);
        $fcantidad.val('1');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $(this).css("background-color", "#FDFC9A");
        $("#fcodigo").val("");
        $('#fconceptoId').val('');
        $('#fcantidad').val('');
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
      if ($fconceptoId.val() == '') {
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
          'cantidad': $fcantidad.val()
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

      $trHtml = $('<tr>' +
                  '<td style="width: 70px;">' +
                    producto.codigo +
                    '<input type="hidden" name="codigo[]" value="'+producto.codigo+'" class="span12">' +
                  '</td>' +
                  '<td>' +
                    producto.concepto +
                    '<input type="hidden" name="concepto[]" value="'+producto.concepto+'" id="concepto" class="span12">' +
                    '<input type="hidden" name="productoId[]" value="'+producto.id+'" id="productoId" class="span12">' +
                  '</td>' +
                  '<td style="width: 65px;">' +
                      '<input type="number" name="cantidad[]" value="'+producto.cantidad+'" id="cantidad" class="span12 vpositive jump'+jumpIndex+'" min="1" data-next="jump'+(++jumpIndex)+'">' +
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

      // $('.jump'+indexJump).focus();
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

});