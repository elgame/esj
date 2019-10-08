(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();

    autocompleteFormulas();
    autocompleteCultivo();
    autocompleteRanchos();
    autocompleteCentroCosto();
    autocompleteEmpresas();
    autocompleteConcepto();

    eventChangeTipo();
    eventCalcuDatos();
    eventBtnAddProducto();
    eventBtnDelProducto();
    eventCantidadProd();

    $('#tipo').change();
  });

  /*
   |------------------------------------------------------------------------
   | Autocompletes
   |------------------------------------------------------------------------
   */

  var autocompleteFormulas = function () {
    $("#formula").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
        params.tipo = $("#tipo").val();
        $.ajax({
            url: base_url + 'panel/recetas_formulas/ajax_get_formulas/',
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
        var $formula =  $(this);

        $("#formulaId").val(ui.item.id);
        $('#folio_formula').val(ui.item.item.folio);
        $('#area').val(ui.item.item.area);
        $('#areaId').val(ui.item.item.id_area);
        $('#tipo').change();
        $formula.css("background-color", "#A1F57A");

        // agrega los productos
        $.ajax({
            url: base_url + 'panel/recetas_formulas/ajax_get_formula/',
            dataType: "json",
            data: {id: ui.item.id},
            success: function(data) {
              if (data.info.productos) {
                var producto = {};
                for (var i = 0; i < data.info.productos.length; i++) {
                  producto = {
                    id: data.info.productos[i].id_producto,
                    concepto: data.info.productos[i].producto,
                    cantidad: data.info.productos[i].dosis_mezcla,
                    precio: data.info.productos[i].precio_unitario,
                    percent: data.info.productos[i].precio,
                  };
                  addProducto(producto, '');
                }
              }
            }
        });
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#formula").css("background-color", "#FFD071");
        $("#formulaId").val('');
        $('#folio_formula, #area, #areaId, #rancho, #centroCosto').val('');
        $('#tagsRanchoIds, #tagsCCIds').html('');
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

        params.tipo = ['melga', 'tabla', 'seccion'];

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
        $('#productos #fprecio').val(ui.item.item.last_precio);
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
      }
    });
  };

  var eventChangeTipo = function () {
    $('#tipo').on('change', function(event) {
      var tipo = $(this).find('option:selected').val();
      var ide = $('#empresaId').val();
      $.get(base_url + 'panel/recetas/ajax_get_folio/?tipo='+tipo+'&ide='+ide , function(folio) {
        $('#folio').val(folio);
      });

      // Acomoda los campos de acuerdo al tipo de receta
      $('.datoskl').val('');
      $(".datos-lts, .datos-kg").hide();
      $(".datos-"+tipo).show();
      if (tipo === 'kg') {
        $('#ha_neta').removeAttr('readonly');
        $('#no_plantas').attr('readonly', 'readonly');
        $('.titulo-box-kglts').text('Datos Kg');
      } else {
        $('#no_plantas').removeAttr('readonly');
        $('#ha_neta').attr('readonly', 'readonly');
        $('.titulo-box-kglts').text('Datos Lts');
      }
    });
  }

  var eventBtnAddProducto = function () {
    $('#productos #btnAddProd').on('click', function(event) {
      var idval = $(this).parents("div[id^=productos]").attr('id').replace("productos", ""),
          $fcodigo       = $('#productos #fcodigo').css({'background-color': '#FFF'}),
          $fconcepto     = $('#productos #fconcepto').css({'background-color': '#FFF'}),
          $fconceptoId   = $('#productos #fconceptoId'),
          $fcantidad     = $('#productos #fcantidad').css({'background-color': '#FFF'}),
          $fprecio       = $('#productos #fprecio').css({'background-color': '#FFF'}),
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

      if ( ! error) {
        producto = {
          'id'       : $fconceptoId.val(),
          'codigo'   : $fcodigo.val(),
          'concepto' : $fconcepto.val(),
          'cantidad' : $fcantidad.val(),
          'precio'   : $fprecio.val(),
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

  var eventCalcuDatos = function () {
    $('#dosis_planta, #planta_ha, #ha_neta').on('keyup', function(event) {
      var $tipo     = $('#tipo'),
      $dosis_planta = $('#dosis_planta'),
      $planta_ha    = $('#planta_ha'),
      $ha_neta      = $('#ha_neta'),
      $no_plantas   = $('#no_plantas'),
      $kg_totales   = $('#kg_totales');

      if ($tipo.val() === 'kg') {
        no_plantas = (parseFloat($ha_neta.val())||0) * (parseFloat($planta_ha.val())||0);
        $no_plantas.val(no_plantas);
        kg_totales = (parseFloat(no_plantas)||0) * (parseFloat($dosis_planta.val())||0);
        $kg_totales.val(kg_totales);
      } else {
      }

      calculaTotal();
    });
  };



  var jumpIndex = 0;
  function addProducto(producto, idval) {
    var $tabla            = $('#productos #table-productos'),
        trHtml            = '',
        indexJump         = jumpIndex + 1,
        exist             = false,
        $autorizar_active = $("#btnAutorizar").length>0?true:false;

    // Valida si ya existe el producto agregado
    $tabla.find('input[id=productoId]').each(function(index, el) {
      if (el && $(el).val() == producto.id) {
        exist = true;
      }
    });

    // Si el producto a agregar no existe en el listado los agrega por primera
    // vez.
    if ( ! exist) {

      $trHtml = $(
        '<tr class="rowprod">'+
          '<td>'+
            '<span class="percent"></span>'+
            '<input type="hidden" name="percent[]" value="" id="percent" class="jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
          '</td>'+
          '<td>'+
            producto.concepto+
            '<input type="hidden" name="concepto[]" value="'+producto.concepto+'" id="concepto" class="span12">'+
            '<input type="hidden" name="productoId[]" value="'+producto.id+'" id="productoId" class="span12">'+
          '</td>'+
          '<td style="width: 65px;">'+
              '<input type="number" step="any" name="cantidad[]" value="'+producto.cantidad+'" id="cantidad" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0">'+
          '</td>'+
          '<td style="width: 65px;">'+
              '<input type="number" step="any" name="aplicacion_total[]" value="'+(producto['aplicacion_total']? producto.aplicacion_total: '')+'" id="aplicacion_total" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0" readonly>'+
          '</td>'+
          '<td style="width: 65px;">'+
              '<input type="number" step="any" name="precio[]" value="'+(producto['precio']? producto.precio: '')+'" id="precio" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0">'+
          '</td>'+
          '<td style="width: 65px;">'+
              '<input type="number" step="any" name="importe[]" value="'+(producto['importe']? producto.importe: '')+'" id="importe" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0" readonly>'+
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
     // console.log(total_cantidad);

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
