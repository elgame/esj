(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();

    autocompleteEmpresas();
    autocompleteCentroCosto();

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

  var autocompleteCentroCosto = function () {
    $("#centroCosto").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};

        params.tipo = ['banco', 'gastofinanciero', 'resultado', 'creditobancario', 'otrosingresos',
          'impuestoxpagar', 'productofinanc', 'impuestoafavor'];

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
        // $centroCosto.val(ui.item.id);
        $("#centroCostoId").val(ui.item.id);
        $("#fcuentaCtp").val(ui.item.item.cuenta_cpi);
        $centroCosto.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#centroCosto").css("background-color", "#FFD071");
        $("#centroCostoId").val('');
      }
    });
  };

  /*
   |------------------------------------------------------------------------
   | Events
   |------------------------------------------------------------------------
   */
  var eventBtnAddProducto = function () {
    $('#btnAddProd').on('click', function(event) {
      var $centroCosto   = $('#centroCosto').css({'background-color': '#FFF'}),
          $centroCostoId = $('#centroCostoId').css({'background-color': '#FFF'}),
          $cuentaCtp     = $('#fcuentaCtp').css({'background-color': '#FFF'}),
          $tipo          = $('#tipo'),
          $fcantidad     = $('#fcantidad').css({'background-color': '#FFF'}),
          campos         = [$fcantidad, $centroCosto],
          producto,
          error          = false,
          msg = 'Los campos marcados son obligatorios.';

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
      if ($centroCostoId.val() == '') {
        $centroCosto.css({'background-color': '#FDFC9A'});
        error = true;
      }

      // Valida si el campo cantida es 0.
      if ($fcantidad.val() === '0') {
        $fcantidad.css({'background-color': '#FDFC9A'})
        error = true;
      }

      $("#table-productos tbody tr").each(function(index, el) {
        var $tr = $(this);
        if ($tr.find('.centroCostoId').val() === $centroCostoId.val()) {
          error = true;
          msg = 'El centro de costo ya esta agregado a los movimientos.';
        }
      });

      // Si no hubo un error, es decir que no halla faltado algun campo de
      // completar.
      if ( ! error) {
        producto = {
          'centroCosto'   : $centroCosto.val(),
          'centroCostoId' : $centroCostoId.val(),
          'cuentaCtp'     : $cuentaCtp.val(),
          'tipo'          : $tipo.val(),
          'cantidad'      : $fcantidad.val(),
        };

        addProducto(producto);

        // Recorre los campos para limpiarlos.
        for (var i in campos) {
          campos[i].val('').css({'background-color': '#FFF'});
        }
      } else {
        noty({"text": msg, "layout":"topRight", "type": 'error'});
        $centroCosto.focus();
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
        tipos     = {'t': 'Suma', 'f': 'Resta'};

    // Si el producto a agregar no existe en el listado los agrega por primera
    // vez.
    if ( ! exist) {
      $trHtml = $('<tr>' +
                  '<td>' +
                    '<input type="hidden" name="centroCosto[]" value="'+producto.centroCosto+'" class="centroCosto">'+
                    '<input type="hidden" name="centroCostoId[]" value="'+(producto.centroCostoId||'0')+'" class="centroCostoId">'+
                    producto.centroCosto +
                  '</td>' +
                  '<td>' +
                    producto.cuentaCtp +
                    '<input type="hidden" name="cuentaCtp[]" value="'+producto.cuentaCtp+'" class="span12 cuentaCtp">' +
                  '</td>' +
                  '<td>' +
                    tipos[producto.tipo] +
                    '<input type="hidden" name="tipo[]" value="'+producto.tipo+'" class="span12 tipo">' +
                  '</td>' +
                  '<td>' +
                    producto.cantidad+
                    '<input type="hidden" name="cantidad[]" value="'+producto.cantidad+'" class="span12 cantidad">' +
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
      $('#centroCosto').focus();
      calculaTotal();
    }
  }

  var calculaTotal = function() {
    var totalSuma = 0, totalResta = 0;
    $("#table-productos tbody tr").each(function(index, el) {
      var $tr = $(this);

      if ($tr.find('.tipo').val() === 't') {
        totalSuma += parseFloat($tr.find('.cantidad').val())||0;
      } else {
        totalResta += parseFloat($tr.find('.cantidad').val())||0;
      }

      $('#sumas').text(totalSuma);
      $('#restas').text(totalResta);
      $('#diferencia').text(totalSuma-totalResta);
    });
  }


});