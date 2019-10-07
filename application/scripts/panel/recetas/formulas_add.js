(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();

    autocompleteCultivo();
    autocompleteEmpresas();
    autocompleteConcepto();

    eventChangeTipo();
    eventBtnAddProducto();
    eventBtnDelProducto();
    eventCantidadProd();
  });

  /*
   |------------------------------------------------------------------------
   | Autocompletes
   |------------------------------------------------------------------------
   */

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

        $("#areaId").val(ui.item.id);
        $area.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#area").css("background-color", "#FFD071");
        $("#areaId").val('');
      }
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
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#empresa").css("background-color", "#FFD071");
        $("#empresaId").val('');
      }
    });
  };

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
              tipo: 'p',
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
            $fcantidad     = $('#productos #fcantidad');


        $fconcepto.css("background-color", "#B6E7FF");
        $fcodigo.val(ui.item.item.codigo);
        $fconceptoId.val(ui.item.id);
        $fcantidad.val('1');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        var $fconcepto = $(this),
        idval = $fconcepto.parents("div[id^=productos]").attr('id').replace("productos", "");

        $(this).css("background-color", "#FDFC9A");
        $("#productos #fcodigo").val("");
        $('#productos #fconceptoId').val('');
        $('#productos #fcantidad').val('');
      }
    });
  };

  var eventChangeTipo = function () {
    $('#tipo').on('change', function(event) {
      var tipo = $(this).find('option:selected').val();
      var ide = $('#empresaId').val();
      $.get(base_url + 'panel/recetas_formulas/ajax_get_folio/?tipo='+tipo+'&ide='+ide , function(folio) {
        $('#folio').val(folio);
      });
    });
  }

  var eventBtnAddProducto = function () {
    $('#productos #btnAddProd').on('click', function(event) {
      var idval = $(this).parents("div[id^=productos]").attr('id').replace("productos", ""),
          $fcodigo       = $('#productos #fcodigo').css({'background-color': '#FFF'}),
          $fconcepto     = $('#productos #fconcepto').css({'background-color': '#FFF'}),
          $fconceptoId   = $('#productos #fconceptoId'),
          $fcantidad     = $('#productos #fcantidad').css({'background-color': '#FFF'}),
          campos = [$fcantidad],
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

      if ( ! error) {
        producto = {
          'id'       : $fconceptoId.val(),
          'codigo'   : $fcodigo.val(),
          'concepto' : $fconcepto.val(),
          'cantidad' : $fcantidad.val(),
        };

        addProducto(producto, idval);

        // Recorre los campos para limpiarlos.
        for (var i in campos) {
          campos[i].val('').css({'background-color': '#FFF'});
        }

        $fconcepto.val('').css({'background-color': '#FFF'}).focus();
        $fconceptoId.val('').css({'background-color': '#FFF'});
        $fcodigo.val('');
        $("#productos #show_info_prod").show().find('span').text('');
      } else {
        noty({"text": 'Los campos marcados son obligatorios.', "layout":"topRight", "type": 'error'});
        $fconcepto.focus();
      }
    });
  };

  var jumpIndex = 0;
  function addProducto(producto, idval) {
    var $tabla            = $('#productos #table-productos'),
        trHtml            = '',
        indexJump         = jumpIndex + 1,
        exist             = false,
        $autorizar_active = $("#btnAutorizar").length>0?true:false;

    // Si el producto a agregar no existe en el listado los agrega por primera
    // vez.
    if ( ! exist) {

      $trHtml = $(
        '<tr class="rowprod">'+
          '<td>'+
            '<span class="percent"></span>'+
            '<input type="hidden" name="percent[]" value="" id="percent" class="jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
          '</td>'+
          '<td style="width: 65px;">'+
              '<input type="number" step="any" name="cantidad[]" value="'+producto.cantidad+'" id="cantidad" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0">'+
          '</td>'+
          '<td>'+
            producto.concepto+
            '<input type="hidden" name="concepto[]" value="'+producto.concepto+'" id="concepto" class="span12">'+
            '<input type="hidden" name="productoId[]" value="'+producto.id+'" id="productoId" class="span12">'+
          '</td>'+
          '<td>'+
            '<button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button>'+
          '</td>'+
        '</tr>');

      $($trHtml).appendTo($tabla.find('tbody.bodyproducs'));

      for (i = indexJump, max = jumpIndex; i <= max; i += 1) {
        $.fn.keyJump.setElem($('.jump'+i));
      }

      $(".vnumeric").numeric(); //numero
      $(".vinteger").numeric({ decimal: false }); //Valor entero
      $(".vpositive").numeric({ negative: false }); //Numero positivo
      $(".vpos-int").numeric({ decimal: false, negative: false }); //Numero entero positivo

      calculaTotal();
      // $('.jump'+indexJump).focus();
    }
  }

  var eventCantidadProd = function () {
    $('#productos #table-productos').on('keyup', '#cantidad', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parents("tr.rowprod");

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotal();
      }
    }).on('change', '#cantidad', function(event) {
      var $tr = $(this).parents("tr.rowprod");
      calculaTotal();
    });
  };

  var eventBtnDelProducto = function () {
    var $table = $('#productos #table-productos');

    $table.on('click', 'button#btnDelProd', function(event) {
      var idval = $(this).parents("div[id^=productos]").attr('id').replace("productos", ""),
      $parent = $(this).parents("tr.rowprod");
      $parent.remove();

      calculaTotal();
    });
  };

  function calculaTotal () {
    var total_cantidad = 0;

     $('#productos input#cantidad').each(function(i, e) {
        total_cantidad += (parseFloat($(this).val())||0);
     });
     console.log(total_cantidad);

    $('#productos tr.rowprod').each(function(i, e) {
      var $tr = $(this),
      total = (parseFloat($tr.find('#cantidad').val())||0)*100/(total_cantidad>0? total_cantidad: 1);
      $tr.find('#percent').val(total.toFixed(2));
      $tr.find('.percent').text(total.toFixed(2));
    });
  }



  // Regresa true si esta seleccionada una empresa si no false.
  var isEmpresaSelected = function () {
    return $('#empresaId').val() !== '';
  };

});
