(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    btnAddPagoPc();
    btnAddPrestamo();
    btnDelPrestamo();
    onChancePrestamos();

    btnAddFondoCaja();
    btnDelFondoCaja();
    // agregarFondoCaja();

    onChanceDenominacionNum();
    onChanceTotalDenominacion();

    btnAddPagos();
    btnDelPagos();
    onChanceImportePagos();

    autocompleteCategorias();
    autocompleteCategoriasLive();
    autocompleteEmpleado();

    quitarAdeudosEmpleados();

    btnAddDeudor();
    btnDelDeudor();
    onChanceImporteDeudores();
    autocompleteDeudoresLive();

    onSubmit();


    $('#total-efectivo-diferencia').text(util.darFormatoNum($('#ttotal-diferencia').val()));

  });

  var onSubmit = function () {
    $("#frmcajachica").submit(function(event) {
      $("#table-fondocajas tbody tr").each(function(index, el) {
        var tr = $(this);
        if ( (parseFloat(tr.find('#fondo_ingreso').val())||0) > 0 && (parseFloat(tr.find('#fondo_egreso').val())||0) > 0) {
          alert("En cada fondo de caja solo puede tener INGRESO o EGRESO no ambos.");
          event.preventDefault();
        } else if ( (parseFloat(tr.find('#fondo_ingreso').val())||0) == 0 && (parseFloat(tr.find('#fondo_egreso').val())||0) == 0) {
          alert("En cada fondo de caja es requerido tener un INGRESO o EGRESO.");
          event.preventDefault();
        }
      });
    });
  };

  var btnAddPagoPc = function () {
    $("#table-ingresos, #table-presdia").on('click', '.prestamo-cp-pago', function(event) {
      console.log("ddd");
      var $tr = $(this).parent().parent();
      $("#pc_id_categoria").val($tr.find(".gasto-cargo-id").val());
      $("#pc_id_prestamo_caja").val($tr.find('#prestamo_id_prestamo').val());
      $("#pc_no_caja").val($("#fno_caja").val());
      $("#pc_fecha").val($("#fecha_caja").val());

      $('#addPrestamosCp').modal('show');
    });
  };

  var btnAddPrestamo = function () {
    $('#btn-add-prestamo').on('click', function(event) {
      agregarPrestamo();
    });
  };

  var btnDelPrestamo = function () {
    $('#table-ingresos, #table-presdia').on('click', '.btn-del-prestamo', function(event) {
      var $tr = $(this).parents('tr'),
      $prestamo_id_prestamo = $tr.find('#prestamo_id_prestamo'),
      $prestamo_del = $tr.find('#prestamo_del');

      if ($prestamo_id_prestamo.val() != '') {
        $prestamo_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.find('.prestamo-empleado, .prestamo-empleado-id, .gasto-cargo, .prestamo-concepto, .prestamo-monto').removeAttr('required');
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
                  '<input type="hidden" name="prestamo_id_prestamo[]" value="" id="prestamo_id_prestamo" class="input-small vpositive">'+
                  '<input type="hidden" name="prestamo_del[]" value="" id="prestamo_del">'+
                  '<input type="hidden" name="prestamo_id_prestamo_nom[]" value="" class="input-small vpositive">'+
                  // '<input type="hidden" name="prestamo_id_empleado[]" value="" class="input-small vpositive">'+
                '</td>'+
                '<td>'+
                  '<input type="text" name="prestamo_empleado[]" value="" class="prestamo-empleado span12" maxlength="500" placeholder="Trabajador" required>'+
                  '<input type="hidden" name="prestamo_empleado_id[]" value="" class="prestamo-empleado-id span12" required>'+
                '</td>'+
                '<td>'+$("#fecha_caja").val()+'</td>'+
                '<td>'+
                  '<input type="text" name="prestamo_concepto[]" value="" class="prestamo-concepto span12" maxlength="500" placeholder="Concepto" required>'+
                '</td>'+
                '<td style="width: 100px;"><input type="text" name="prestamo_monto[]" value="" class="prestamo-monto vpositive input-small" placeholder="Monto" required></td>'+
                '<td></td>'+
                '<td></td>'+
                '<td></td>'+
                '<td></td>'+
                '<td></td>'+
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

  var btnAddFondoCaja = function () {
    $('#btn-add-fondocaja').on('click', function(event) {
      agregarFondoCaja();
    });

    $("#dvfondo_caja").text("FONDO DE CAJA: "+$("#table-fondocajas .fondoc_saldo").last().text());
  };

  var btnDelFondoCaja = function () {
    $('#table-fondocajas').on('click', '.btn-del-fondo', function(event) {
      var $tr = $(this).parents('tr'),
      $fondo_id_fondo = $tr.find('#fondo_id_fondo'),
      $fondo_del = $tr.find('#fondo_del');

      if ($fondo_id_fondo.val() != '') {
        $fondo_del.val('true');
        $tr.css('display', 'none');
      } else {
        // $tr.find('.gasto-cargo, .prestamo-concepto, .prestamo-monto').removeAttr('required');
        $tr.remove();
      }
      // calculaTotalPrestamos();
    });
  };

  var agregarFondoCaja = function () {
    var $table = $('#table-fondocajas').find('tbody'),
        tr =  '<tr>'+
                '<td>'+
                  '<input type="text" name="fondo_categoria[]" value="" class="span11 gasto-cargo" id="fondo_categoria" required>'+
                  '<input type="hidden" name="fondo_id_categoria[]" value="" id="fondo_id_categoria" class="gasto-cargo-id">'+
                  '<input type="hidden" name="fondo_id_fondo[]" value="" id="fondo_id_fondo">'+
                  '<input type="hidden" name="fondo_del[]" value="" id="fondo_del">'+
                '</td>'+
                '<td id="fondo_empresa"></td>'+
                '<td><input type="date" name="fondo_fecha[]" value="'+$("#fecha_caja").val()+'" id="fondo_fecha" required></td>'+
                '<td> <input type="text" name="fondo_referencia[]" value="" id="fondo_referencia" class="span11"> </td>'+
                '<td> <input type="number" name="fondo_ingreso[]" value="" id="fondo_ingreso" class="span11 vpositive"></td>'+
                '<td> <input type="number" name="fondo_egreso[]" value="" id="fondo_egreso" class="span11 vpositive"></td>'+
                '<td class="fondoc_saldo"></td>'+
                '<td></td>'+
                '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-fondo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>'+
              '</tr>';
    $table.append(tr);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
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

    $('#ttotal-diferencia').val((totalCorte-parseFloat(total)).toFixed(2));
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

  var autocompleteEmpleado = function () {
    $('body').on('focus', '.prestamo-empleado:not(.ui-autocomplete-input)', function(event) {
      $("#table-ingresos .prestamo-empleado").autocomplete({
        source: base_url + 'panel/usuarios/ajax_get_usuarios/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $tr =  $(this).parent().parent();

          $(this).css("background-color", "#A1F57A");
          $tr.find(".prestamo-empleado-id").val(ui.item.id);
        }
      }).on("keydown", function(event) {
        if(event.which == 8 || event.which == 46) {
          var $tr =  $(this).parent().parent();
          $(this).css("background-color", "#FFD071");
          $tr.find(".prestamo-empleado-id").val('');
        }
      });
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


  var btnAddDeudor = function () {
    $('#btn-add-deudor').on('click', function(event) {
      agregarDeudor();
    });
  };

  var agregarDeudor = function () {
    var $table = $('#table-deudor').find('tbody .row-total'),
        fecha = $('#fecha_caja').val(),
        tr =  '<tr>'+
                '<td style="width: 80px;">'+
                  fecha+
                  '<input type="hidden" name="deudor_fecha[]" value="'+fecha+'">'+
                '</td>'+
                '<td style="width: 80px;">'+
                  '<select name="deudor_tipo[]" style="width: 80px;">'+
                    '<option value="otros">Otros</option>'+
                    '<option value="caja_limon">Caja limón</option>'+
                    '<option value="caja_gastos">Caja gastos</option>'+
                    '<option value="caja_fletes">Caja fletes</option>'+
                    '<option value="caja_general">Caja Distribuidora</option>'+
                    '<option value="caja_prestamo">Prestamo</option>'+
                  '</select>'+
                '</td>'+
                '<td style="width: 80px;">'+
                  '<select name="deudor_nomenclatura[]" class="span12 deudor_nomenclatura">'+
                    $('#nomeclaturas_base').html() +
                  '</select>'+
                '</td>'+
                '<td style="width: 200px;">'+
                  '<input type="text" name="deudor_nombre[]" value="" class="span12 deudor_nombre" required autocomplete="off">'+
                  '<input type="hidden" name="deudor_id_deudor[]" value="" id="deudor_id_deudor">'+
                  '<input type="hidden" name="deudor_del[]" value="" id="deudor_del">'+
                '</td>'+
                '<td style="width: 200px;">'+
                  '<input type="text" name="deudor_concepto[]" value="" class="span12 deudor-cargo" required>'+
                '</td>'+
                '<td style="width: 80px;">'+
                  '<input type="text" name="deudor_importe[]" value="" class="span12 vpositive deudor-importe">'+
                '</td>'+
                '<td style="width: 80px;" class="deudor_abonos" data-abonos="0">'+
                '</td>'+
                '<td style="width: 80px;" class="deudor_saldo" data-saldo="0" data-mismo="">'+
                '</td>'+
                '<td style="width: 30px;">'+
                  '<button type="button" class="btn btn-danger btn-del-deudor" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
                '</td>'+
              '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  var btnDelDeudor = function () {
    $('#table-deudor').on('click', '.btn-del-deudor', function(event) {
      var $tr = $(this).parents('tr'),
          $deudor_id_deudor = $tr.find('#deudor_id_deudor'),
          $deudor_del = $tr.find('#deudor_del'),
          total = 0;

      if ($deudor_id_deudor.val() != '') {
        $deudor_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.remove();
      }

      calculaTotalDeudores();
      calculaCorte();
    });

    // $('#table-gastos').on('change', '.gasto-reposicion', function(event) {
    //   var $tr = $(this).parents('tr');
    //   $tr.find('.gasto-reposicionhid').val( ($(this).is(':checked')? 't': 'f') );
    //   console.log($tr.find('.gasto-reposicionhid').val());
    // });
  };

  var onChanceImporteDeudores = function () {
    $('#table-deudor').on('keyup change', '.deudor-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent(),
          total = 0,
          monto = (parseFloat($this.val())||0);

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8 || monto > 0) {

        var abonos = (parseFloat($tr.find('.deudor_abonos').attr('data-abonos'))||0),
        saldo = (monto-abonos).toFixed(2);

        $tr.find('.deudor_saldo').attr('data-saldo', saldo).text(saldo);

        calculaTotalDeudores();
        calculaCorte();
      }
    });
  };

  var calculaTotalDeudores = function () {
    var total = 0, total_dia = 0;

    $('#table-deudor .deudor_saldo').each(function(index, el) {
      total += parseFloat($(this).attr('data-saldo') || 0);

      if ($(this).attr('data-mismo') == '') {
        total_dia += (parseFloat($(this).attr('data-saldo'))||0);
      }
    });

    // $('#td-total-gastos').text(util.darFormatoNum(total.toFixed(2)));
    $('input#total-deudores-pres-dia').val(total_dia.toFixed(2));
    $('input#total-deudores').val(total.toFixed(2));
    $('#ttotal-deudores').val(total_dia.toFixed(2));
  };

  var autocompleteDeudoresLive = function () {
    $('body').on('focus', '.deudor_nombre:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/caja_chica/ajax_get_deudores/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $(this).val(ui.item.id);
          $(this).css("background-color", "#B0FFB0");
        }
      }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).val("").css("background-color", "#FFD9B3");
        }
      });
    });
  };

});