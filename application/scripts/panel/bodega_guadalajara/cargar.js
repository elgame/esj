(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    btnAddIngreso();
    // btnAddMovimientos();
    btnDelIngreso();

    // btnAddOtros();
    // btnDelOtros();

    onChanceIngresos();

    // cargaRemisiones();
    // btnDelRemision();
    // onChangeTotalRemisiones();

    $("#btn-add-prestamos").click(agregarPrestamos);
    btnDelPrestamos();
    onChangeKeysPrestamos();
    autocompleteClasifiLive();

    onChanceDenominacionNum();
    onChanceTotalDenominacion();

    btnAddGasto();
    btnDelGasto();
    btnShowGastoCat();
    btnAddTraspaso();
    btnDelTraspaso();

    autocompleteCategorias();
    autocompleteCategoriasLive();

    onChanceImporteGastos();
    onChanceImporteTraspaso();

    $('#total-efectivo-diferencia').text(util.darFormatoNum($('#ttotal-diferencia').val()));

    // cargaMovimientos();
    searchModalMovimientos();

    $("#lista_remisiones_modal, #lista_movimientos_modal").filterTable();
  });

  var btnAddIngreso = function () {
    $('#btn-add-ingreso').on('click', function(event) {
      agregarIngreso();
    });
  };

  var btnDelIngreso = function () {
    $('#table-ingresos').on('click', '.btn-del-ingreso', function(event) {
      $(this).parents('tr').remove();
      calculaTotalIngresos();
    });
  };

  var agregarIngreso = function (movimiento) {

    var poliza = '', concepto = '', id = '', abono = '0';
    if (movimiento) {
      poliza   = movimiento.poliza;
      concepto = movimiento.proveedor;
      id       = movimiento.id;
      abono    = movimiento.total;
    }

    var $table = $('#table-ingresos').find('tbody'),
        tr =  '<tr>' +
                '<td style="width: 100px;">' +
                  '<input type="text" name="ingreso_empresa[]" value="" class="input-small gasto-cargo" style="width: 150px;" required>' +
                  '<input type="hidden" name="ingreso_empresa_id[]" value="" class="input-small vpositive gasto-cargo-id">' +
                '</td>' +
                '<td style="width: 40px;">' +
                  '<select name="ingreso_nomenclatura[]" class="ingreso_nomenclatura" style="width: 70px;">' +
                    $('#nomeclaturas_base').html() +
                  '</select>' +
                '</td>' +
                '<td style="width: 100px;"><input type="text" name="ingreso_poliza[]" value="'+poliza+'" class="ingreso_poliza span12" maxlength="100" placeholder="Poliza" style="width: 100px;"></td>' +
                '<td>' +
                  '<input type="text" name="ingreso_concepto[]" value="'+concepto+'" class="ingreso-concepto span12" maxlength="500" placeholder="Concepto" required>' +
                  '<input type="hidden" name="ingreso_concepto_id[]" value="'+id+'" class="ingreso_concepto_id span12" placeholder="Concepto">' +
                '</td>' +
                '<td style="width: 100px;"><input type="text" name="ingreso_monto[]" value="'+abono+'" class="ingreso-monto vpositive input-small" placeholder="Monto" required></td>' +
                '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-ingreso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>' +
              '</tr>';

    $(tr).appendTo($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  // var btnAddOtros = function () {
  //   $('#btn-add-otros').on('click', function(event) {
  //     agregarOtros();
  //   });
  // };

  // var btnDelOtros = function () {
  //   $('#table-remisiones').on('click', '.btn-del-otros', function(event) {
  //     $(this).parents('tr').remove();
  //     calculaTotalIngresos();
  //   });
  // };

  // var agregarRemisiones = function (remision) {
  //   var $table = $('#table-remisiones').find('tbody .row-total'),
  //       tr;

  //   var numRemision = '', folio = '', id = '', abono = '0', concepto = '';
  //   if (remision) {
  //     id           = remision.id;
  //     numRemision  = remision.numremision;
  //     abono        = remision.total;
  //     foliofactura = remision.foliofactura;
  //     concepto     = remision.concepto;
  //   }

  //   tr =  '<tr>' +
  //           '<td style="width: 100px;">' +
  //             '<input type="text" name="remision_empresa[]" value="" class="input-small gasto-cargo" style="width: 150px;" required>' +
  //             '<input type="hidden" name="remision_empresa_id[]" value="" class="input-small vpositive gasto-cargo-id">' +
  //           '</td>' +
  //           '<td style="width: 70px;"><input type="text" name="remision_numero[]" value="'+numRemision+'" class="remision-numero vpositive input-small" placeholder="" readonly style="width: 70px;"></td>' +
  //           '<td style="width: 100px;"><input type="text" name="remision_folio[]" value="'+foliofactura+'" class="remision_folio" placeholder="Folio" style="width: 100px;"></td>' +
  //           '<td>' +
  //             '<input type="text" name="remision_concepto[]" value="'+concepto+'" class="remision-concepto span12" maxlength="500" placeholder="Nombre" required>' +
  //             '<input type="hidden" name="remision_id[]" value="'+id+'" class="remision-id span12" required>' +
  //           '</td>' +
  //           '<td style="width: 100px;"><input type="text" name="remision_importe[]" value="'+abono+'" class="remision-importe vpositive input-small" placeholder="Importe" required></td>' +
  //           '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>' +
  //         '</tr>';

  //   $(tr).insertBefore($table);
  //   $(".vpositive").numeric({ negative: false }); //Numero positivo
  // };

  var calculaTotalIngresos = function () {
    var total = 0;
    $('.ingreso-monto').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });

    // $('.otros-monto').each(function(index, el) {
    //   total += parseFloat($(this).val() || 0);
    // });

    // $('.remision-importe').each(function(index, el) {
    //   total += parseFloat($(this).val() || 0);
    // });

    total = parseFloat(total.toFixed(2));

    $('#total-ingresos-ext').val(total);

    // var saldo_inicial = parseFloat($('#saldo_inicial').val()),
    //     totalSaldoIngresos =  saldo_inicial + total; //saldo_inicial +

    // $('input#total-saldo-ingresos.span12').val(totalSaldoIngresos.toFixed(2));
    // $('input#total-saldo-ingresos.vpositive').val((totalSaldoIngresos - saldo_inicial).toFixed(2));

    calculaCorte();
  };

  var onChanceIngresos = function () {
    // $('#table-ingresos, #table-otros').on('keyup', '.ingreso-monto, .otros-monto', function(e) {
    $('#table-ingresos').on('keyup', '.ingreso-monto', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalIngresos();
      }
    });
  };

  // var cargaRemisiones = function () {
  //   $('#carga-remisiones').on('click', function(event) {
  //     var $table = $('#table-remisiones').find('#table-rem-tbody'),
  //         html = '',
  //         $this;

  //     if ($('.chk-remision:checked').length > 0) {
  //       $('.chk-remision:checked').each(function(index, el) {
  //         $this = $(this);

  //         agregarRemisiones({id: $this.attr('data-id'), numremision: $this.attr('data-numremision'), total: $this.attr('data-total'), foliofactura: $this.attr('data-foliofactura'), concepto: $this.attr('data-concepto')});

  //         // html += '<tr>' +
  //         //           '<td>' +
  //         //             '<input type="text" name="remision_concepto[]" value="" class="remision-concepto span12" maxlength="500" placeholder="Observacion" required>' +
  //         //             '<input type="hidden" name="remision_id[]" value="'+$this.attr('data-id')+'" class="remision-id span12" required>' +
  //         //           '</td>' +
  //         //           '<td><input type="text" name="remision_numero[]" value="'+$this.attr('data-folio')+'" class="remision-numero vpositive input-small" placeholder="#" readonly style="width: 45px;"></td>' +
  //         //           '<td><input type="text" name="remision_importe[]" value="'+$this.attr('data-total')+'" class="remision-importe vpositive input-small" placeholder="Importe" required style="width: 55px;text-align: right;"></td>' +
  //         //           '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-remision" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>' +
  //         //         '</tr>';
  //       });

  //       // $(html).appendTo($table);
  //       calculaTotalRemisiones();

  //       $('#modal-remisiones').modal('hide');
  //     } else {
  //       noty({"text": 'Seleccione al menos una remision.', "layout":"topRight", "type": 'error'});
  //     }
  //   });
  // };

  // var btnDelRemision = function () {
  //   $('#table-remisiones').on('click', '.btn-del-remision', function(event) {
  //     $(this).parents('tr').remove();
  //     calculaTotalRemisiones();
  //   });
  // };

  // var calculaTotalRemisiones = function () {
  //   var total = 0;
  //   $('.remision-importe').each(function(index, el) {
  //     total += parseFloat($(this).val());
  //   });

  //   calculaTotalIngresos();

  //   $('#total-remisiones').text(util.darFormatoNum(total.toFixed(2)));
  // };

  // var onChangeTotalRemisiones = function () {
  //   $('#table-remisiones').on('keyup', '.remision-importe', function(e) {
  //     var key = e.which,
  //         $this = $(this),
  //         $tr = $this.parent().parent();

  //     if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
  //       calculaTotalRemisiones();
  //     }
  //   });
  // };

  // *********** Prestamos
  var autocompleteClasifiLive = function () {
    $('#table-prestamos').on('focus', 'input#prestamo_descripcion:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");
          $tr.find('#prestamo_id_prod').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#prestamo_id_prod').val('');
        }
      });
    });
  };

  var agregarPrestamos = function () {
    $.get(base_url + 'panel/facturacion/ajax_get_unidades', function(unidades) {
      var unidadesHtml = '';
      for (var i in unidades) {
        unidadesHtml += '<option value="'+unidades[i].id_unidad+'">'+unidades[i].nombre+'</option>';
      }

      var $table = $('#table-prestamos').find('tbody .row-total'),
          tr =  '<tr>'+
                  '<td style="width: 120px;">'+
                  '  <input type="text" name="prestamo_concepto[]" value="" class="span12">'+
                  '</td>'+
                  '<td style="width: 120px;">'+
                  '  <input type="text" name="prestamo_descripcion[]" value="" id="prestamo_descripcion" class="span12">'+
                  '  <input type="hidden" name="prestamo_id_prod[]" value="" id="prestamo_id_prod" class="span12">'+
                  '</td>'+
                  '<td style="width: 70px;">'+
                  '  <select name="prestamo_umedida[]" id="prestamo_umedida" class="span12">'+
                      unidadesHtml +
                  '  </select>'+
                  '</td>'+
                  '<td style="width: 50px;">'+
                  '  <input type="text" name="prestamo_cantidad[]" value="" class="span12 vpositive prestamo_cantidad">'+
                  '</td>'+
                  '<td style="width: 50px;">'+
                  '  <input type="text" name="prestamo_precio[]" value="" class="span12 vpositive prestamo_precio">'+
                  '</td>'+
                  '<td style="width: 50px;">'+
                  '  <input type="text" name="prestamo_importe[]" value="" class="span12 vpositive prestamo_importe" readonly>'+
                  '</td>'+
                  '<td style="width: 50px;">'+
                  '  <select name="prestamo_tipo[]" id="prestamo_tipo" class="span12">'+
                  '    <option value="t">Prestamo</option>'+
                  '    <option value="f">Pago</option>'+
                  '  </select>'+
                  '</td>'+
                  '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-prestamo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>'+
                '</tr>';

      $(tr).insertBefore($table);
      $(".vpositive").numeric({ negative: false }); //Numero positivo

    }, 'json');
  };
  var btnDelPrestamos = function () {
    $('#table-prestamos').on('click', '.btn-del-prestamo', function(event) {
      var $tr = $(this).parents('tr');

      $tr.remove();

      calculaTotalPrestamos();
    });
  };
  var onChangeKeysPrestamos = function () {
    $('#table-prestamos').on('change', '.prestamo_cantidad, .prestamo_precio', function(e) {
      var $this = $(this),
          $tr = $this.parent().parent();
      calculaTotalPrestamos();
    });
  };
  var calculaTotalPrestamos = function () {
    var total_bultos = 0, total_importe = 0, total_importe_restas = 0;
    $('#table-prestamos .prestamo_importe').each(function(index, el) {
      var $this = $(this),
          $tr = $this.parent().parent(),
          tipo = $tr.find('#prestamo_tipo').val(),
          cantidad = parseFloat( $tr.find('.prestamo_cantidad').val()||0 ),
          precio = parseFloat( $tr.find('.prestamo_precio').val()||0 ),
          importe = parseFloat( (cantidad*precio).toFixed(2) );

      $tr.find('.prestamo_importe').val( importe );
      total_bultos += cantidad;
      total_importe += importe;
      if (tipo == 'true' || tipo == 'dev') {
        total_importe_restas += importe;
      }
    });
    $("#ttotal-prestamos-bultos").val(total_bultos.toFixed(2));
    $("#ttotal-prestamos-precio").val( (total_importe/(total_bultos>0? total_bultos: 1)).toFixed(2) );
    $("#ttotal-prestamos").val(total_importe.toFixed(2));
    $("#ttotal-prestamos-restas").val(total_importe_restas.toFixed(2));
  };




  var onChanceDenominacionNum = function () {
    $('#table-tabulaciones').on('keyup', '.denom-num', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {

        $tr.find('.denom-total').val(parseFloat($this.val() || 0) * parseFloat($this.attr('data-denominacion') || 0));

        calculaTotalDenominaciones();
      }
    });
  };

  var onChanceTotalDenominacion = function () {
    $('#table-tabulaciones').on('keyup', '.denom-total', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalDenominaciones();
      }
    });
  };

  var calculaTotalDenominaciones = function () {
    var total = 0;
    $('.denom-total').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });

    $('#total-efectivo-den').text(util.darFormatoNum(total.toFixed(2)));

    var totalCorte = parseFloat($('#ttotal-corte').val());

    $('#total-efectivo-diferencia').text(util.darFormatoNum((parseFloat(total) - totalCorte).toFixed(2)));
  };

  var btnAddGasto = function () {
    $('#btn-add-gasto').on('click', function(event) {
      $('#accion_catalogos').val('true');
      $('#modalCatalogos').modal('show');
      $('#area').val('');
      $('#areaId').val('');
      $('#rancho').val('');
      $('#ranchoId').val('');
      $('#centroCosto').val('');
      $('#centroCostoId').val('');
      $('#activos').val('');
      $('#activoId').val('');
      $('#dempresa').val('');
      $('#did_empresa').val('');
      $('#did_categoria').val('');
    });

    $('#btnModalCatalogosSel').on('click', function(event) {
      if ($('#accion_catalogos').val() == 'true') {
        agregarGasto();
      } else { // Edita
        $trGastoCat.find('.area').val($('#area').val());
        $trGastoCat.find('.areaId').val($('#areaId').val());
        $trGastoCat.find('.rancho').val($('#rancho').val());
        $trGastoCat.find('.ranchoId').val($('#ranchoId').val());
        $trGastoCat.find('.centroCosto').val($('#centroCosto').val());
        $trGastoCat.find('.centroCostoId').val($('#centroCostoId').val());
        $trGastoCat.find('.activos').val($('#activos').val());
        $trGastoCat.find('.activoId').val($('#activoId').val());
        $trGastoCat.find('.gasto-cargo').val($('#dempresa').val());
        $trGastoCat.find('.gasto-cargo-id').val($('#did_categoria').val());
        $('#modalCatalogos').modal('hide');
      }
    });

    autocompleteEmpresa();
    autocompleteCultivo();
    autocompleteRanchos();
    autocompleteCentroCosto();
    autocompleteActivos();
  };

  var agregarGasto = function () {
    var area = $('#area').val();
    var areaId = $('#areaId').val();
    var rancho = $('#rancho').val();
    var ranchoId = $('#ranchoId').val();
    var centroCosto = $('#centroCosto').val();
    var centroCostoId = $('#centroCostoId').val();
    var activos = $('#activos').val();
    var activoId = $('#activoId').val();
    var dempresa = $('#dempresa').val();
    var did_categoria = $('#did_categoria').val();
    var empresaId = $('#did_empresa').val();

    if (areaId != '' && ranchoId != '' && centroCostoId != '' && empresaId != '') {
      var $table = $('#table-gastos').find('tbody .row-total'),
          tr =  '<tr>' +
                  '<td style="width: 60px;">'+
                    '<input type="hidden" name="gasto_id_gasto[]" value="" id="gasto_id_gasto">'+
                    '<input type="hidden" name="gasto_del[]" value="" id="gasto_del">'+
                    '<input type="text" name="codigoArea[]" value="" id="codigoArea" class="span12 showCodigoAreaAuto" required>'+
                    '<input type="hidden" name="codigoAreaId[]" value="" id="codigoAreaId" class="span12" required>'+
                    '<input type="hidden" name="codigoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12" required>'+
                    '<i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>'+
                    '<input type="hidden" name="area[]" value="'+ area +'" class="area span12">'+
                    '<input type="hidden" name="areaId[]" value="'+ areaId +'" class="areaId span12">'+
                    '<input type="hidden" name="rancho[]" value="'+ rancho +'" class="rancho span12">'+
                    '<input type="hidden" name="ranchoId[]" value="'+ ranchoId +'" class="ranchoId span12">'+
                    '<input type="hidden" name="centroCosto[]" value="'+ centroCosto +'" class="centroCosto span12">'+
                    '<input type="hidden" name="centroCostoId[]" value="'+ centroCostoId +'" class="centroCostoId span12">'+
                    '<input type="hidden" name="activos[]" value="'+ activos +'" class="activos span12">'+
                    '<input type="hidden" name="activoId[]" value="'+ activoId +'" class="activoId span12">'+
                    '<input type="hidden" name="empresaId[]" value="'+ empresaId +'" class="empresaId span12">'+
                  '</td>'+
                  '<td style="width: 100px;">' +
                    '<input type="text" name="gasto_empresa[]" value="'+ dempresa +'" class="span12 gasto-cargo" readonly>' +
                    '<input type="hidden" name="gasto_empresa_id[]" value="'+ did_categoria +'" class="input-small vpositive gasto-cargo-id">' +
                  '</td>' +
                  '<td style="width: 40px;">' +
                    '<select name="gasto_nomenclatura[]" class="span12 ingreso_nomenclatura">' +
                      $('#nomeclaturas_base').html() +
                    '</select>' +
                  '</td>' +
                  '<td style="width: 100px;"><input type="text" name="gasto_folio[]" value="" class="span12 gasto-folio"></td>' +
                  '<td style="">' +
                    '<input type="text" name="gasto_concepto[]" value="" class="span12 gasto-concepto">' +
                  '</td>' +
                  '<td style="width: 100px;"><input type="text" name="gasto_importe[]" value="0" class="span12 vpositive gasto-importe"></td>' +
                  '<td style="width: 30px;">'+
                    '<button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
                    '<button type="button" class="btn btn-info btn-show-cat" style="padding: 2px 7px 2px;"><i class="icon-edit"></i></button>'+
                  '</td>' +
                '</tr>';

      $(tr).insertBefore($table);
      $(".vpositive").numeric({ negative: false }); //Numero positivo
      $('#modalCatalogos').modal('hide');
    } else {
      alert('Son requeridos Empresa, Cultivo, Área y centro de costo');
    }
  };

  var btnDelGasto = function () {
    $('#table-gastos').on('click', '.btn-del-gasto', function(event) {
      var $tr = $(this).parents('tr'),
          id = $tr.find('.gasto-cargo-id').val(),
          $totalRepo = $('#repo-'+id).find('.reposicion-importe'),
          $gasto_id_gasto = $tr.find('#gasto_id_gasto'),
          $gasto_del = $tr.find('#gasto_del'),
          total = 0;

      if ($gasto_id_gasto.val() != '') {
        $gasto_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.remove();
      }

      $('input[value="'+id+'"]').each(function(index, el) {
        var $parent = $(this).parents('tr');
        total += parseFloat($parent.find('.gasto-importe').val() || 0);
      });

      $totalRepo.val(total.toFixed(2));

      calculaTotalGastos();
      calculaCorte();
    });
  };

  var $trGastoCat;
  var btnShowGastoCat = function () {
    $('#table-gastos').on('click', '.btn-show-cat', function(event) {
      $trGastoCat = $(this).parents('tr');
      $('#area').val($trGastoCat.find('.area').val());
      $('#areaId').val($trGastoCat.find('.areaId').val());
      $('#rancho').val($trGastoCat.find('.rancho').val());
      $('#ranchoId').val($trGastoCat.find('.ranchoId').val());
      $('#centroCosto').val($trGastoCat.find('.centroCosto').val());
      $('#centroCostoId').val($trGastoCat.find('.centroCostoId').val());
      $('#activos').val($trGastoCat.find('.activos').val());
      $('#activoId').val($trGastoCat.find('.activoId').val());
      $('#dempresa').val($trGastoCat.find('.gasto-cargo').val());
      $('#did_empresa').val($trGastoCat.find('.empresaId').val());
      $('#did_categoria').val($trGastoCat.find('.gasto-cargo-id').val());
      $('#accion_catalogos').val('false');
      $('#modalCatalogos').modal('show');
    });
  };

  var autocompleteEmpresa = function () {

    $("#dempresa").autocomplete({
        // source: base_url+'panel/facturacion/ajax_get_empresas_fac/',
        source: base_url+'panel/caja_chica/ajax_get_categorias/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $("#did_empresa").val(ui.item.item.id_empresa);
          $("#did_categoria").val(ui.item.id);
          $("#dempresa").css("background-color", "#B0FFB0");

          $('#groupCatalogos').show();
          $('#area').val('');
          $('#areaId').val('');
          $('#rancho').val('');
          $('#ranchoId').val('');
          $('#activos').val('');
          $('#activoId').val('');
        }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $("#dempresa").val("").css("background-color", "#FFD9B3");
          $("#did_empresa").val("");
          $("#did_categoria").val("");

          $("#dproveedor").val("").css("background-color", "#FFD9B3");
          $("#did_proveedor").val("");

          $("#dcliente").val("").css("background-color", "#FFD9B3");
          $("#did_cliente").val("");

          $('#area').val('');
          $('#areaId').val('');
          $('#rancho').val('');
          $('#ranchoId').val('');
          $('#activos').val('');
          $('#activoId').val('');
          $('#groupCatalogos').hide();
        }
    });
  };

  var autocompleteCultivo = function () {
    $("#area").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#did_empresa").val()) > 0)
          params.did_empresa = $("#did_empresa").val();
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
        $("#ranchoId").val('');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#area").css("background-color", "#FFD071");
        $("#areaId").val('');
        $("#rancho").val('').css("background-color", "#FFD071");
        $("#ranchoId").val('');
      }
    });
  };

  var autocompleteRanchos = function () {
    $("#rancho").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#did_empresa").val()) > 0)
          params.did_empresa = $("#did_empresa").val();
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

        $rancho.val(ui.item.id);
        $("#ranchoId").val(ui.item.id);
        $rancho.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#rancho").css("background-color", "#FFD071");
        $("#ranchoId").val('');
      }
    });
  };

  var autocompleteCentroCosto = function () {
    $("#centroCosto").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};

        params.tipo = ['gasto', 'servicio'];

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

        $centroCosto.val(ui.item.id);
        $("#centroCostoId").val(ui.item.id);
        $centroCosto.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#centroCosto").css("background-color", "#FFD071");
        $("#centroCostoId").val('');
      }
    });
  };

  var autocompleteActivos = function () {
    $("#activos").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#did_empresa").val()) > 0)
          params.did_empresa = $("#did_empresa").val();
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

  var autocompleteCategorias = function () {
    $(".gasto-cargo").autocomplete({
      source: base_url+'panel/caja_chica/ajax_get_categorias/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $(this).parents('tr').find(".gasto-cargo-id").val(ui.item.id);
        $(this).css("background-color", "#B0FFB0");
      }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).parents('tr').find(".gasto-cargo-id").val("");
          $(this).val("").css("background-color", "#FFD9B3");
        }
    });
  };

  var autocompleteCategoriasLive = function () {
    $('body').on('focus', '.gasto-cargo:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/caja_chica/ajax_get_categorias/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $(this).parents('tr').find(".gasto-cargo-id").val(ui.item.id);
          $(this).css("background-color", "#B0FFB0");
        }
      }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).parents('tr').find(".gasto-cargo-id").val("");
          $(this).val("").css("background-color", "#FFD9B3");
        }
      });
    });
  };

  var onChanceImporteGastos = function () {
    $('#table-gastos').on('keyup', '.gasto-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent(),
          total = 0;

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {

        var id = $tr.find('.gasto-cargo-id').val(),
            $totalRepo = $('#repo-'+id).find('.reposicion-importe');

        $('input[value="'+id+'"]').each(function(index, el) {
          var $parent = $(this).parents('tr');

          total += parseFloat($parent.find('.gasto-importe').val() || 0);
        });

        $totalRepo.val(total.toFixed(2));

        calculaTotalGastos();
        calculaCorte();
      }
    });
  };

  var calculaTotalGastos = function () {
    var total = 0;
    $('.gasto-importe').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });

    $('#td-total-gastos').text(util.darFormatoNum(total.toFixed(2)));
    $('input#ttotal-gastos').val(total.toFixed(2));
  };

  var calculaCorte = function () {
    var total = 0;

    total = parseFloat($('#totalCont').val() || 0) + (parseFloat($('#total-ingresos-ext').val()) || 0) +
            parseFloat($('#abonoshVentas').val() || 0) - parseFloat($('#ttotal-gastos').val() || 0);
    $('#ttotal-corte').val(total.toFixed(2));
    $("#ttotal-corte1").text(util.darFormatoNum(total.toFixed(2)));

    var costo_venta = (parseFloat($("#total_exis_ant").val()) || 0) + (parseFloat($("#total_ingresos_mercan").val()) || 0) - (parseFloat($("#ttotal-prestamos-restas").val()) || 0) - (parseFloat($("#total-boletas_exis").val()) || 0);
    $("#costo_venta").val(costo_venta);
    var utilidad = (parseFloat($("#total-ingresos-ext").val()) || 0) + (parseFloat($("#total-boletas").val()) || 0) - costo_venta - (parseFloat($("#ttotal-gastos").val()) || 0);
    $("#utilidad").val(utilidad);
  };

  var btnAddTraspaso = function () {
    $('#btn-add-traspaso').on('click', function(event) {
      agregarTraspaso();
    });
  };

  var agregarTraspaso = function () {
    var $table = $('#table-traspasos').find('tbody .row-total'),
        tr = '<tr>'+
                '<td>'+
                  '<select name="traspaso_tipo[]" class="span12 ingreso_nomenclatura">'+
                    '<option value="t">Ingreso</option>'+
                    '<option value="f">Egreso</option>'+
                  '</select>'+
                  '<input type="hidden" name="traspaso_id_traspaso[]" value="" id="traspaso_id_traspaso">'+
                  '<input type="hidden" name="traspaso_del[]" value="" id="traspaso_del">'+
                '</td>'+
                '<td style="">'+
                  '<input type="text" name="traspaso_concepto[]" value="" class="span12 traspaso-concepto">'+
                '</td>'+
                '<td style="width: 60px;"><input type="text" name="traspaso_importe[]" value="" class="span12 vpositive traspaso-importe"></td>'+
                '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-traspaso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>'+
              '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  var btnDelTraspaso = function () {
    $('#table-traspasos').on('click', '.btn-del-traspaso', function(event) {
      var $tr = $(this).parents('tr'),
          // id = $tr.find('.gasto-cargo-id').val(),
          // $totalRepo = $('#repo-'+id).find('.reposicion-importe'),
          $traspaso_id_traspaso = $tr.find('#traspaso_id_traspaso'),
          $traspaso_del = $tr.find('#traspaso_del'),
          total = 0;

      if ($traspaso_id_traspaso.val() != '') {
        $traspaso_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.remove();
      }

      calculaTotalTraspaso();
      calculaCorte();
    });
  };

  var calculaTotalTraspaso = function () {
    var total = 0;
    $('#table-traspasos .traspaso-importe').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });
    $('input#ttotal-traspasos').val(total.toFixed(2));
  };

  var onChanceImporteTraspaso = function () {
    $('#table-traspasos').on('keyup', '.traspaso-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $t = $('#table-traspasos'),
          total = 0;

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalTraspaso();
        calculaCorte();
      }
    });
  };

  // var cargaMovimientos = function () {
  //   $('#carga-movimientos').on('click', function(event) {
  //     var $table = $('#table-modal-movimientos'),
  //         html = '',
  //         $this;

  //     if ($('.chk-movimiento:checked').length > 0) {
  //       $('.chk-movimiento:checked').each(function(index, el) {
  //         $this = $(this);

  //         agregarIngreso({id: $this.attr('data-id'), total: $this.attr('data-total'), proveedor: $this.attr('data-proveedor'), poliza: $this.attr('data-poliza')});
  //       });

  //       calculaTotalIngresos();

  //       $('#modal-movimientos').modal('hide');
  //     } else {
  //       noty({"text": 'Seleccione al menos un movimiento.', "layout":"topRight", "type": 'error'});
  //     }
  //   });
  // };

  var searchModalMovimientos = function () {
    $("#search-movimientos").on("keyup", function() {
      var value = $(this).val();
      $("#table-modal-movimientos tr").each(function(index) {
        if (index !== 0) {
          $row = $(this);
          var id = $row.find("td.search-field").text();
          if (id.indexOf(value) !== 0) {
            $row.hide();
          }
          else {
            $row.show();
          }
        }
      });
    });
  };
});