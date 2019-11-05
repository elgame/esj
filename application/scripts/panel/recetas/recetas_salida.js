(function (closure) {
  closure($, window);
})(function ($, window) {
  var opcClear = {formula: false, datos: false};

  $(function(){
    $('#form').keyJump();

    // autocompleteFormulas();
    // autocompleteCultivo();
    // autocompleteRanchos();
    // autocompleteCentroCosto();
    // autocompleteAutorizo();
    // autocompleteEmpresas();
    // autocompleteConcepto();

    // eventChangeTipo();
    // eventCalcuDatos();
    // eventBtnAddProducto();
    eventCargasLts();
    eventBtnDelProducto();
    eventCantidadProd();
    calculaCantidadCarga();

    opcClear.datos = opcClear.formula = true;
    // $('#tipo').change();
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

        opcClear.datos = true;
        opcClear.formula = false;
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

        calcTotalHecPlant();
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
          '<input type="hidden" name="centroCostoHec[]" class="centroCostoHec" value="'+(parseFloat(item.item.hectareas)||0)+'">'+
          '<input type="hidden" name="centroCostoNoplantas[]" class="centroCostoNoplantas" value="'+(parseFloat(item.item.no_plantas)||0)+'">'+
          '</li>');
      } else {
        noty({"text": 'Ya esta agregada el Centro de costo.', "layout":"topRight", "type": 'error'});
      }
    };

    $('#tagsCCIds').on('click', 'li:not(.disable)', function(event) {
      $(this).remove();
      calcTotalHecPlant();
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

  var autocompleteAutorizo = function () {
    $("#solicito").autocomplete({
      source: base_url + 'panel/usuarios/ajax_get_usuarios/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $solicito =  $(this);

        $solicito.css("background-color", "#A1F57A");
        $("#solicitoId").val(ui.item.id);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {$("#solicito").css("background-color", "#FFD071");
        $("#solicitoId").val('');
      }
    });

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


  /*********************************************
   * Eventos
   */

  var eventChangeTipo = function () {
    $('#tipo').on('change', function(event) {
      var tipo = $(this).find('option:selected').val();
      var ide = $('#empresaId').val();

      if ($('.modificar-receta').length == 0) {
        $.get(base_url + 'panel/recetas/ajax_get_folio/?tipo='+tipo+'&ide='+ide , function(folio) {
          $('#folio').val(folio);
        });

        // Acomoda los campos de acuerdo al tipo de receta, se limpian los campos
        if (opcClear.formula) {
          $('#formulaId, #formula, #folio_formula, #area, #areaId, #rancho, #centroCosto').val('');
          $('#tagsRanchoIds, #tagsCCIds').html('');
        }
        if (opcClear.datos) {
          $('.datoskl').val('');
          $('tbody.bodyproducs .rowprod').remove();
          calculaTotal();
        }
      } else {
        $('#form').removeClass('modificar-receta');
      }

      // Acomoda los campos de acuerdo al tipo de receta
      $('#no_plantas').show();
      $(".datos-lts, .datos-kg").hide();
      $(".datos-"+tipo).show();
      if (tipo === 'kg') {
        $('#ha_neta').removeAttr('readonly');
        $('#no_plantas').attr('readonly', 'readonly');
        $('.titulo-box-kglts').text('Datos Kg');
        $('.tipostyle').hide();
      } else {
        $('#no_plantas').removeAttr('readonly');
        $('#ha_neta, #carga1, #carga2').attr('readonly', 'readonly');
        $('.titulo-box-kglts').text('Datos Lts');
        $('.tipostyle').show();
      }

      opcClear.datos = opcClear.formula = true;
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
    $('#dosis_planta, #ha_bruta, #planta_ha, #ha_neta, #no_plantas, #carga1, #carga2, #dosis_equipo').on('keyup', function(event) {
      var $tipo          = $('#tipo'),
      $dosis_planta      = $('#dosis_planta'),
      $planta_ha         = $('#planta_ha'),
      $ha_neta           = $('#ha_neta'),
      $no_plantas        = $('#no_plantas'),
      $kg_totales        = $('#kg_totales'),
      $dosis_equipo      = $('#dosis_equipo'),
      $carga1            = $('#carga1'),
      $carga2            = $('#carga2'),
      $dosis_equipo_car2 = $('#dosis_equipo_car2')
      ;

      if ($tipo.val() === 'kg') {
        no_plantas = (parseFloat($ha_neta.val())||0) * (parseFloat($planta_ha.val())||0);
        $no_plantas.val(no_plantas);
        kg_totales = (parseFloat(no_plantas)||0) * (parseFloat($dosis_planta.val())||0);
        $kg_totales.val(kg_totales);
      } else {
        ha_neta = (parseFloat($no_plantas.val())||0)/((parseFloat($planta_ha.val())||1)>0? (parseFloat($planta_ha.val())||1): 1);
        $ha_neta.val(ha_neta.toFixed(2));

        // Separa decimales para las cargas
        let cargas = ha_neta.toFixed(2).split('.');
        $carga1.val(cargas[0]);
        if (cargas.length > 1) {
          $carga2.val("0."+cargas[1]);
        }

        lts_cargas2 = (parseFloat($dosis_equipo.val())||0)*(parseFloat($carga2.val())||0);
        $dosis_equipo_car2.val(lts_cargas2.toFixed(2));
      }

      calculaTotal();
    });
  };



  var jumpIndex = 0;
  function addProducto(producto, idval) {
    var $tabla            = $('#productos #table-productos'),
        trHtml            = '',
        $tipo             = $('#tipo'),
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
      tipostyle = $tipo.val() === 'kg'? 'display:none;': '';

      $trHtml = $(
        '<tr class="rowprod">'+
          '<td style="width: 50px;">'+
            '<span class="percent"></span>'+
            '<input type="hidden" name="percent[]" value="" id="percent" class="jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'">'+
          '</td>'+
          '<td>'+
            producto.concepto+
            '<input type="hidden" name="concepto[]" value="'+producto.concepto+'" id="concepto" class="span12">'+
            '<input type="hidden" name="productoId[]" value="'+producto.id+'" id="productoId" class="span12">'+
          '</td>'+
          '<td style="width: 80px;">'+
              '<input type="number" step="any" name="cantidad[]" value="'+producto.cantidad+'" id="cantidad" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0">'+
          '</td>'+
          '<td style="width: 80px;'+tipostyle+'">'+
              '<input type="number" step="any" name="pcarga1[]" value="'+($tipo.val() === 'lts'? producto.cantidad: '')+'" id="pcarga1" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0" readonly>'+
          '</td>'+
          '<td style="width: 80px;'+tipostyle+'">'+
              '<input type="number" step="any" name="pcarga2[]" value="'+(producto['carga2']? producto.carga2: '')+'" id="pcarga2" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0" readonly>'+
          '</td>'+
          '<td style="width: 130px;">'+
              '<input type="number" step="any" name="aplicacion_total[]" value="'+(producto['aplicacion_total']? producto.aplicacion_total: '')+'" id="aplicacion_total" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0" readonly>'+
          '</td>'+
          '<td style="width: 130px;">'+
              '<input type="number" step="any" name="precio[]" value="'+(producto['precio']? producto.precio: '')+'" id="precio" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0">'+
          '</td>'+
          '<td style="width: 150px;">'+
              '<input type="number" step="any" name="importe[]" value="'+(producto['importe']? producto.importe: '')+'" id="importe" class="span12 vpositive jump'+jumpIndex+'" data-next="jump'+(++jumpIndex)+'" min="0" readonly>'+
          '</td>'+
          '<td style="width: 50px;">'+
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

  var eventCargasLts = function () {
    $('#carga_salida').on('keyup', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parents("tr.rowprod");

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaCantidadCarga();
      }
    }).on('change', function(event) {
      var $tr = $(this).parents("tr.rowprod");
      calculaCantidadCarga();
    });
  };

  function calculaTotal () {
    var $tipo = $('#tipo'),
    total_percent = 0,
    total_cantidad = 0,
    total_aplicacion = 0,
    total_importe = 0;

     $('#productos input#cantidad').each(function(i, e) {
        total_cantidad += (parseFloat($(this).val())||0);
     });

    var $tr = undefined;
    $('#productos tr.rowprod').each(function(i, e) {
      $tr = $(this);

      // Calculos de acuerdo al tipo de receta
      if ($tipo.val() === 'kg') {
        cantidad = (parseFloat($tr.find('#cantidad').val())||0);
        importe = cantidad*(parseFloat($tr.find('#precio').val())||0);
        $tr.find('#importe').val(importe.toFixed(2));

        saldo = (parseFloat($tr.find('#aplicacion_total_saldo').attr('data-saldo'))||0) - cantidad;
        $tr.find('#aplicacion_total_saldo').val(saldo.toFixed(2));
      } else { // lts
        cantidad = (parseFloat($tr.find('#cantidad').val())||0);
        importe = cantidad*(parseFloat($tr.find('#precio').val())||0);
        $tr.find('#importe').val(importe.toFixed(2));

        saldo = (parseFloat($tr.find('#aplicacion_total_saldo').attr('data-saldo'))||0) - cantidad;
        $tr.find('#aplicacion_total_saldo').val(saldo.toFixed(2));
      }

      total_importe += (importe? importe: 0);
    });

    // $('#ttpercent').text(total_percent.toFixed(2));
    // $('#ttcantidad').text(total_cantidad.toFixed(2));
    // $('#ttaplicacion_total').text(total_aplicacion.toFixed(2));
    $('#ttimporte').text(total_importe.toFixed(2));
    $('#total_importe').val(total_importe.toFixed(2));
  }

  function calculaCantidadCarga() {
    var $tipo = $('#tipo');
    var $tr = undefined;
    $('#productos tr.rowprod').each(function(i, e) {
      $tr = $(this);

      // Calculos de acuerdo al tipo de receta
      if ($tipo.val() === 'kg') {
      } else { // lts
        carga_salida = (parseFloat($('#carga_salida').val())||0);
        carga1       = (parseFloat($tr.find('#pcarga1').val())||0);
        cantidad     = carga1*carga_salida;
        $tr.find('#cantidad').val(parseFloat(cantidad.toFixed(4)).toString());
      }
    });
    calculaTotal();
  }

  function calcTotalHecPlant() {
    var $tipo     = $('#tipo'),
      $ha_bruta   = $('#ha_bruta'),
      $no_plantas = $('#no_plantas'),
      $ha_neta    = $('#ha_neta'),
      ha_netas    = 0,
      ha_bruta    = 0,
      no_plantas  = 0;

    // Calculos de acuerdo al tipo de receta
    if ($tipo.val() === 'kg') {
      $('#tagsCCIds li').each(function(index, el) {
        var $li = $(this);
        ha_netas += (parseFloat($li.find('.centroCostoHec').val())||0);
      });

      $ha_neta.val(ha_netas.toFixed(2));

      $ha_neta.keyup();
    } else { // lts
      $('#tagsCCIds li').each(function(index, el) {
        var $li = $(this);
        ha_bruta += (parseFloat($li.find('.centroCostoHec').val())||0);
        no_plantas += (parseFloat($li.find('.centroCostoNoplantas').val())||0);
      });

      $ha_bruta.val(ha_bruta.toFixed(2));
      $no_plantas.val(no_plantas.toFixed(0));

      $ha_bruta.keyup();
    }
  }



  // Regresa true si esta seleccionada una empresa si no false.
  var isEmpresaSelected = function () {
    return $('#empresaId').val() !== '';
  };

});
