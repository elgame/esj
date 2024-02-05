$(function(){
  comisionTerceros();
  btnAddGasto();
  btnDelGasto();
  onChanceImporteGastos();
});

const comisionTerceros = () => {
    $('#btnAddComisionTerceros').click(function(event) {
      html =
      '<tr>'+
        '<td>'+
          '<input type="text" name="comisionTerceros_nombre[]" value="" class="span12 comisionTerceros_nombre" required>'+
          '<input type="hidden" name="comisionTerceros_id[]" value="" class="span12 comisionTerceros_id">'+
          '<input type="hidden" name="comisionTerceros_delete[]" value="false" class="span12 comisionTerceros_delete">'+
        '</td>'+
        '<td>'+
          '<input type="text" name="comisionTerceros_descripcion[]" value="" class="span12 comisionTerceros_descripcion" required>'+
        '</td>'+
        '<td><input type="text" name="comisionTerceros_cantidad[]" value="" class="span12 vpositive comisionTerceros_cantidad" required></td>'+
        '<td><input type="text" name="comisionTerceros_costo[]" value="" class="span12 vpositive comisionTerceros_costo" required></td>'+
        '<td><input type="text" name="comisionTerceros_importe[]" value="" class="span12 vpositive comisionTerceros_importe" readonly></td>'+
        '<td style="width: 30px;">'+
          '<button type="button" class="btn btn-danger comisionTerceros_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
        '</td>'+
      '</tr>';
      $(html).insertBefore($('#table-comision-terceros tbody tr.footer'));
      $("#table-comision-terceros tbody .vpositive").numeric({ negative: false }); //Numero positivo
    });

    $('#table-comision-terceros').on('click', '.comisionTerceros_del', function(event) {
      var $tr = $(this).parents('tr');
      if((parseInt($tr.find('.comisionTerceros_id').val())||0) > 0) {
        $tr.find('.comisionTerceros_delete').val('true');
        $tr.hide();
      } else {
        $tr.remove();
      }

      calculaTotalComisionTerceros();
    });

    $('#table-comision-terceros').on('keyup', '.comisionTerceros_cantidad, .comisionTerceros_costo', function(event) {
      let $tr = $(this).parents('tr');
      let cantidad = parseFloat($tr.find('.comisionTerceros_cantidad').val())||0;
      let costo = parseFloat($tr.find('.comisionTerceros_costo').val())||0;

      $tr.find('.comisionTerceros_importe').val(cantidad*costo);

      calculaTotalComisionTerceros();
    });
  };
  const calculaTotalComisionTerceros = () => {
    cantidadt = kilost = importet = 0;
    $("#table-comision-terceros tbody tr:not(.footer)").each(function(index, el) {
      if($(el).find('.comisionTerceros_delete').val() == 'false') {
        cantidadt += parseFloat($(el).find('.comisionTerceros_cantidad').val())||0;
        importet += parseFloat($(el).find('.comisionTerceros_importe').val())||0;
      }
    });
    $('#comisionTerceros_cantidad').text(cantidadt);
    $('#comisionTerceros_importe').text(importet);
  };


  var btnAddGasto = function () {
    $('#btn-add-gasto').on('click', function(event) {
      agregarGasto();
    });
  };

  var agregarGasto = function () {
    var tabla_gastos = '#table-gastos';
    var prefix_gastos = '';
    var area = $('#area').val();
    var areaId = $('#areaId').val();

    var $table = $(tabla_gastos).find('tbody .row-total'),
        tr =  '<tr>' +
                (prefix_gastos!='' && prefix_gastos!='pre_'? '<td></td>': '')+
                // '<td style="">'+
                  '<input type="hidden" name="gasto_'+prefix_gastos+'id_gasto[]" value="" id="gasto_id_gasto">'+
                  '<input type="hidden" name="gasto_'+prefix_gastos+'del[]" value="" id="gasto_del">'+
                //   '<input type="text" name="'+prefix_gastos+'codigoArea[]" value="" id="codigoArea" class="span12 showCodigoAreaAuto">'+
                  '<input type="hidden" name="'+prefix_gastos+'codigoAreaId[]" value="" id="codigoAreaId" class="span12">'+
                  '<input type="hidden" name="'+prefix_gastos+'codigoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12" required>'+
                //   '<i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>'+
                // '</td>'+
                '<td style="">' +
                  '<input type="text" name="gasto_'+prefix_gastos+'nombre[]" value="" class="span12 gasto-nombre">' +
                '</td>' +
                '<td style="">' +
                  '<input type="text" name="gasto_'+prefix_gastos+'concepto[]" value="" class="span12 gasto-concepto">' +
                '</td>' +
                '<td style=""><input type="text" name="gasto_'+prefix_gastos+'importe[]" value="0" class="span12 vpositive gasto-importe"></td>' +
                '<td style="width: 30px;">'+
                  '<button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
                '</td>' +
              '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  var btnDelGasto = function () {
    $('#table-gastos, #table-gastos-comprobar, #table-reposicionGastos, #table-pregastos').on('click', '.btn-del-gasto', function(event) {
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

    $('#table-gastos').on('change', '.gasto-reposicion', function(event) {
      var $tr = $(this).parents('tr');
      $tr.find('.gasto-reposicionhid').val( ($(this).is(':checked')? 't': 'f') );
      console.log($tr.find('.gasto-reposicionhid').val());
    });

    $('#table-reposicionGastos').on('change', '.reposiciong-reposicion', function(event) {
      var $tr = $(this).parents('tr');
      $tr.find('.reposiciong-reposicionhid').val( ($(this).is(':checked')? 't': 'f') );
      console.log($tr.find('.reposiciong-reposicionhid').val());
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
      }
    });
  };

  var calculaTotalGastos = function () {
    var total = 0;
    $('#table-gastos .gasto-importe').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });

    $('#td-total-gastos').text(util.darFormatoNum(total.toFixed(2)));
    $('input#ttotal-gastos').val(total.toFixed(2));
  };
