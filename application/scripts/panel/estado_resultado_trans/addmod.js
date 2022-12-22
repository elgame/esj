(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    obtenRemisionesAjax();
    cargaRemisiones();
    btnDelRemision();
    onChangeTotalRemisiones();
    btnAddSueldo();
    btnDelSueldo();
    onChanceImporteSueldo();
    btnAddGastos();
    btnDelGastos();
    onChanceImporteGastos();

    autocompleteProveedoresLive();

  });

  var submitForm = function () {
    $('#frmcajachica').submit(function(event) {
      $('#btnGuardar').attr('disabled', 'disabled');
      $('#btnGuardar2').attr('disabled', 'disabled');
      $('#logo1').focus();
      if ($('#btnGuardar').length == 0) {
        event.preventDefault();
      }
    });
  }


  var obtenRemisionesAjax = function () {
    $('#modal-remisiones').on('show', function () {
      $.getJSON(base_url+'panel/estado_resultado_trans/ajax_get_remisiones', function(json, textStatus) {
        var html = '';
        for (var key in json) {
          html += '<tr>'+
              '<td><input type="checkbox" class="chk-remision" data-id="'+json[key].id_factura+'" '+
                'data-numremision="'+json[key].serie+json[key].folio+'" data-total="'+json[key].subtotal+'" '+
                'data-cliente="'+json[key].cliente+'" '+
                'data-fecha="'+json[key].fecha+'"></td>'+
              '<td style="width: 66px;">'+json[key].fecha+'</td>'+
              '<td>'+json[key].serie+json[key].folio+'</td>'+
              '<td>'+json[key].cliente+'</td>'+
              '<td style="text-align: right;">'+json[key].subtotal+'</td>'+
            '</tr>';
        }

        $('#modal-remisiones #lista_remisiones_modal tbody').html(html);
        $("#lista_remisiones_modal").filterTable();
      });
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

          agregarRemisiones({
            id: $this.attr('data-id'),
            numremision: $this.attr('data-numremision'),
            total: $this.attr('data-total'),
            cliente: $this.attr('data-cliente'),
            fecha: $this.attr('data-fecha')
          });
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

  var agregarRemisiones = function (remision) {
    var $table = $('#table-remisiones').find('tbody .row-total'),
        tr;

    if ($('#table-remisiones').find('.remision-id[value='+remision.id+']').length == 0) {
      var numRemision = '', folio = '', id = '', total = '0', cliente = '', fecha = '';
      if (remision) {
        id           = remision.id;
        numRemision  = remision.numremision;
        total        = remision.total;
        cliente     = remision.cliente;
        fecha        = remision.fecha;
      }

      tr =  '<tr>' +
              '<td style=""><input type="date" name="remision_fecha[]" value="'+fecha+'" class="remision_fecha" placeholder="Fecha" readonly></td>' +
              '<td style=""><input type="text" name="remision_numero[]" value="'+numRemision+'" class="remision-numero vpositive" placeholder="" readonly style=""></td>' +
              '<td colspan="3">' +
                '<input type="text" name="remision_cliente[]" value="'+cliente+'" class="remision-cliente span12" maxlength="500" placeholder="Nombre" required readonly>' +
                '<input type="hidden" name="remision_id[]" value="'+id+'" class="remision-id span12" required>' +
                '<input type="hidden" name="remision_row[]" value="" class="input-small vpositive remision_row">' +
              '</td>' +
              '<td style=""><input type="number" step="any" name="remision_importe[]" value="'+total+'" class="remision-importe vpositive" placeholder="Importe" required readonly></td>' +
              '<td style="width: 30px;">'+
                '<button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
                '<input type="hidden" name="remision_del[]" value="" id="remision_del">'+
              '</td>' +
            '</tr>';
      $(tr).insertBefore($table);
      $(".vpositive").numeric({ negative: false }); //Numero positivo
    } else {
      alert("Ya esta agregada la remisión "+remision.numremision+" no puedes agregarla en el mismo estado.");
    }
  };

  var calculaTotalRemisiones = function () {
    var total = 0;
    $('#table-remisiones .remision-importe').each(function(index, el) {
      total += parseFloat($(this).val());
    });

    // calculaTotalIngresos();

    $('#total-ingresosRemisiones').val(util.darFormatoNum(total.toFixed(2)));
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


  var btnAddSueldo = function () {
    $('#btn-add-sueldos').on('click', function(event) {
      agregarSueldo();
    });
  };
  var agregarSueldo = function () {
    var $table = $('#table-sueldos').find('tbody .row-total'),
        tr = '<tr>'+
                '<td><input type="date" name="sueldos_fecha" value="" required></td>'+
                '<td>'+
                  '<input type="hidden" name="sueldos_id_sueldo[]" value="" id="sueldos_id_sueldo">'+
                  '<input type="hidden" name="sueldos_del[]" value="" id="sueldos_del">'+
                  '<input type="text" name="sueldos_proveedor[]" value="" class="span12 autproveedor" required>'+
                  '<input type="hidden" name="sueldos_proveedor_id[]" value="" class="span12 vpositive autproveedor-id">'+
                '</td>'+
                '<td style="">'+
                  '<input type="text" name="sueldos_concepto[]" value="" class="span12 sueldos-concepto" required>'+
                '</td>'+
                '<td style="width: 60px;"><input type="text" name="sueldos_importe[]" value="" class="span12 vpositive sueldos-importe" required></td>'+
                '<td style="width: 30px;">'+
                  '<button type="button" class="btn btn-danger btn-del-sueldos" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
                  '<input type="hidden" name="sueldos_del[]" value="" id="sueldos_del">'+
                '</td>' +
              '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };
  var btnDelSueldo = function () {
    $('#table-sueldos').on('click', '.btn-del-sueldos', function(event) {
      var $tr = $(this).parents('tr'),
          // id = $tr.find('.gasto-cargo-id').val(),
          // $totalRepo = $('#repo-'+id).find('.reposicion-importe'),
          $sueldos_id_sueldo = $tr.find('#sueldos_id_sueldo'),
          $sueldos_del = $tr.find('#sueldos_del'),
          total = 0;

      if ($sueldos_id_sueldo.val() != '') {
        $sueldos_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.remove();
      }

      calculaTotalSueldo();
      // calculaCorte();
    });
  };
  var calculaTotalSueldo = function () {
    var total = 0;
    $('#table-sueldos .sueldos-importe').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });
    $('input#ttotal-sueldos').val(total.toFixed(2));
  };
  var onChanceImporteSueldo = function () {
    $('#table-sueldos').on('keyup', '.sueldos-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $t = $('#table-sueldos'),
          total = 0;

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalSueldo();
        // calculaCorte();
      }
    });
  };


  var btnAddGastos = function () {
    $('#btn-add-gastos').on('click', function(event) {
      agregarGastos();
    });
  };
  var agregarGastos = function () {
    var $table = $('#table-gastos').find('tbody .row-total'),
        tr = '<tr>'+
                '<td><input type="date" name="gastos_fecha" value="" required></td>'+
                '<td>'+
                  '<input type="hidden" name="gastos_id_sueldo[]" value="" id="gastos_id_sueldo">'+
                  '<input type="hidden" name="gastos_del[]" value="" id="gastos_del">'+
                  '<input type="text" name="gastos_proveedor[]" value="" class="span12 autproveedor" required>'+
                  '<input type="hidden" name="gastos_proveedor_id[]" value="" class="span12 vpositive autproveedor-id">'+
                '</td>'+
                '<td style="">'+
                  '<input type="text" name="gastos_concepto[]" value="" class="span12 gastos-concepto" required>'+
                '</td>'+
                '<td style="width: 60px;"><input type="text" name="gastos_importe[]" value="" class="span12 vpositive gastos-importe" required></td>'+
                '<td style="width: 30px;">'+
                  '<button type="button" class="btn btn-danger btn-del-gastos" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
                  '<input type="hidden" name="gastos_del[]" value="" id="gastos_del">'+
                '</td>' +
              '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };
  var btnDelGastos = function () {
    $('#table-gastos').on('click', '.btn-del-gastos', function(event) {
      var $tr = $(this).parents('tr'),
          // id = $tr.find('.gasto-cargo-id').val(),
          // $totalRepo = $('#repo-'+id).find('.reposicion-importe'),
          $gastos_id_sueldo = $tr.find('#gastos_id_sueldo'),
          $gastos_del = $tr.find('#gastos_del'),
          total = 0;

      if ($gastos_id_sueldo.val() != '') {
        $gastos_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.remove();
      }

      calculaTotalGastos();
      // calculaCorte();
    });
  };
  var calculaTotalGastos = function () {
    var total = 0;
    $('#table-gastos .gastos-importe').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });
    $('input#ttotal-gastos').val(total.toFixed(2));
  };
  var onChanceImporteGastos = function () {
    $('#table-gastos').on('keyup', '.gastos-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $t = $('#table-gastos'),
          total = 0;

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalGastos();
        // calculaCorte();
      }
    });
  };

  var autocompleteProveedoresLive = function () {
    $('body').on('focus', '.autproveedor:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/caja_chica/ajax_get_categorias/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $(this).parents('tr').find(".autproveedor-id").val(ui.item.id);
          $(this).css("background-color", "#B0FFB0");
        }
      }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).parents('tr').find(".autproveedor-id").val("");
          $(this).val("").css("background-color", "#FFD9B3");
        }
      });
    });
  };

});
