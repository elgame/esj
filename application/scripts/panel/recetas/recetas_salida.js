(function (closure) {
  closure($, window);
})(function ($, window) {
  var opcClear = {formula: false, datos: false};

  $(function(){
    $('#form').keyJump();

    // autocompleteEmpresas();

    eventCargasLts();
    eventBtnDelProducto();
    eventCantidadProd();
    calculaCantidadCarga();

    eventLigarBoletasSalida();
    setBoletasSel();

    opcClear.datos = opcClear.formula = true;
    // $('#tipo').change();
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


  /*********************************************
   * Eventos
   */




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
    $('#carga_salida, #plantas_salida').on('keyup', function(e) {
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
        plantas_salida = (parseFloat($('#plantas_salida').val())||0);
        plantas_saldo  = (parseFloat($('#plantas_saldo').text())||0);
        apts           = (parseFloat($tr.find('#aplicacion_total_saldo').attr('data-saldo'))||0);
        cantidad       = plantas_salida*apts/(plantas_saldo>0? plantas_saldo: 1);
        $tr.find('#cantidad').val(parseFloat(cantidad.toFixed(4)).toString());
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


  // --------------------------------
  // Buscar boletas
  function eventLigarBoletasSalida() {
    $("#show-boletasSalidas").on('click', function(event) {
      $("#filBoleta").val("");

      getBoletas(['en', 'sa', 'p', 'b']);
      $("#modal-boletas").modal('show');
      $("#modal-boletas #BtnAddBoleta").addClass('entrada');
    });

    $("#filBoleta").on('change', function(event) {
      getBoletas(['en', 'sa', 'p', 'b']);
    });
  }

  function getBoletas(accion){
    var params = {
      tipoo: 'sa',
      accion: (accion? accion: ['en', 'p', 'b']),
      filtro: $("#filBoleta").val(),
      area: 6 // 6 = insumos
    };
    $.getJSON(base_url+"panel/compras_ordenes/ajaxGetBoletas/", params, function(json, textStatus) {
      var html = '';
      for (var i in json) {
        html += '<tr class="radioBoleta" data-id="'+json[i].id_bascula+'" data-folio="'+json[i].folio+'" '+
          'data-idempresa="'+json[i].id_empresa+'" data-empresa="'+json[i].empresa+'" style="cursor: pointer;">'+
        '  <td>'+json[i].fecha+'</td>'+
        '  <td>'+json[i].folio+'</td>'+
        '  <td>'+json[i].cliente+'</td>'+
        '  <td>'+json[i].area+'</td>'+
        '</tr>';
      }
      $("#table-boletas tbody").html(html);
    });
  }

  function setBoletasSel() {
    $("#table-boletas").on('dblclick', 'tr.radioBoleta', function(event) {
      var $this = $(this);

      $('#boletasSalidasFolio').val($this.attr('data-folio'));
      $('#boletasSalidasId').val($this.attr('data-id'));

      $("#modal-boletas").modal('hide');
    });
  }


  // Regresa true si esta seleccionada una empresa si no false.
  var isEmpresaSelected = function () {
    return $('#empresaId').val() !== '';
  };

});
