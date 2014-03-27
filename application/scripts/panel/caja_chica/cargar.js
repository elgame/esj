(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    btnAddIngreso();
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

  var agregarIngreso = function () {
    var $table = $('#table-ingresos').find('tbody'),
        tr =  '<tr>' +
                '<td><input type="text" name="ingreso_concepto[]" value="" class="ingreso-concepto span12" maxlength="500" placeholder="Concepto" required></td>' +
                '<td style="width: 100px;"><input type="text" name="ingreso_monto[]" value="0" class="ingreso-monto vpositive input-small" placeholder="Monto" required></td>' +
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
    $('#table-otros').on('click', '.btn-del-otros', function(event) {
      $(this).parents('tr').remove();
      calculaTotalIngresos();
    });
  };

  var agregarOtros = function () {
    var $table = $('#table-otros').find('tbody'),
        tr =  '<tr>' +
                '<td><input type="text" name="otros_concepto[]" value="" class="otros-concepto span12" maxlength="500" placeholder="Concepto" required></td>' +
                '<td style="width: 100px;"><input type="text" name="otros_monto[]" value="0" class="otros-monto vpositive input-small" placeholder="Monto" required></td>' +
                '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>' +
              '</tr>';

    $(tr).appendTo($table);
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

    $('#total-ingresos').val(total);

    var saldo_inicial = parseFloat($('#saldo_inicial').val());
    $('#total-saldo-ingresos').val(saldo_inicial + total);

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
          html += '<tr>' +
                    '<td>' +
                      '<input type="text" name="remision_concepto[]" value="" class="remision-concepto span12" maxlength="500" placeholder="Observacion" required>' +
                      '<input type="hidden" name="remision_id[]" value="'+$this.attr('data-id')+'" class="remision-id span12" required>' +
                    '</td>' +
                    '<td><input type="text" name="remision_numero[]" value="'+$this.attr('data-folio')+'" class="remision-numero vpositive input-small" placeholder="#" readonly style="width: 45px;"></td>' +
                    '<td><input type="text" name="remision_importe[]" value="'+$this.attr('data-total')+'" class="remision-importe vpositive input-small" placeholder="Importe" required style="width: 55px;text-align: right;"></td>' +
                    '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-remision" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>' +
                  '</tr>';
        });

        $(html).appendTo($table);
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
    var $table = $('#table-gastos').find('tbody'),
        tr =  '<tr>' +
                '<td><input type="text" name="gasto_concepto[]" value="" class="input-xlarge span12 gasto-concepto"></td>' +
                '<td style="width: 100px;">' +
                  '<input type="text" name="gasto_cargo[]" value="" class="input-small gasto-cargo" style="width: 150px;">' +
                  '<input type="hidden" name="gasto_cargo_id[]" value="" class="input-small vpositive gasto-cargo-id">' +
                '</td>' +
                '<td style="width: 100px;"><input type="text" name="gasto_importe[]" value="0" class="input-small vpositive gasto-importe" style="text-align: right;"></td>' +
                '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>' +
              '</tr>';

    $(tr).appendTo($table);
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
        $(".gasto-cargo-id").val(ui.item.id);
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
    $('#ttotal-gastos').val(total.toFixed(2));
  };

  var calculaCorte = function () {
    var total = 0;

    total = parseFloat($('#total-saldo-ingresos').val() || 0) - parseFloat($('#total-boletas').val() || 0) - parseFloat($('#ttotal-gastos').val() || 0);
    $('#ttotal-corte').val(total.toFixed(2));
  };
});