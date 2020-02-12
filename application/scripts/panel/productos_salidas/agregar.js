(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();

    autocompleteEmpresas();
    autocompleteTrabajador();
    autocompleteConcepto();
    autocompleteCultivo();
    autocompleteRanchos();
    autocompleteCentroCosto();
    autocompleteActivos();
    autocompleteLabores();

    eventOnChangeTipo();
    eventCodigoBarras();
    eventBtnAddProducto();
    eventBtnDelProducto();
    eventCantKeypress();
    eventChangeTraspaso();

    copyCodigoAll();
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
  };

  // Autocomplete para los trabajadores.
  var autocompleteTrabajador = function () {
    $("#ftrabajador").autocomplete({
      source: base_url + 'panel/usuarios/ajax_get_usuarios/?empleados=si',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#fid_trabajador").val(ui.item.id);
        $("#ftrabajador").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        $('#fid_trabajador').val('');
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
              tipo: ''
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

        $fconcepto.css("background-color", "#B6E7FF");
        $fcodigo.val(ui.item.item.codigo);
        $fconceptoId.val(ui.item.id);
        $ftipoproducto.val(ui.item.item.tipo_familia);
        $fprecio_unitario.val(ui.item.item.precio_unitario);
        $fcantidad.val('1');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $(this).css("background-color", "#FDFC9A");
        $("#fcodigo").val("");
        $('#fconceptoId').val('');
        $('#fcantidad').val('');
        $("#ftipoproducto").val('');
        $("#fprecio_unitario").val('');
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
        $('#tagsRanchoIds').html('');
        $("#rancho").val('').css("background-color", "#FFD071");
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

        params.tipo = ['gasto', 'melga', 'tabla', 'seccion', 'costosventa', 'servicio'];

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

  var autocompleteLabores = function () {
    $('#cimplemento').autocomplete({
      source: base_url+'panel/control_maquinaria/ajax_get_implemento/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this);

        $this.css("background-color", "#B0FFB0");
      }
    }).keydown(function(event){
      if(event.which == 8 || event == 46) {
        var $this = $(this);

        $this.css("background-color", "#FFD9B3");
      }
    });

    $('#datosCombustible').on('focus', 'input#clabor:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/labores_codigo/ajax_get_labores/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");
          $('#clabor_id').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $this = $(this), $tr = $this.parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $('#clabor_id').val('');
        }
      });
    });
  };

  /*
   |------------------------------------------------------------------------
   | Events
   |------------------------------------------------------------------------
   */
  var tipoOrderActual = $('#tipo').find('option:selected').val();
  var eventOnChangeTipo = function () {
    $('#tipo').on('change', function(event) {
      var $this      = $(this),
          $folio     = $('#folio'),
          $tableProd = $('#table-productos');

      if ($tableProd.find('tbody tr').length > 0) {
        noty({"text": 'Ya tiene productos para un tipo de salida, si desea cambiar de tipo elimine los productos del listado', "layout":"topRight", "type": 'error'});

        $this.val(tipoOrderActual);
      } else {
        tipoOrderActual = $this.find('option:selected').val();
        if(tipoOrderActual == 'r') {
          $("#generalCodigo").show();
          $("#datosCombustible").hide();
        } else if(tipoOrderActual == 'c') {
          $("#datosCombustible").show();
          $("#generalCodigo").hide();
        } else {
          $("#generalCodigo").hide();
        }
      }
    });

    // $('#tipo').change();
  };

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

  var eventChangeTraspaso = function () {
    $('#tid_almacen').change(function(event) {
      $('#groupCatalogos').show();
      if ($(this).val() != '') {
        $('#groupCatalogos').hide();
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
                  // '<td style="width: 60px;">'+
                    // '<input type="text" name="codigoArea[]" value="" id="codigoArea" class="span12 showCodigoAreaAuto" required>'+
                    // '<input type="hidden" name="codigoAreaId[]" value="" id="codigoAreaId" class="span12" required>'+
                    // '<input type="hidden" name="codigoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12">'+
                    // '<i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>'+
                  // '</td>'+
                  '<td style="width: 70px;">' +
                    '<input type="hidden" name="tipoProducto[]" value="'+producto.tipo_prod+'">'+
                    '<input type="hidden" name="precioUnit[]" value="'+(producto.precio_unitario||'0')+'">'+
                    producto.codigo +
                    '<input type="hidden" name="codigo[]" value="'+producto.codigo+'" class="span12">' +
                  '</td>' +
                  '<td>' +
                    producto.concepto +
                    '<input type="hidden" name="concepto[]" value="'+producto.concepto+'" id="concepto" class="span12">' +
                    '<input type="hidden" name="productoId[]" value="'+producto.id+'" id="productoId" class="span12">' +
                  '</td>' +
                  '<td style="width: 65px;">' +
                      '<input type="number" step="any" name="cantidad[]" value="'+producto.cantidad+'" id="cantidad" class="span12 vpositive jump'+jumpIndex+'" min="0.01" data-next="jump'+(++jumpIndex)+'">' +
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

  var copyCodigoAll = function() {
    $("#chkcopydatos").on('click', function(event) {
      var obj = $("#table-productos tbody tr:first"),
      codigo = obj.find('#codigoArea'),
      codigoid = obj.find('#codigoAreaId');
      $("#table-productos tbody tr").each(function(index, el) {
        $(this).find('#codigoArea').val(codigo.val()).css('background-color', codigo.css('background-color'));
        $(this).find('#codigoAreaId').val(codigoid.val()).css('background-color', codigo.css('background-color'));
      });
    });
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