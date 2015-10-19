(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    btnAddIngreso();
    // btnAddMovimientos();
    btnDelIngreso();

    btnAddOtros();
    btnDelOtros();

    onChanceIngresos();

    cargaRemisiones();
    btnDelRemision();
    onChangeTotalRemisiones();

    onChanceDenominacionNum();
    onChanceTotalDenominacion();

    btnAddGasto();
    btnDelGasto();

    autocompleteCategorias();
    autocompleteCategoriasLive();

    onChanceImporteGastos();

    $('#total-efectivo-diferencia').text(util.darFormatoNum($('#ttotal-diferencia').val()));

    cargaMovimientos();
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

  var btnAddOtros = function () {
    $('#btn-add-otros').on('click', function(event) {
      agregarOtros();
    });
  };

  var btnDelOtros = function () {
    $('#table-remisiones').on('click', '.btn-del-otros', function(event) {
      $(this).parents('tr').remove();
      calculaTotalIngresos();
    });
  };

  var agregarRemisiones = function (remision) {
    var $table = $('#table-remisiones').find('tbody .row-total'),
        tr;

    var numRemision = '', folio = '', id = '', abono = '0', concepto = '';
    if (remision) {
      id           = remision.id;
      numRemision  = remision.numremision;
      abono        = remision.total;
      foliofactura = remision.foliofactura;
      concepto     = remision.concepto;
    }

    tr =  '<tr>' +
            '<td style="width: 100px;">' +
              '<input type="text" name="remision_empresa[]" value="" class="input-small gasto-cargo" style="width: 150px;" required>' +
              '<input type="hidden" name="remision_empresa_id[]" value="" class="input-small vpositive gasto-cargo-id">' +
            '</td>' +
            '<td style="width: 70px;"><input type="text" name="remision_numero[]" value="'+numRemision+'" class="remision-numero vpositive input-small" placeholder="" readonly style="width: 70px;"></td>' +
            '<td style="width: 100px;"><input type="text" name="remision_folio[]" value="'+foliofactura+'" class="remision_folio" placeholder="Folio" style="width: 100px;"></td>' +
            '<td>' +
              '<input type="text" name="remision_concepto[]" value="'+concepto+'" class="remision-concepto span12" maxlength="500" placeholder="Nombre" required>' +
              '<input type="hidden" name="remision_id[]" value="'+id+'" class="remision-id span12" required>' +
            '</td>' +
            '<td style="width: 100px;"><input type="text" name="remision_importe[]" value="'+abono+'" class="remision-importe vpositive input-small" placeholder="Importe" required></td>' +
            '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>' +
          '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  var calculaTotalIngresos = function () {
    var total = 0;
    $('.ingreso-monto').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });

    // $('.otros-monto').each(function(index, el) {
    //   total += parseFloat($(this).val() || 0);
    // });

    $('.remision-importe').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });

    total = parseFloat(total.toFixed(2));

    $('#total-ingresos').val(total);

    var saldo_inicial = parseFloat($('#saldo_inicial').val()),
        totalSaldoIngresos =  saldo_inicial + total; //saldo_inicial +

    $('input#total-saldo-ingresos.span12').val(totalSaldoIngresos.toFixed(2));
    $('input#total-saldo-ingresos.vpositive').val((totalSaldoIngresos - saldo_inicial).toFixed(2));

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

  var cargaRemisiones = function () {
    $('#carga-remisiones').on('click', function(event) {
      var $table = $('#table-remisiones').find('#table-rem-tbody'),
          html = '',
          $this;

      if ($('.chk-remision:checked').length > 0) {
        $('.chk-remision:checked').each(function(index, el) {
          $this = $(this);

          agregarRemisiones({id: $this.attr('data-id'), numremision: $this.attr('data-numremision'), total: $this.attr('data-total'), foliofactura: $this.attr('data-foliofactura'), concepto: $this.attr('data-concepto')});

          // html += '<tr>' +
          //           '<td>' +
          //             '<input type="text" name="remision_concepto[]" value="" class="remision-concepto span12" maxlength="500" placeholder="Observacion" required>' +
          //             '<input type="hidden" name="remision_id[]" value="'+$this.attr('data-id')+'" class="remision-id span12" required>' +
          //           '</td>' +
          //           '<td><input type="text" name="remision_numero[]" value="'+$this.attr('data-folio')+'" class="remision-numero vpositive input-small" placeholder="#" readonly style="width: 45px;"></td>' +
          //           '<td><input type="text" name="remision_importe[]" value="'+$this.attr('data-total')+'" class="remision-importe vpositive input-small" placeholder="Importe" required style="width: 55px;text-align: right;"></td>' +
          //           '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-remision" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>' +
          //         '</tr>';
        });

        // $(html).appendTo($table);
        calculaTotalRemisiones();

        $('#modal-remisiones').modal('hide');
      } else {
        noty({"text": 'Seleccione al menos una remision.', "layout":"topRight", "type": 'error'});
      }
    });
  };

  var btnDelRemision = function () {
    $('#table-remisiones').on('click', '.btn-del-remision', function(event) {
      $(this).parents('tr').remove();
      calculaTotalRemisiones();
    });
  };

  var calculaTotalRemisiones = function () {
    var total = 0;
    $('.remision-importe').each(function(index, el) {
      total += parseFloat($(this).val());
    });

    calculaTotalIngresos();

    $('#total-remisiones').text(util.darFormatoNum(total.toFixed(2)));
  };

  var onChangeTotalRemisiones = function () {
    $('#table-remisiones').on('keyup', '.remision-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalRemisiones();
      }
    });
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
      agregarGasto();
    });
  };

  var agregarGasto = function () {
    var $table = $('#table-gastos').find('tbody .row-total'),
        tr =  '<tr>' +
                '<td style="width: 60px;">'+
                  '<input type="text" name="codigoArea[]" value="" id="codigoArea" class="span12 showCodigoAreaAuto" required>'+
                  '<input type="hidden" name="codigoAreaId[]" value="" id="codigoAreaId" class="span12" required>'+
                  '<i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>'+
                '</td>'+
                '<td style="width: 100px;">' +
                  '<input type="text" name="gasto_empresa[]" value="" class="span12 gasto-cargo">' +
                  '<input type="hidden" name="gasto_empresa_id[]" value="" class="input-small vpositive gasto-cargo-id">' +
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
                '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>' +
              '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  var btnDelGasto = function () {
    $('#table-gastos').on('click', '.btn-del-gasto', function(event) {
      var $tr = $(this).parents('tr'),
          id = $tr.find('.gasto-cargo-id').val(),
          $totalRepo = $('#repo-'+id).find('.reposicion-importe'),
          total = 0;

      $tr.remove();

      $('input[value="'+id+'"]').each(function(index, el) {
        var $parent = $(this).parents('tr');
        total += parseFloat($parent.find('.gasto-importe').val() || 0);
      });

      $totalRepo.val(total.toFixed(2));

      calculaTotalGastos();
      calculaCorte();
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

    total = parseFloat($('#totalCont').val() || 0) + parseFloat($('#abonoshVentas').val() || 0) - parseFloat($('#ttotal-gastos').val() || 0);
    $('#ttotal-corte').val(total.toFixed(2));
    $("#ttotal-corte1").text(util.darFormatoNum(total.toFixed(2)));
  };

  var cargaMovimientos = function () {
    $('#carga-movimientos').on('click', function(event) {
      var $table = $('#table-modal-movimientos'),
          html = '',
          $this;

      if ($('.chk-movimiento:checked').length > 0) {
        $('.chk-movimiento:checked').each(function(index, el) {
          $this = $(this);

          agregarIngreso({id: $this.attr('data-id'), total: $this.attr('data-total'), proveedor: $this.attr('data-proveedor'), poliza: $this.attr('data-poliza')});
        });

        calculaTotalIngresos();

        $('#modal-movimientos').modal('hide');
      } else {
        noty({"text": 'Seleccione al menos un movimiento.', "layout":"topRight", "type": 'error'});
      }
    });
  };

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