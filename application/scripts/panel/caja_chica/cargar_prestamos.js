(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    btnAddPrestamo();
    btnDelPrestamo();
    onChancePrestamos();

    onChanceDenominacionNum();
    onChanceTotalDenominacion();

    btnAddPagos();
    btnDelPagos();
    onChanceImportePagos();

    autocompleteCategorias();
    autocompleteCategoriasLive();

    quitarAdeudosEmpleados();


    $('#total-efectivo-diferencia').text(util.darFormatoNum($('#ttotal-diferencia').val()));

  });

  var btnAddPrestamo = function () {
    $('#btn-add-prestamo').on('click', function(event) {
      agregarPrestamo();
    });
  };

  var btnDelPrestamo = function () {
    $('#table-ingresos').on('click', '.btn-del-prestamo', function(event) {
      var $tr = $(this).parents('tr'),
      $prestamo_id_prestamo = $tr.find('#prestamo_id_prestamo'),
      $prestamo_del = $tr.find('#prestamo_del');

      if ($prestamo_id_prestamo.val() != '') {
        $prestamo_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.find('.gasto-cargo, .prestamo-concepto, .prestamo-monto').removeAttr('required');
        $tr.remove();
      }
      calculaTotalPrestamos();
    });
  };

  var agregarPrestamo = function () {
    var $table = $('#table-ingresos').find('tbody .row-total'),
        tr =  '<tr>'+
                '<td style="width: 100px;">'+
                  '<input type="text" name="prestamo_empresa[]" value="" class="input-small gasto-cargo" style="width: 150px;" required>'+
                  '<input type="hidden" name="prestamo_empresa_id[]" value="" class="input-small vpositive gasto-cargo-id">'+
                  '<input type="hidden" name="prestamo_id_prestamo[]" id="prestamo_id_prestamo" value="" class="input-small vpositive">'+
                  '<input type="hidden" name="prestamo_del[]" value="" id="prestamo_del">'+
                  '<input type="hidden" name="prestamo_id_prestamo_nom[]" value="" class="input-small vpositive">'+
                  '<input type="hidden" name="prestamo_id_empleado[]" value="" class="input-small vpositive">'+
                '</td>'+
                '<td style="width: 40px;">'+
                  '<select name="prestamo_nomenclatura[]" class="prestamo_nomenclatura" style="width: 70px;">' +
                    $('#nomeclaturas_base').html() +
                  '</select>' +
                '</td>'+
                '<td>'+
                  '<input type="text" name="prestamo_concepto[]" value="" class="prestamo-concepto span12" maxlength="500" placeholder="Concepto" required>'+
                '</td>'+
                '<td style="width: 100px;"><input type="text" name="prestamo_monto[]" value="" class="prestamo-monto vpositive input-small" placeholder="Monto" required></td>'+
                '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-prestamo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>'+
              '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  var calculaTotalPrestamos = function () {
    var total = 0;
    $('.prestamo-monto').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });
    total = parseFloat(total.toFixed(2));

    $('#ttotal-prestamo').val(total);
    $('#total-saldo-prestamo').val(total);

    calculaCorte();
  };

  var onChancePrestamos = function () {
    // $('#table-ingresos, #table-otros').on('keyup', '.ingreso-monto, .otros-monto', function(e) {
    $('#table-ingresos').on('keyup', '.prestamo-monto', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalPrestamos();
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

  var btnAddPagos = function () {
    $('#btn-add-pagos').on('click', function(event) {
      agregarPagos();
    });
  };

  var agregarPagos = function () {
    var $table = $('#table-gastos').find('tbody .row-total'),
        tr =  '<tr>'+
                  '<td style="width: 100px;">'+
                    '<input type="text" name="pago_empresa[]" value="" class="span12 gasto-cargo" required>'+
                    '<input type="hidden" name="pago_empresa_id[]" value="" class="input-small vpositive gasto-cargo-id">'+
                    '<input type="hidden" name="pago_id[]" id="pago_id" value="" class="input-small vpositive">'+
                    '<input type="hidden" name="pago_del[]" value="" id="pago_del">'+
                    '<input type="hidden" name="pago_id_empleado[]" value="" class="input-small vpositive">'+
                    '<input type="hidden" name="pago_id_empresa[]" value="" class="input-small vpositive">'+
                    '<input type="hidden" name="pago_anio[]" value="" class="input-small vpositive">'+
                    '<input type="hidden" name="pago_semana[]" value="" class="input-small vpositive">'+
                    '<input type="hidden" name="pago_id_prestamo[]" value="" class="input-small vpositive">'+
                  '</td>'+
                  '<td style="width: 40px;">'+
                    '<select name="pago_nomenclatura[]" class="span12 ingreso_nomenclatura">'+
                      $('#nomeclaturas_base').html() +
                    '</select>'+
                  '</td>'+
                  '<td style="">'+
                    '<input type="text" name="pago_concepto[]" value="" class="span12 pago-concepto">'+
                  '</td>'+
                  '<td style="width: 60px;"><input type="text" name="pago_importe[]" value="" class="span12 vpositive pago-importe"></td>'+
                  '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-pagos" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>'+
                '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  var btnDelPagos = function () {
    $('#table-gastos').on('click', '.btn-del-pagos', function(event) {
      var $tr = $(this).parents('tr'),
          id         = $tr.find('.gasto-cargo-id').val(),
          $totalRepo = $('#repo-'+id).find('.reposicion-importe'),
          total      = 0,
          $pago_id   = $tr.find('#pago_id'),
          $pago_del  = $tr.find('#pago_del');

      if ($pago_id.val() != '') {
        $pago_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.remove();
      }

      calculaTotalPagos();
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

  var onChanceImportePagos = function () {
    $('#table-gastos').on('keyup', '.pago-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent(),
          total = 0;

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {

        calculaTotalPagos();
      }
    });
  };

  var calculaTotalPagos = function () {
    var total = 0;
    $('.pago-importe').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });

    $('input#ttotal-pago').val(total.toFixed(2));
    $('#ttotal-pagos').val(total.toFixed(2))

    calculaCorte();
  };

  var calculaCorte = function () {
    var total = 0, total_efectivco = 0;

    total = parseFloat($('#total-saldo-inicial').val() || 0) - parseFloat($('#total-saldo-prestamo').val() || 0) + parseFloat($('#ttotal-pagos').val() || 0);
    $('#ttotal-corte').val(total.toFixed(2));

    total_efectivco = parseFloat(util.quitarFormatoNum($("#total-efectivo-den").text()) || 0) - parseFloat(total.toFixed(2) || 0);
    $('#ttotal-diferencia').val(total_efectivco.toFixed(2));
    $('#total-efectivo-diferencia').text(util.darFormatoNum(total_efectivco.toFixed(2)));
  };


  var quitarAdeudosEmpleados = function () {
    $("#table-empsaldo").on('click', '.btn-del-empsaldo', function(event) {
      var $tr = $(this).parent().parent();
      var r = confirm("Estas seguro de saldar los adeudos del empleado?");
      if (r) {
        $.getJSON(base_url+'panel/caja_chica_prest/ajax_saldar_adeudos/', {
          empleadoId: $tr.find('.empsaldo_empleado_id').val(),
          fecha: $("#fecha_caja").val() }, function(json, textStatus) {
            console.log(json);
            noty({"text":"Se saldaron los prestamos correctamiente", "layout":"topRight", "type":"success"});
            setTimeout(function(){
              window.location.href = window.location.href;
            }, 200);
        });
      }
    });
  };

});