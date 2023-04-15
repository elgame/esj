(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    onKeyPressProduccionCosto();

    onSubmit();
    existencia();
    existenciaPiso();
    existenciaReProceso();
    compraFruta();
    devolucionFruta();
    manoObraInsumos();
    descuentoVentas();
    comisionTerceros();
    industrial();

    btnAddGasto();
    btnDelGasto();
    onChanceImporteGastos();

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

  var onKeyPressProduccionCosto = function () {
    $('#table-produccion').on('keyup', '.produccion_costo', function(event) {
      var $tr = $(this).parent().parent(),
      $this = $(this),
      $cantidad = $tr.find('.produccion_cantidad'),
      $importe = $tr.find('.produccion_importe'),
      $timporte = $tr.find('.tproduccion_importe'),
      importe = 0;

      importe = (parseFloat($this.val())||0)*(parseFloat($cantidad.text())||0);
      $importe.text(importe);
      $timporte.val(importe);
    });
  };

  const existencia = () => {
    $('#table-existencia').on('keyup', '.existencia_costo', function(event) {
      let $tr = $(this).parents('tr');
      let cantidad = parseFloat($tr.find('.existencia_cantidad').val())||0;
      let costo = parseFloat($(this).val())||0;

      $tr.find('.existencia_importe').text(cantidad*costo);
      $tr.find('.existencia_importee').val(cantidad*costo);

      calculaTotalExistencia();
    });
  };
  const calculaTotalExistencia = () => {
    cantidadt = kilost = importet = 0;
    $("#table-existencia tbody tr:not(.footer)").each(function(index, el) {
      importet += parseFloat($(el).find('.existencia_importee').val())||0;
    });
    $('#exisImporte').text(importet);
  };

  const existenciaPiso = () => {
    $('#table-existencia-piso').on('focus', 'input.existenciaPiso_calibre:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/rastreabilidad/ajax_get_calibres/?tipo=c',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('.existenciaPiso_id_calibre').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('.existenciaPiso_id_calibre').val('');
        }
      });
    });

    $('#btnAddExisPiso').click(function(event) {
      const unidades = JSON.parse($('#unidades').val());
      htmlUnidad = '';
      $.each(unidades, function(index, val) {
         htmlUnidad += '<option value="' + val.id_unidad + '" data-cantidad="' + val.cantidad + '">' + val.nombre + '</option>';
      });

      html =
        '<tr>'+
          '<td>'+
            '<input type="text" name="existenciaPiso_calibre[]" value="" class="span12 existenciaPiso_calibre" required>'+
            '<input type="hidden" name="existenciaPiso_id_calibre[]" value="" class="span12 existenciaPiso_id_calibre" required>'+
          '</td>'+
          '<td>'+
            '<select name="existenciaPiso_id_unidad[]" class="span12 existenciaPiso_id_unidad" required>'+
            htmlUnidad+
            '</select>'+
          '</td>'+
          '<td><input type="text" name="existenciaPiso_cantidad[]" value="" class="span12 vpositive existenciaPiso_cantidad" required></td>'+
          '<td><input type="text" name="existenciaPiso_kilos[]" value="" class="span12 vpositive existenciaPiso_kilos" readonly></td>'+
          '<td><input type="text" name="existenciaPiso_costo[]" value="" class="span12 vpositive existenciaPiso_costo" required></td>'+
          '<td><input type="text" name="existenciaPiso_importe[]" value="" class="span12 vpositive existenciaPiso_importe" readonly></td>'+
          '<td style="width: 30px;">'+
            '<button type="button" class="btn btn-danger existenciaPiso_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
          '</td>'+
        '</tr>';
      $(html).insertBefore($('#table-existencia-piso tbody tr.footer'));
      $("#table-existencia-piso tbody .vpositive").numeric({ negative: false }); //Numero positivo
    });

    $('#table-existencia-piso').on('click', '.existenciaPiso_del', function(event) {
      var $tr = $(this).parents('tr');
      $tr.remove();

      calculaTotalExisPiso();
    });

    $('#table-existencia-piso').on('keyup', '.existenciaPiso_cantidad, .existenciaPiso_costo', function(event) {
      let $tr = $(this).parents('tr');
      let cantidad = parseFloat($tr.find('.existenciaPiso_cantidad').val())||0;
      let costo = parseFloat($tr.find('.existenciaPiso_costo').val())||0;
      let unidad = parseFloat($tr.find('.existenciaPiso_id_unidad option:selected').attr('data-cantidad'))||0;

      $tr.find('.existenciaPiso_kilos').val(cantidad*unidad);
      $tr.find('.existenciaPiso_importe').val(cantidad*costo);

      calculaTotalExisPiso();
    });
  };
  const calculaTotalExisPiso = () => {
    cantidadt = kilost = importet = 0;
    $("#table-existencia-piso tbody tr:not(.footer)").each(function(index, el) {
      cantidadt += parseFloat($(el).find('.existenciaPiso_cantidad').val())||0;
      kilost += parseFloat($(el).find('.existenciaPiso_kilos').val())||0;
      importet += parseFloat($(el).find('.existenciaPiso_importe').val())||0;
    });
    $('#exisPisoCantidad').text(cantidadt);
    $('#exisPisoKilos').text(kilost);
    $('#exisPisoImporte').text(importet);
  };

  const existenciaReProceso = () => {
    $('#table-existencia-reproceso').on('focus', 'input.existenciaRepro_calibre:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/rastreabilidad/ajax_get_calibres/?tipo=c',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('.existenciaRepro_id_calibre').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('.existenciaRepro_id_calibre').val('');
        }
      });
    });

    $('#btnAddExisRepro').click(function(event) {
      const unidades = JSON.parse($('#unidades').val());
      htmlUnidad = '';
      $.each(unidades, function(index, val) {
         htmlUnidad += '<option value="' + val.id_unidad + '" data-cantidad="' + val.cantidad + '">' + val.nombre + '</option>';
      });

      html =
      '<tr>'+
        '<td>'+
          '<input type="text" name="existenciaRepro_calibre[]" value="" class="span12 existenciaRepro_calibre" required>'+
          '<input type="hidden" name="existenciaRepro_id_calibre[]" value="" class="span12 existenciaRepro_id_calibre" required>'+
        '</td>'+
        '<td>'+
          '<select name="existenciaRepro_id_unidad[]" class="span12 existenciaRepro_id_unidad" required>'+
          htmlUnidad+
          '</select>'+
        '</td>'+
        '<td><input type="text" name="existenciaRepro_cantidad[]" value="" class="span12 vpositive existenciaRepro_cantidad" required></td>'+
        '<td><input type="text" name="existenciaRepro_kilos[]" value="" class="span12 vpositive existenciaRepro_kilos" readonly></td>'+
        '<td><input type="text" name="existenciaRepro_costo[]" value="" class="span12 vpositive existenciaRepro_costo" required></td>'+
        '<td><input type="text" name="existenciaRepro_importe[]" value="" class="span12 vpositive existenciaRepro_importe" readonly></td>'+
        '<td style="width: 30px;">'+
          '<button type="button" class="btn btn-danger existenciaRepro_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
        '</td>'+
      '</tr>';
      $(html).insertBefore($('#table-existencia-reproceso tbody tr.footer'));
      $("#table-existencia-reproceso tbody .vpositive").numeric({ negative: false }); //Numero positivo
    });

    $('#table-existencia-reproceso').on('click', '.existenciaRepro_del', function(event) {
      var $tr = $(this).parents('tr');
      $tr.remove();

      calculaTotalExisRePro();
    });

    $('#table-existencia-reproceso').on('keyup', '.existenciaRepro_cantidad, .existenciaRepro_costo', function(event) {
      let $tr = $(this).parents('tr');
      let cantidad = parseFloat($tr.find('.existenciaRepro_cantidad').val())||0;
      let costo = parseFloat($tr.find('.existenciaRepro_costo').val())||0;
      let unidad = parseFloat($tr.find('.existenciaRepro_id_unidad option:selected').attr('data-cantidad'))||0;

      $tr.find('.existenciaRepro_kilos').val(cantidad*unidad);
      $tr.find('.existenciaRepro_importe').val(cantidad*costo);

      calculaTotalExisRePro();
    });
  };
  const calculaTotalExisRePro = () => {
    cantidadt = kilost = importet = 0;
    $("#table-existencia-reproceso tbody tr:not(.footer)").each(function(index, el) {
      cantidadt += parseFloat($(el).find('.existenciaRepro_cantidad').val())||0;
      kilost += parseFloat($(el).find('.existenciaRepro_kilos').val())||0;
      importet += parseFloat($(el).find('.existenciaRepro_importe').val())||0;
    });
    $('#exisReproCantidad').text(cantidadt);
    $('#exisReproKilos').text(kilost);
    $('#exisReproImporte').text(importet);
  };


  const compraFruta = () => {
    $('#table-compra-fruta-comp').on('focus', 'input.frutaCompra_calibre:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/rastreabilidad/ajax_get_calibres/?tipo=c',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('.frutaCompra_id_calibre').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('.frutaCompra_id_calibre').val('');
        }
      });
    });

    $('#btnAddFrutaCom').click(function(event) {
      const unidades = JSON.parse($('#unidades').val());
      htmlUnidad = '';
      $.each(unidades, function(index, val) {
         htmlUnidad += '<option value="' + val.id_unidad + '" data-cantidad="' + val.cantidad + '">' + val.nombre + '</option>';
      });

      html =
        '<tr>'+
          '<td>'+
            '<input type="text" name="frutaCompra_calibre[]" value="" class="span12 frutaCompra_calibre" required>'+
            '<input type="hidden" name="frutaCompra_id_calibre[]" value="" class="span12 frutaCompra_id_calibre" required>'+
          '</td>'+
          '<td>'+
            '<select name="frutaCompra_id_unidad[]" class="span12 frutaCompra_id_unidad" required>'+
            htmlUnidad+
            '</select>'+
          '</td>'+
          '<td><input type="text" name="frutaCompra_cantidad[]" value="" class="span12 vpositive frutaCompra_cantidad" required></td>'+
          '<td><input type="text" name="frutaCompra_kilos[]" value="" class="span12 vpositive frutaCompra_kilos" readonly></td>'+
          '<td><input type="text" name="frutaCompra_costo[]" value="" class="span12 vpositive frutaCompra_costo" required></td>'+
          '<td><input type="text" name="frutaCompra_importe[]" value="" class="span12 vpositive frutaCompra_importe" readonly></td>'+
          '<td style="width: 30px;">'+
            '<button type="button" class="btn btn-danger frutaCompra_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
          '</td>'+
        '</tr>';
      $(html).insertBefore($('#table-compra-fruta-comp tbody tr.footer'));
      $("#table-compra-fruta-comp tbody .vpositive").numeric({ negative: false }); //Numero positivo
    });

    $('#table-compra-fruta-comp').on('click', '.frutaCompra_del', function(event) {
      var $tr = $(this).parents('tr');
      $tr.remove();

      calculaTotalCompraFruta();
    });

    $('#table-compra-fruta-comp').on('keyup', '.frutaCompra_cantidad, .frutaCompra_costo', function(event) {
      let $tr = $(this).parents('tr');
      let cantidad = parseFloat($tr.find('.frutaCompra_cantidad').val())||0;
      let costo = parseFloat($tr.find('.frutaCompra_costo').val())||0;
      let unidad = parseFloat($tr.find('.frutaCompra_id_unidad option:selected').attr('data-cantidad'))||0;

      $tr.find('.frutaCompra_kilos').val(cantidad*unidad);
      $tr.find('.frutaCompra_importe').val(cantidad*costo);

      calculaTotalCompraFruta();
    });
  };
  const calculaTotalCompraFruta = () => {
    cantidadt = kilost = importet = 0;
    $("#table-compra-fruta-comp tbody tr:not(.footer)").each(function(index, el) {
      cantidadt += parseFloat($(el).find('.frutaCompra_cantidad').val())||0;
      kilost += parseFloat($(el).find('.frutaCompra_kilos').val())||0;
      importet += parseFloat($(el).find('.frutaCompra_importe').val())||0;
    });
    $('#frutaCompraCantidad').text(cantidadt);
    $('#frutaCompraKilos').text(kilost);
    $('#frutaCompraImporte').text(importet);
  };


  const devolucionFruta = () => {
    $('#table-devolucion-fruta').on('focus', 'input.devFruta_calibre:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/rastreabilidad/ajax_get_calibres/?tipo=c',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('.devFruta_id_calibre').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('.devFruta_id_calibre').val('');
        }
      });
    });

    $('#btnAddDevFruta').click(function(event) {
      const unidades = JSON.parse($('#unidades').val());
      htmlUnidad = '';
      $.each(unidades, function(index, val) {
         htmlUnidad += '<option value="' + val.id_unidad + '" data-cantidad="' + val.cantidad + '">' + val.nombre + '</option>';
      });

      html =
        '<tr>'+
          '<td>'+
            '<input type="text" name="devFruta_calibre[]" value="" class="span12 devFruta_calibre" required>'+
            '<input type="hidden" name="devFruta_id_calibre[]" value="" class="span12 devFruta_id_calibre" required>'+
          '</td>'+
          '<td>'+
            '<select name="devFruta_id_unidad[]" class="span12 devFruta_id_unidad" required>'+
            htmlUnidad+
            '</select>'+
          '</td>'+
          '<td><input type="text" name="devFruta_cantidad[]" value="" class="span12 vpositive devFruta_cantidad" required></td>'+
          '<td><input type="text" name="devFruta_kilos[]" value="" class="span12 vpositive devFruta_kilos" readonly></td>'+
          '<td><input type="text" name="devFruta_costo[]" value="" class="span12 vpositive devFruta_costo" required></td>'+
          '<td><input type="text" name="devFruta_importe[]" value="" class="span12 vpositive devFruta_importe" readonly></td>'+
          '<td style="width: 30px;">'+
            '<button type="button" class="btn btn-danger devFruta_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
          '</td>'+
        '</tr>';
      $(html).insertBefore($('#table-devolucion-fruta tbody tr.footer'));
      $("#table-devolucion-fruta tbody .vpositive").numeric({ negative: false }); //Numero positivo
    });

    $('#table-devolucion-fruta').on('click', '.devFruta_del', function(event) {
      var $tr = $(this).parents('tr');
      $tr.remove();

      calculaTotalDevolucionFruta();
    });

    $('#table-devolucion-fruta').on('keyup', '.devFruta_cantidad, .devFruta_costo', function(event) {
      let $tr = $(this).parents('tr');
      let cantidad = parseFloat($tr.find('.devFruta_cantidad').val())||0;
      let costo = parseFloat($tr.find('.devFruta_costo').val())||0;
      let unidad = parseFloat($tr.find('.devFruta_id_unidad option:selected').attr('data-cantidad'))||0;

      $tr.find('.devFruta_kilos').val(cantidad*unidad);
      $tr.find('.devFruta_importe').val(cantidad*costo);

      calculaTotalDevolucionFruta();
    });
  };
  const calculaTotalDevolucionFruta = () => {
    cantidadt = kilost = importet = 0;
    $("#table-devolucion-fruta tbody tr:not(.footer)").each(function(index, el) {
      cantidadt += parseFloat($(el).find('.devFruta_cantidad').val())||0;
      kilost += parseFloat($(el).find('.devFruta_kilos').val())||0;
      importet += parseFloat($(el).find('.devFruta_importe').val())||0;
    });
    $('#devFrutaCantidad').text(cantidadt);
    $('#devFrutaKilos').text(kilost);
    $('#devFrutaImporte').text(importet);
  };

  const manoObraInsumos = () => {
    // $('#table-manoo-insumos').on('focus', 'input.devFruta_calibre:not(.ui-autocomplete-input)', function(event) {
    //   $(this).autocomplete({
    //     source: base_url+'panel/rastreabilidad/ajax_get_calibres/?tipo=c',
    //     minLength: 1,
    //     selectFirst: true,
    //     select: function( event, ui ) {
    //       var $this = $(this),
    //           $tr = $this.parent().parent();

    //       $this.css("background-color", "#B0FFB0");

    //       $tr.find('.devFruta_id_calibre').val(ui.item.id);
    //     }
    //   }).keydown(function(event){
    //     if(event.which == 8 || event == 46) {
    //       var $tr = $(this).parent().parent();

    //       $(this).css("background-color", "#FFD9B3");
    //       $tr.find('.devFruta_id_calibre').val('');
    //     }
    //   });
    // });

    $('#btnAddManooInsumos').click(function(event) {
      const unidades = JSON.parse($('#unidades').val());
      htmlUnidad = '';
      $.each(unidades, function(index, val) {
         htmlUnidad += '<option value="' + val.id_unidad + '" data-cantidad="' + val.cantidad + '">' + val.nombre + '</option>';
      });

      html =
        '<tr>'+
          '<td>'+
            '<input type="text" name="manooInsumos_descripcion[]" value="" class="span12 manooInsumos_descripcion" required>'+
          '</td>'+
          '<td>'+
            '<select name="manooInsumos_id_unidad[]" class="span12 manooInsumos_id_unidad" required>'+
            htmlUnidad+
            '</select>'+
          '</td>'+
          '<td><input type="text" name="manooInsumos_cantidad[]" value="" class="span12 vpositive manooInsumos_cantidad" required></td>'+
          '<td><input type="text" name="manooInsumos_kilos[]" value="" class="span12 vpositive manooInsumos_kilos" readonly></td>'+
          '<td><input type="text" name="manooInsumos_costo[]" value="" class="span12 vpositive manooInsumos_costo" required></td>'+
          '<td><input type="text" name="manooInsumos_importe[]" value="" class="span12 vpositive manooInsumos_importe" readonly></td>'+
          '<td style="width: 30px;">'+
            '<button type="button" class="btn btn-danger manooInsumos_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
          '</td>'+
        '</tr>';
      $(html).insertBefore($('#table-manoo-insumos tbody tr.footer'));
      $("#table-manoo-insumos tbody .vpositive").numeric({ negative: false }); //Numero positivo
    });

    $('#table-manoo-insumos').on('click', '.manooInsumos_del', function(event) {
      var $tr = $(this).parents('tr');
      $tr.remove();

      calculaTotalManooInsumos();
    });

    $('#table-manoo-insumos').on('keyup', '.manooInsumos_cantidad, .manooInsumos_costo', function(event) {
      let $tr = $(this).parents('tr');
      let cantidad = parseFloat($tr.find('.manooInsumos_cantidad').val())||0;
      let costo = parseFloat($tr.find('.manooInsumos_costo').val())||0;
      let unidad = parseFloat($tr.find('.manooInsumos_id_unidad option:selected').attr('data-cantidad'))||0;

      $tr.find('.manooInsumos_kilos').val(cantidad*unidad);
      $tr.find('.manooInsumos_importe').val(cantidad*costo);

      calculaTotalManooInsumos();
    });
  };
  const calculaTotalManooInsumos = () => {
    cantidadt = kilost = importet = 0;
    $("#table-manoo-insumos tbody tr:not(.footer)").each(function(index, el) {
      cantidadt += parseFloat($(el).find('.manooInsumos_cantidad').val())||0;
      kilost += parseFloat($(el).find('.manooInsumos_kilos').val())||0;
      importet += parseFloat($(el).find('.manooInsumos_importe').val())||0;
    });
    $('#manooInsumosCantidad').text(cantidadt);
    $('#manooInsumosKilos').text(kilost);
    $('#manooInsumosImporte').text(importet);
  };

  const descuentoVentas = () => {
    $('#btnAddCostoVentas').click(function(event) {
      html =
      '<tr>'+
        '<td>'+
          '<input type="text" name="descuentoVentas_nombre[]" value="" class="span12 descuentoVentas_nombre" required>'+
          '<input type="hidden" name="descuentoVentas_id[]" value="" class="span12 descuentoVentas_id">'+
          '<input type="hidden" name="descuentoVentas_delete[]" value="false" class="span12 descuentoVentas_delete">'+
        '</td>'+
        '<td>'+
          '<input type="text" name="descuentoVentas_descripcion[]" value="" class="span12 descuentoVentas_descripcion" required>'+
        '</td>'+
        '<td><input type="text" name="descuentoVentas_importe[]" value="" class="span12 vpositive descuentoVentas_importe" required></td>'+
        '<td style="width: 30px;">'+
          '<button type="button" class="btn btn-danger descuentoVentas_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
        '</td>'+
      '</tr>';
      $(html).insertBefore($('#table-costo-ventas tbody tr.footer'));
      $("#table-costo-ventas tbody .vpositive").numeric({ negative: false }); //Numero positivo
    });

    $('#table-costo-ventas').on('click', '.descuentoVentas_del', function(event) {
      var $tr = $(this).parents('tr');
      if((parseInt($tr.find('.descuentoVentas_id').val())||0) > 0) {
        $tr.find('.descuentoVentas_delete').val('true');
        $tr.hide();
      } else {
        $tr.remove();
      }

      calculaTotalCostoVentas();
    });

    $('#table-costo-ventas').on('keyup', '.descuentoVentas_importe', function(event) {
      let $tr = $(this).parents('tr');

      calculaTotalCostoVentas();
    });
  };
  const calculaTotalCostoVentas = () => {
    cantidadt = kilost = importet = 0;
    $("#table-costo-ventas tbody tr:not(.footer)").each(function(index, el) {
      if($(el).find('.descuentoVentas_delete').val() == 'false') {
        importet += parseFloat($(el).find('.descuentoVentas_importe').val())||0;
      }
    });
    $('#descuentoVentas_importe').text(importet);
  };

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

  const industrial = () => {
    $('#table-industrial').on('keyup', '.industrial_costo', function(event) {
      let $tr = $(this).parents('tr');
      let cantidad = parseFloat($tr.find('.industrial_kilos').val())||0;
      let costo = parseFloat($tr.find('.industrial_costo').val())||0;

      $tr.find('.industrial_importe').val(cantidad*costo);

      calculaTotalIndustrial();
    });
  };
  const calculaTotalIndustrial = () => {
    cantidadt = kilost = importet = 0;
    $("#table-industrial tbody tr:not(.footer)").each(function(index, el) {
      cantidadt += parseFloat($(el).find('.industrial_kilos').val())||0;
      importet += parseFloat($(el).find('.industrial_importe').val())||0;
    });
    $('#indusKilos').text(cantidadt);
    $('#indusImporte').text(importet);
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
                  '<button type="button" class="btn btn-info btn-show-cat" style="padding: 2px 7px 2px;"><i class="icon-edit"></i></button>'+
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

});