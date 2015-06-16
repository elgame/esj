(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {
    eventsCalculos();

    btnAddExisComp();
    btnDelExisComp();
    autocompleteClasifiLive();

  });

  var calculaIngresos = function () {
    var total_exis_bultos = 0, total_exis_kilos = 0, total_existencias = 0;
    $('#table-existencia input#prod_bultos').each(function(index, el) {
      var $this = $(el),
      $tr = $this.parent().parent();

      $tr.find('#prod_kilos').val( ((parseFloat($this.val())||0) * (parseFloat($tr.find('#prod_dmedida option:selected').attr('data-cantidad'))||0)).toFixed(2) );
      $tr.find('#prod_importe').val( ((parseFloat($this.val())||0) * (parseFloat($tr.find('#prod_precio').val())||0)).toFixed(2) );

      total_exis_bultos += parseFloat($this.val())||0;
      total_exis_kilos += parseFloat($tr.find('#prod_kilos').val())||0;
      total_existencias += parseFloat($tr.find('#prod_importe').val())||0;
    });
    $('#total_exis_bultos').val(total_exis_bultos.toFixed(2));
    $('#total_exis_kilos').val(total_exis_kilos.toFixed(2));
    $('#total_existencias').val(total_existencias.toFixed(2));

    $('#total_exis_bultos_txt').text( util.darFormatoNum(total_exis_bultos.toFixed(2), '') );
    $('#total_exis_kilos_txt').text( util.darFormatoNum(total_exis_kilos.toFixed(2), '') );
    $('#total_existencias_txt').text( util.darFormatoNum(total_existencias.toFixed(2)) );

    // SUMA TOTALES *****************
    $("#suma_totales_bultos").text( util.darFormatoNum(
        parseFloat(total_exis_bultos.toFixed(2)) +
        Number(parseFloat($("#total_bultos").val()||0).toFixed(2))
      , '')
    );
    $("#suma_totales_kilos").text( util.darFormatoNum(
        parseFloat(total_exis_kilos.toFixed(2)) +
        Number(parseFloat($("#total_kilos").val()||0).toFixed(2))
      , '')
    );
    $("#suma_totales_existencias").text( util.darFormatoNum(
        parseFloat(total_existencias.toFixed(2)) +
        Number(parseFloat($("#total_facturas").val()||0).toFixed(2))
      )
    );

    // TOTAL DE INGRESOS *****************
    $("#total_ingres_bultos").text( util.darFormatoNum(
        parseFloat(total_exis_bultos.toFixed(2)) +
        Number(parseFloat($("#total_bultos").val()||0).toFixed(2)) +
        Number(parseFloat($("#total_otsingr_bultos").val()||0).toFixed(2))
      )
    );
    $("#total_ingres").text( util.darFormatoNum(
        parseFloat(total_existencias.toFixed(2)) +
        Number(parseFloat($("#total_facturas").val()||0).toFixed(2)) +
        Number(parseFloat($("#total_otsingr").val()||0).toFixed(2))
      )
    );

  },

  calculaGastos = function () {
    var total_compra_empa = 0, total_compra_empa_bultos = 0, total_compra_empa_kilos = 0;
    $('#table-compra_empa input#compe_bultos').each(function(index, el) {
      var $this = $(el),
      $tr = $this.parent().parent();

      $tr.find('#compe_kilos').val( ((parseFloat($this.val())||0) * (parseFloat($tr.find('#compe_dmedida option:selected').attr('data-cantidad'))||0)).toFixed(2) );
      $tr.find('#compe_importe').val( ((parseFloat($this.val())||0) * (parseFloat($tr.find('#compe_precio').val())||0)).toFixed(2) );

      total_compra_empa_bultos += parseFloat($this.val())||0;
      total_compra_empa_kilos += parseFloat($tr.find('#compe_kilos').val())||0;
      total_compra_empa += parseFloat($tr.find('#compe_importe').val())||0;
    });
    $('#total_compra_empa_bultos').val(total_compra_empa_bultos.toFixed(2));
    $('#total_compra_empa_kilos').val(total_compra_empa_kilos.toFixed(2));
    $('#total_compra_empa').val(total_compra_empa.toFixed(2));

    $('#total_compra_empa_bultos_txt').text( util.darFormatoNum(total_compra_empa_bultos.toFixed(2), '') );
    $('#total_compra_empa_kilos_txt').text( util.darFormatoNum(total_compra_empa_kilos.toFixed(2), '') );
    $('#total_compra_empa_txt').text( util.darFormatoNum(total_compra_empa.toFixed(2)) );


    var total_apatzin = 0;
    $('#table-apatzin input#apatzin_ddescripcion').each(function(index, el) {
      var $this = $(el),
      $tr = $this.parent().parent();

      $tr.find('#apatzin_importe').val( $tr.find('#apatzin_precio').val() );

      total_apatzin += parseFloat($tr.find('#apatzin_importe').val())||0;
    });
    $('#total_apatzin').val(total_apatzin.toFixed(2));
    $('#total_apatzin_txt').text( util.darFormatoNum(total_apatzin.toFixed(2)) );


    var total_costo_venta = 0;
    $('#table-costo_venta input#costo_venta_precio').each(function(index, el) {
      var $this = $(el),
      $tr = $this.parent().parent(),
      importe = ((parseFloat($this.val())||0) * (parseFloat($this.attr('data-bultos'))||0)).toFixed(2);

      $tr.find('#costo_venta_importe').text( util.darFormatoNum(importe) );

      total_costo_venta += parseFloat(importe)||0;
    });
    $('#total_costo_venta').val(total_costo_venta.toFixed(2));
    $('#total_costo_venta_txt').text( util.darFormatoNum(total_costo_venta.toFixed(2)) );

    var total_industrial = 0;
    $('#table-industrial input#industrial_precio').each(function(index, el) {
      var $this = $(el),
      $tr = $this.parent().parent(),
      importe = ((parseFloat($this.val())||0) * (parseFloat($("#total_industrial_kilos").val())||0)).toFixed(2);

      total_industrial += parseFloat(importe)||0;
    });
    $('#total_industrial').val(total_industrial.toFixed(2));
    $('#total_industrial_txt').text( util.darFormatoNum(total_industrial.toFixed(2)) );

  },

  eventsCalculos = function () {
    // Ingresos
    $('#table-existencia').on('keyup', 'input#prod_bultos, input#prod_precio', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaIngresos();
      }
    });
    $('#table-existencia').on('change', '#prod_dmedida', function(e) {
      var key = e.which;

      calculaIngresos();
    });

    // Egresos ********
    // compras empa
    $('#table-compra_empa').on('keyup', 'input#compe_bultos, input#compe_precio', function(e) {
      var key = e.which,
          $this = $(this),
          $tr   = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaGastos();
      }
    });
    $('#table-compra_empa').on('change', '#compe_dmedida', function(e) {
      var key = e.which;

      calculaGastos();
    });

    // apatzinGAN
    $('#table-apatzin').on('keyup', 'input#apatzin_precio', function(e) {
      var key = e.which,
          $this = $(this),
          $tr   = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaGastos();
      }
    });

    $('#table-costo_venta').on('keyup', 'input#costo_venta_precio', function(e) {
      var key = e.which,
          $this = $(this),
          $tr   = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaGastos();
      }
    });

    $('#table-industrial').on('keyup', 'input#industrial_precio', function(e) {
      var key = e.which,
          $this = $(this),
          $tr   = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaGastos();
      }
    });

  };

  /** Existencia del dia y compras de limon empacado
   */
    var btnAddExisComp = function () {
      $('#btn-add-gasto').on('click', function(event) {
        agregarExistencia('table-existencia', 'prod');
      });

      $('#btn-add-compra').on('click', function(event) {
        agregarExistencia('table-compra_empa', 'compe');
      });

      $('#btn-add-apatzin').on('click', function(event) {
        agregarApatzin('table-apatzin', 'apatzin');
      });
    };

    var agregarExistencia = function (tabla, prefix) {
      var $table = $('#'+tabla).find('tbody .row-total'),
          tr =  '<tr>'+
                '  <td>'+
                '    <input type="text" name="'+prefix+'_ddescripcion[]" class="span12" value="" id="'+prefix+'_ddescripcion">'+
                '    <input type="hidden" name="'+prefix+'_did_prod[]" class="span12" value="" id="'+prefix+'_did_prod">'+
                '  </td>'+
                '  <td>'+
                '    <select name="'+prefix+'_dmedida[]" id="'+prefix+'_dmedida" class="span12">'+
                '       '+$("#unidad_medidas").html()+
                '    </select>'+
                '  </td>'+
                '  <td class="cporte">'+
                '    <input type="text" name="'+prefix+'_bultos[]" value="" id="'+prefix+'_bultos" class="span12 vpositive" style="width: 80px;">'+
                '  </td>'+
                '  <td class="cporte">'+
                '    <input type="text" name="'+prefix+'_kilos[]" value="" id="'+prefix+'_kilos" class="span12" style="width: 80px;" readonly>'+
                '  </td>'+
                '  <td class="cporte">'+
                '    <input type="text" name="'+prefix+'_precio[]" value="" id="'+prefix+'_precio" class="span12 vpositive" style="width: 80px;">'+
                '  </td>'+
                '  <td class="cporte">'+
                '    <input type="text" name="'+prefix+'_importe[]" value="" id="'+prefix+'_importe" class="span12" style="width: 80px;" readonly>'+
                '  </td>'+
                '  <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>'+
                '</tr>';

      $(tr).insertBefore($table);
      $(".vpositive").numeric({ negative: false }); //Numero positivo
    };

    var btnDelExisComp = function () {
      $('#table-existencia').on('click', '.btn-del-gasto', function(event) {
        var $tr = $(this).parents('tr');
        $tr.remove();
        calculaIngresos();
      });

      $('#table-compra_empa').on('click', '.btn-del-gasto', function(event) {
        var $tr = $(this).parents('tr');
        $tr.remove();
        calculaIngresos();
      });

      $('#table-apatzin').on('click', '.btn-del-gasto', function(event) {
        var $tr = $(this).parents('tr');
        $tr.remove();
        calculaGastos();
      });
    };

    var agregarApatzin = function (tabla, prefix) {
      var $table = $('#'+tabla).find('tbody .row-total'),
          tr =  '<tr>'+
                '  <td>'+
                '    <input type="text" name="'+prefix+'_ddescripcion[]" class="span12" value="" id="'+prefix+'_ddescripcion">'+
                '  </td>'+
                '  <td>'+
                '    <input type="text" name="'+prefix+'_dmedida[]" class="span12" value="" id="'+prefix+'_dmedida">'+
                '  </td>'+
                '  <td class="cporte">'+
                '    <input type="text" name="'+prefix+'_precio[]" value="" id="'+prefix+'_precio" class="span12 vpositive" style="width: 80px;">'+
                '  </td>'+
                '  <td class="cporte">'+
                '    <input type="text" name="'+prefix+'_importe[]" value="" id="'+prefix+'_importe" class="span12" style="width: 80px;" readonly>'+
                '  </td>'+
                '  <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>'+
                '</tr>';

      $(tr).insertBefore($table);
      $(".vpositive").numeric({ negative: false }); //Numero positivo
    };

    var autocompleteClasifiLive = function () {
      $('#table-existencia').on('focus', 'input#prod_ddescripcion:not(.ui-autocomplete-input)', function(event) {
        $(this).autocomplete({
          // source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
          source: function (request, response) {
            if ($('#id_area').val()!='') {
              $.ajax({
                url: base_url + 'panel/facturacion/ajax_get_clasificaciones/',
                dataType: 'json',
                data: {
                  term : request.term,
                  type: $('#id_area').val()
                },
                success: function (data) {
                  response(data)
                }
              });
            } else {
              noty({"text": 'Seleccione una area.', "layout":"topRight", "type": 'error'});
            }
          },
          minLength: 1,
          selectFirst: true,
          select: function( event, ui ) {
            var $this = $(this),
                $tr = $this.parent().parent();

            $this.css("background-color", "#B0FFB0");

            $tr.find('#prod_did_prod').val(ui.item.id);

            $tr.find('#prod_dmedida').find('[value="'+ui.item.item.id_unidad+'"]').attr('selected', 'selected');
          }
        }).keydown(function(event){
          if(event.which == 8 || event == 46) {
            var $tr = $(this).parent().parent();

            $(this).css("background-color", "#FFD9B3");
            $tr.find('#prod_did_prod').val('');
          }
        });
      });

      $('#table-compra_empa').on('focus', 'input#compe_ddescripcion:not(.ui-autocomplete-input)', function(event) {
        $(this).autocomplete({
          // source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
          source: function (request, response) {
            if ($('#id_area').val()!='') {
              $.ajax({
                url: base_url + 'panel/facturacion/ajax_get_clasificaciones/',
                dataType: 'json',
                data: {
                  term : request.term,
                  type: $('#id_area').val()
                },
                success: function (data) {
                  response(data)
                }
              });
            } else {
              noty({"text": 'Seleccione una area.', "layout":"topRight", "type": 'error'});
            }
          },
          minLength: 1,
          selectFirst: true,
          select: function( event, ui ) {
            var $this = $(this),
                $tr = $this.parent().parent();

            $this.css("background-color", "#B0FFB0");

            $tr.find('#compe_did_prod').val(ui.item.id);

            $tr.find('#compe_dmedida').find('[value="'+ui.item.item.id_unidad+'"]').attr('selected', 'selected');
          }
        }).keydown(function(event){
          if(event.which == 8 || event == 46) {
            var $tr = $(this).parent().parent();

            $(this).css("background-color", "#FFD9B3");
            $tr.find('#compe_dmedida').val('');
          }
        });
      });

    };


});