(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    submitForm();
    btnAddIngreso();
    // btnAddMovimientos();
    btnDelIngreso();

    btnAddOtros();
    btnDelOtros();

    onChanceIngresos();

    cargaRemisiones();
    btnDelRemision();
    onChangeTotalRemisiones();
    obtenRemisionesAjax();

    onChanceDenominacionNum();
    onChanceTotalDenominacion();

    btnAddGasto();
    btnDelGasto();
    btnShowGastoCat();
    btnAddTraspaso();
    btnDelTraspaso();

    btnAddDeudor();
    btnDelDeudor();
    onChanceImporteDeudores();
    autocompleteDeudoresLive();

    autocompleteCategorias();
    autocompleteCategoriasLive();

    onChanceImporteGastos();
    onChanceImporteTraspaso();

    $('#total-efectivo-diferencia').text(util.darFormatoNum($('#ttotal-diferencia').val()));

    cargaMovimientos();
    searchModalMovimientos();

    $("#lista_remisiones_modal, #lista_movimientos_modal").filterTable();

    var preventClickCerrar = false;
    if ($(".btnCerrarCaja").length > 0)
    {
      $(".btnCerrarCaja").click(function (e) {
        if (!preventClickCerrar) {
          preventClickCerrar = true;
        } else {
          e.preventDefault();
        }
      });
    }
  });

  var submitForm = function () {
    $('#frmcajachica').submit(function(event) {
      if ($('#btnGuardar').length == 0) {
        event.preventDefault();
      }
    });
  }

  var btnAddIngreso = function () {
    $('#btn-add-ingreso').on('click', function(event) {
      agregarIngreso();
    });
  };

  var btnDelIngreso = function () {
    $('#table-ingresos').on('click', '.btn-del-ingreso', function(event) {
      var $tr = $(this).parents('tr'),
          $ingreso_id_ingresos = $tr.find('#ingreso_id_ingresos'),
          $ingreso_del = $tr.find('#ingreso_del'),

          id = $tr.find('.gasto-cargo-id').val(),
          $totalRepo = $('#repo-'+id).find('.reposicion-importe'),
          total = 0;

      if ($ingreso_id_ingresos.val() != '') {
        $ingreso_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.remove();
      }

      calculaTotalIngresos();
    });
  };

  var agregarIngreso = function (movimiento) {
    var add_band = true;

    if (movimiento && $('#table-ingresos').find('.ingreso_concepto_id[value='+movimiento.id+']').length > 0) {
      add_band = confirm("Ya esta agregado el ingreso "+movimiento.poliza+" estas seguro de agregarlo de nuevo?");
    }
    if (add_band) {
      var poliza = '', concepto = '', id = '', abono = '0', idcategoria = '', empresa = '';
      banco    = '';
      proveedor  = '';
      if (movimiento) {
        banco       = movimiento.banco;
        proveedor   = movimiento.proveedor;
        poliza      = movimiento.poliza;
        concepto    = movimiento.concepto;
        id          = movimiento.id;
        abono       = movimiento.total;
        idcategoria = movimiento.idcategoria;
        empresa     = movimiento.empresa;
      }

      var $table = $('#table-ingresos').find('tbody'),
          tr =  '<tr>' +
                  '<td style="width: 100px;">' +
                    '<input type="hidden" name="ingreso_id_ingresos[]" value="" id="ingreso_id_ingresos">'+
                    '<input type="hidden" name="ingreso_del[]" value="" id="ingreso_del">'+
                    '<input type="text" name="ingreso_empresa[]" value="'+empresa+'" class="input-small gasto-cargo" style="width: 150px;" required>' +
                    '<input type="hidden" name="ingreso_empresa_id[]" value="'+idcategoria+'" class="input-small vpositive gasto-cargo-id">' +
                  '</td>' +
                  '<td style="width: 40px;">' +
                    '<select name="ingreso_nomenclatura[]" class="ingreso_nomenclatura" style="width: 70px;">' +
                      $('#nomeclaturas_base').html() +
                    '</select>' +
                  '</td>' +
                  '<td style=""><input type="text" name="ingreso_banco[]" value="'+banco+'" class="ingreso_banco span12" maxlength="50" placeholder="Banco" style=""></td>' +
                  '<td style=""><input type="text" name="ingreso_poliza[]" value="'+poliza+'" class="ingreso_poliza span12" maxlength="100" placeholder="Poliza" style=""></td>' +
                  '<td>' +
                    '<input type="text" name="ingreso_nombre[]" value="'+proveedor+'" class="ingreso-nombre span12" maxlength="130" placeholder="Nombre" required>' +
                  '</td>' +
                  '<td>' +
                    '<input type="text" name="ingreso_concepto[]" value="'+concepto+'" class="ingreso-concepto span12" maxlength="500" placeholder="Concepto" required>' +
                    '<input type="hidden" name="ingreso_concepto_id[]" value="'+id+'" class="ingreso_concepto_id span12" placeholder="Concepto">' +
                  '</td>' +
                  '<td style=""><input type="text" name="ingreso_monto[]" value="'+abono+'" class="ingreso-monto vpositive input-small" placeholder="Monto" required></td>' +
                  '<td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-ingreso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>' +
                '</tr>';

      $(tr).appendTo($table);
      $(".vpositive").numeric({ negative: false }); //Numero positivo
    }
  };

  var btnAddOtros = function () {
    $('#btn-add-otros').on('click', function(event) {
      agregarOtros();
    });
  };

  var btnDelOtros = function () {
    $('#table-remisiones').on('click', '.btn-del-otros', function(event) {
      var $tr = $(this).parents('tr');
      if ($tr.find('.remision_row').val() == '') {
        $tr.remove();
      } else {
        $tr.find('#remision_del').val('true');
        $tr.hide();
      }
      calculaTotalIngresos();
    });
  };

  var agregarRemisiones = function (remision) {
    var $table = $('#table-remisiones').find('tbody .row-total'),
        tr;

    if ($('#table-remisiones').find('.remision-id[value='+remision.id+']').length == 0) {
      var numRemision = '', folio = '', id = '', abono = '0', concepto = '', idempresa = '', empresa = '', fecha = '';
      if (remision) {
        id           = remision.id;
        numRemision  = remision.numremision;
        abono        = remision.total;
        foliofactura = remision.foliofactura;
        concepto     = remision.concepto;
        idempresa    = remision.idempresa;
        empresa      = remision.empresa;
        fecha        = remision.fecha;
      }

      tr =  '<tr>' +
              '<td style="">' +
                '<input type="text" name="remision_empresa[]" value="'+empresa+'" class="gasto-cargo" style="" required>' +
                '<input type="hidden" name="remision_empresa_id[]" value="'+idempresa+'" class="input-small vpositive gasto-cargo-id">' +
                '<input type="hidden" name="remision_row[]" value="" class="input-small vpositive remision_row">' +
              '</td>' +
              '<td style=""><input type="text" name="remision_numero[]" value="'+numRemision+'" class="remision-numero vpositive" placeholder="" readonly style=""></td>' +
              '<td style=""><input type="date" name="remision_fecha[]" value="'+fecha+'" class="remision_fecha" placeholder="Fecha" style=""></td>' +
              '<td style="width: 40px;">' +
                '<select name="remision_nomenclatura[]" class="remision_nomenclatura" style="width: 70px;">' +
                  $('#nomeclaturas_base').html() +
                '</select>' +
              '</td>' +
              '<td colspan="3">' +
                '<input type="text" name="remision_concepto[]" value="'+concepto+'" class="remision-concepto span12" maxlength="500" placeholder="Nombre" required>' +
                '<input type="hidden" name="remision_id[]" value="'+id+'" class="remision-id span12" required>' +
              '</td>' +
              '<td style=""><input type="text" name="remision_importe[]" value="'+abono+'" class="remision-importe vpositive" placeholder="Importe" required></td>' +
              '<td style="width: 30px;">'+
                '<button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
                '<input type="hidden" name="remision_del[]" value="" id="remision_del">'+
              '</td>' +
            '</tr>';

      $(tr).insertBefore($table);
      $(".vpositive").numeric({ negative: false }); //Numero positivo
    } else {
      alert("Ya esta agregada la remisión "+remision.numremision+" no puedes agregarla en el mismo corte.");
    }
  };

  var calculaTotalIngresos = function () {
    var total = 0;
    $('.ingreso-monto').each(function(index, el) {
      if ($(this).parents("tr").css('display') != 'none')
        total += parseFloat($(this).val() || 0);
    });

    // $('.otros-monto').each(function(index, el) {
    //   total += parseFloat($(this).val() || 0);
    // });

    $('.remision-importe').each(function(index, el) {
      if ($(this).parents("tr").css('display') != 'none')
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

          agregarRemisiones({
            id: $this.attr('data-id'), numremision: $this.attr('data-numremision'),
            total: $this.attr('data-total'), foliofactura: $this.attr('data-foliofactura'),
            concepto: $this.attr('data-concepto'),
            idempresa: $this.attr('data-idempresa'), empresa: $this.attr('data-empresa'),
            fecha: $this.attr('data-fecha')
          });

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
      $('#accion_catalogos').val('true');
      $('#accion_catalogos_tipo').val('gasto');
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

    $('#btnModalCatalogosSel').on('click', btnModalCatalogosSel);
    btnAddGastoComprobar();
    btnShowCompGasto();

    autocompleteEmpresa();
    autocompleteCultivo();
    autocompleteRanchos();
    autocompleteCentroCosto();
    autocompleteActivos();
  };

  var agregarGasto = function () {
    var tabla_gastos = $('#accion_catalogos_tipo').val() == 'gasto_comp'? '#table-gastos-comprobar': '#table-gastos';
    var prefix_gastos = $('#accion_catalogos_tipo').val() == 'gasto_comp'? 'comprobar_': '';
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
      var $table = $(tabla_gastos).find('tbody .row-total'),
          tr =  '<tr>' +
                  (prefix_gastos!=''? '<td></td>': '')+
                  '<td style="">'+
                    '<input type="hidden" name="gasto_'+prefix_gastos+'id_gasto[]" value="" id="gasto_id_gasto">'+
                    '<input type="hidden" name="gasto_'+prefix_gastos+'del[]" value="" id="gasto_del">'+
                    '<input type="text" name="'+prefix_gastos+'codigoArea[]" value="" id="codigoArea" class="span12 showCodigoAreaAuto" required>'+
                    '<input type="hidden" name="'+prefix_gastos+'codigoAreaId[]" value="" id="codigoAreaId" class="span12" required>'+
                    '<input type="hidden" name="'+prefix_gastos+'codigoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12" required>'+
                    '<i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>'+
                    '<input type="hidden" name="'+prefix_gastos+'area[]" value="'+ area +'" class="area span12">'+
                    '<input type="hidden" name="'+prefix_gastos+'areaId[]" value="'+ areaId +'" class="areaId span12">'+
                    '<input type="hidden" name="'+prefix_gastos+'rancho[]" value="'+ rancho +'" class="rancho span12">'+
                    '<input type="hidden" name="'+prefix_gastos+'ranchoId[]" value="'+ ranchoId +'" class="ranchoId span12">'+
                    '<input type="hidden" name="'+prefix_gastos+'centroCosto[]" value="'+ centroCosto +'" class="centroCosto span12">'+
                    '<input type="hidden" name="'+prefix_gastos+'centroCostoId[]" value="'+ centroCostoId +'" class="centroCostoId span12">'+
                    '<input type="hidden" name="'+prefix_gastos+'activos[]" value="'+ activos +'" class="activos span12">'+
                    '<input type="hidden" name="'+prefix_gastos+'activoId[]" value="'+ activoId +'" class="activoId span12">'+
                    '<input type="hidden" name="'+prefix_gastos+'empresaId[]" value="'+ empresaId +'" class="empresaId span12">'+
                  '</td>'+
                  '<td style="">' +
                    '<input type="text" name="gasto_'+prefix_gastos+'empresa[]" value="'+ dempresa +'" class="span12 gasto-cargo" readonly>' +
                    '<input type="hidden" name="gasto_'+prefix_gastos+'empresa_id[]" value="'+ did_categoria +'" class="input-small vpositive gasto-cargo-id">' +
                  '</td>' +
                  '<td style="">' +
                    '<select name="gasto_'+prefix_gastos+'nomenclatura[]" class="span12 ingreso_nomenclatura">' +
                      $('#nomeclaturas_base').html() +
                    '</select>' +
                  '</td>' +
                  // '<td style=""><input type="text" name="gasto_folio[]" value="" class="span12 gasto-folio"></td>' +
                  '<td style="">' +
                    '<input type="text" name="gasto_'+prefix_gastos+'nombre[]" value="" class="span12 gasto-nombre">' +
                  '</td>' +
                  '<td style="">' +
                    '<input type="text" name="gasto_'+prefix_gastos+'concepto[]" value="" class="span12 gasto-concepto">' +
                  '</td>' +
                  '<td style="">'+
                    '<input type="checkbox" value="si" class="gasto-reposicion">'+
                    '<input type="hidden" name="gasto_'+prefix_gastos+'reposicion[]" value="f" class="gasto-reposicionhid">'+
                  '</td>'+
                  '<td style=""><input type="text" name="gasto_'+prefix_gastos+'importe[]" value="0" class="span12 vpositive gasto-importe"></td>' +
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
    $('#table-gastos, #table-gastos-comprobar').on('click', '.btn-del-gasto', function(event) {
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
      calculaTotalGastosComprobar();
      calculaCorte();
    });

    $('#table-gastos').on('change', '.gasto-reposicion', function(event) {
      var $tr = $(this).parents('tr');
      $tr.find('.gasto-reposicionhid').val( ($(this).is(':checked')? 't': 'f') );
      console.log($tr.find('.gasto-reposicionhid').val());
    });
  };

  var btnModalCatalogosSel = function(event) {
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
  };

  var btnAddGastoComprobar = function () {
    $('#btn-add-gasto-comprobar').on('click', function(event) {
      $('#accion_catalogos').val('true');
      $('#accion_catalogos_tipo').val('gasto_comp');
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

    // $('#btnModalCatalogosSel').on('click', btnModalCatalogosSel);
  };

  var $trGastoCat;
  var btnShowGastoCat = function () {
    var setDataGastos = function (event) {
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
    }

    $('#table-gastos').on('click', '.btn-show-cat', setDataGastos);
    $('#table-gastos-comprobar').on('click', '.btn-show-cat', setDataGastos);
  };

  var btnShowCompGasto = function () {
    var $trGasto;

    $('#table-gastos-comprobar').on('click', '.btn-show-comp-gasto', function(event) {
      $trGasto = $(this).parents('tr');
      $('#modalCompGastos').modal('show');
      $('#compGasto_id_empresa').val($trGasto.find('.gasto-cargo-id').val());
      $('#compGasto_id_gasto').val($trGasto.find('#gasto_id_gasto').val());
      $('#compGasto_importe').text($trGasto.find('.gasto-importe').val());
      $('#compGastoMonto').val($trGasto.find('.gasto-importe').val());
    });
    $('#modalCompGastos').on('shown', function () {
      $('#compGastoMonto').focus();
    });

    $('#btnModalCompGasto').on('click', function(event) {
      if ((parseFloat($('#compGastoMonto').val())||0) > 0) {
        var remisiones = [], gastos = [];
        $('#tableComGastoRemisiones .compGastoAll').each(function(index, el) {
          remisiones.push( JSON.parse(decodeURIComponent($(this).val())) );
        });
        $('#tableComGastoGastos .compGastoGAll').each(function(index, el) {
          gastos.push( JSON.parse(decodeURIComponent($(this).val())) );
        });

        var params = {
          'id_gasto'    : $('#compGasto_id_gasto').val(),
          'id_empresa'  : $('#compGasto_id_empresa').val(),
          'importe_old' : $('#compGasto_importe').text(),
          'importe'     : $('#compGastoMonto').val(),
          'fno_caja'    : $('#fno_caja').val(),
          'fecha_caja'  : $('#fecha_caja').val(),
          'remisiones'  : remisiones,
          'gastos'      : gastos
        };
        console.log(params);
        $.post(base_url+'panel/caja_chica/ajax_registra_gasto_comp/', params, function(json, textStatus) {
          console.log(json, textStatus);
          $('#modalCompGastos').modal('hide');
          $trGasto.remove();
        }, 'json');
      } else {
        noty({"text": 'El monto es requerido.', "layout":"topRight", "type": 'error'});
      }
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
        // if(parseInt($("#did_empresa").val()) > 0)
        //   params.did_empresa = $("#did_empresa").val();
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
        calculaTotalGastosComprobar();
        calculaCorte();
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

  var calculaTotalGastosComprobar = function () {
    var total = 0;
    $('#table-gastos-comprobar .gasto-importe').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });
    $('input#ttotal-gastos-comprobar').val(total.toFixed(2));
  };

  var calculaCorte = function () {
    var total = 0;

    total = parseFloat($('#total-saldo-ingresos').val() || 0) + parseFloat($('#ttotal-acreedores').val() || 0) -
      parseFloat($('#total-boletas').val() || 0) - (parseFloat($('#ttotal-gastos').val() || 0)) - (parseFloat($('#ttotal-deudores').val())||0);
    $('#ttotal-corte').val(total.toFixed(2));
  };

  var cargaMovimientos = function () {
    $('#carga-movimientos').on('click', function(event) {
      var $table = $('#table-modal-movimientos'),
          html = '',
          $this;

      if ($('.chk-movimiento:checked').length > 0) {
        $('.chk-movimiento:checked').each(function(index, el) {
          $this = $(this);

          agregarIngreso({
            id: $this.attr('data-id'), total: $this.attr('data-total'),
            proveedor: $this.attr('data-proveedor'), poliza: $this.attr('data-poliza'),
            banco: $this.attr('data-banco'), concepto: $this.attr('data-concepto'),
            idcategoria: $this.attr('data-idcategoria'),
            empresa: $this.attr('data-empresa'),
          });
        });

        calculaTotalIngresos();

        $('#modal-movimientos').modal('hide');
      } else {
        noty({"text": 'Seleccione al menos un movimiento.', "layout":"topRight", "type": 'error'});
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
                    '<option value="caja_general">Caja Distribuidora</option>'+
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

  var btnAddTraspaso = function () {
    $('#btn-add-traspaso').on('click', function(event) {
      agregarTraspaso();
    });
  };
  var agregarTraspaso = function () {
    var $table = $('#table-traspasos').find('tbody .row-total'),
        tr = '<tr>'+
                '<td>'+
                  // '<select name="traspaso_tipo[]" class="span12 ingreso_nomenclatura">'+
                  //   '<option value="t">Ingreso</option>'+
                  //   '<option value="f">Egreso</option>'+
                  // '</select>'+
                  '<select name="traspaso_tipo[]" class="span12 traspaso_tipo">'+
                    '<option value="otros">Otros</option>'+
                    '<option value="caja_limon">Caja limón</option>'+
                    '<option value="caja_gastos">Caja gastos</option>'+
                    '<option value="caja_general">Caja Distribuidora</option>'+
                  '</select>'+
                  '<input type="hidden" name="traspaso_id_traspaso[]" value="" id="traspaso_id_traspaso">'+
                  '<input type="hidden" name="traspaso_del[]" value="" id="traspaso_del">'+
                '</td>'+
                '<td></td>'+
                '<td>'+
                  '<select name="traspaso_afectar_fondo[]" class="span12 traspaso_afectar_fondo">'+
                    '<option value="f">No</option>'+
                    '<option value="t">Si</option>'+
                  '</select>'+
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

  var obtenRemisionesAjax = function () {
    $('#modal-remisiones').on('show', function () {
      $.getJSON(base_url+'panel/caja_chica/ajax_get_remisiones', function(json, textStatus) {
        var html = '';
        for (var key in json) {
          html += '<tr>'+
              '<td><input type="checkbox" class="chk-remision" data-id="'+json[key].id_factura+'" '+
                'data-numremision="'+json[key].serie+json[key].folio+'" data-total="'+json[key].saldo+'" '+
                'data-foliofactura="'+(json[key].folio_factura||'')+'" data-concepto="'+json[key].cliente+'" '+
                'data-idempresa="'+json[key].id_empresa+'" data-empresa="'+json[key].empresa+'" '+
                'data-fecha="'+json[key].fecha+'"></td>'+
              '<td style="width: 66px;">'+json[key].fecha+'</td>'+
              '<td>'+json[key].serie+json[key].folio+'</td>'+
              '<td>'+json[key].cliente+'</td>'+
              '<td>'+json[key].empresa+'</td>'+
              '<td style="text-align: right;">'+json[key].saldo+'</td>'+
            '</tr>';
        }

        $('#modal-remisiones #lista_remisiones_modal tbody').html(html);
        $("#lista_remisiones_modal").filterTable();
      });
    });
  };

});




// Modal de comprobar gastos
(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    submitForm();
    btnAddRemisiones();
    btnDelRemisiones();

    modalBuscarGastosDirectos();
  });

  var submitForm = function () {
    // $('#frmcajachica').submit(function(event) {
    //   if ($('#btnGuardar').length == 0) {
    //     event.preventDefault();
    //   }
    // });
  }

  var btnAddRemisiones = function () {
    $('#compGastoFrmAddRem').on('click', function(event) {
      var datos = {
        folio: $('#compGastoFrmFolio').val(),
        total: $('#compGastoFrmMonto').val(),
        proveedor: $('#compGastoFrmProveedor').val(),
      };

      var trhtml =
      '<tr>'+
        '<td>'+$('#compGastoFrmProveedor').val()+
          '<input type="hidden" class="compGastoProveedor" value="'+$('#compGastoFrmProveedor').val()+'">'+
          '<input type="hidden" class="compGastoAll" value="'+encodeURIComponent(JSON.stringify(datos))+'">'+
        '</td>'+
        '<td>'+$('#compGastoFrmFolio').val()+
          '<input type="hidden" class="compGastoFolio" value="'+$('#compGastoFrmFolio').val()+'">'+
        '</td>'+
        '<td>'+$('#compGastoFrmMonto').val()+
          '<input type="hidden" class="compGastoMonto" value="'+$('#compGastoFrmMonto').val()+'">'+
        '</td>'+
        '<td><button type="button" class="btn compGastoFrmRemRem"><i class="icon-remove"></i></button></td>'+
      '</tr>';
      $('#tableComGastoRemisiones tbody').append(trhtml);
      calculaTotalRemisiones();
    });
  };

  var btnDelRemisiones = function () {
    $('#tableComGastoRemisiones').on('click', '.compGastoFrmRemRem', function(event) {
      var $tr = $(this).parents('tr');
      $tr.remove();

      calculaTotalRemisiones();
    });
  };

  var modalBuscarGastosDirectos = function () {
    // btn buscar
    $('#btnBuscarGastosDirectos').on('click', function(event) {
      $('#modal-gastosdirectos').modal('show');
    });

    // Al abrir el modal carga los gastos
    $('#modal-gastosdirectos').on('show', function () {
      var params = {idEmpresa: $('#compGasto_id_empresa').val()};
      $.getJSON(base_url+'panel/caja_chica/ajax_get_gastosdirectos', params, function(json, textStatus) {
        var html = '';
        for (var key in json) {
          html += '<tr>'+
              '<td><input type="checkbox" class="chk-gastos" data-id="'+json[key].id_compra+'" '+
                'data-folio="'+json[key].folio+'" data-total="'+json[key].total+'" '+
                'data-proveedor="'+json[key].proveedor+'" '+
                'data-idproveedor="'+json[key].id_proveedor+'" '+
                'data-idempresa="'+json[key].id_empresa+'" data-empresa="'+json[key].empresa+'" '+
                'data-fecha="'+json[key].fecha+'" '+
                'data-area="'+json[key].area+'" '+
                'data-id_area="'+json[key].id_area+'" '+
                'data-activo="'+json[key].activo+'" '+
                'data-id_activo="'+json[key].id_activo+'" '+
                'data-centros_costos="'+json[key].centros_costos+'" '+
                'data-centros_costos_id="'+json[key].centros_costos_id+'" '+
                'data-ranchos="'+json[key].ranchos+'" '+
                'data-ranchos_id="'+json[key].ranchos_id+'" /></td>'+
              '<td style="width: 66px;">'+json[key].fecha+'</td>'+
              '<td>'+json[key].folio+'</td>'+
              '<td>'+json[key].proveedor+'</td>'+
              '<td>'+json[key].empresa+'</td>'+
              '<td style="text-align: right;">'+json[key].total+'</td>'+
            '</tr>';
        }

        $('#modal-gastosdirectos #lista_gastosdirectos_modal tbody').html(html);
        $("#lista_gastosdirectos_modal").filterTable();
      });
    });

    // Cargar los gastos marcados a la comprobación
    $('#carga-gastosdirectos').on('click', function(event) {
      var $table = $('#tableComGastoGastos tbody'),
          html = '',
          $this;

      if ($('.chk-gastos:checked').length > 0) {
        $('.chk-gastos:checked').each(function(index, el) {
          $this = $(this);

          var datos = {
            id: $this.attr('data-id'),
            folio: $this.attr('data-folio'),
            total: $this.attr('data-total'),
            proveedor: $this.attr('data-proveedor'),
            idproveedor: $this.attr('data-idproveedor'),
            idempresa: $this.attr('data-idempresa'),
            empresa: $this.attr('data-empresa'),
            fecha: $this.attr('data-fecha'),
            area: $this.attr('data-area'),
            id_area: $this.attr('data-id_area'),
            activo: $this.attr('data-activo'),
            id_activo: $this.attr('data-id_activo'),
            centros_costos: $this.attr('data-centros_costos'),
            centros_costos_id: $this.attr('data-centros_costos_id'),
            ranchos: $this.attr('data-ranchos'),
            ranchos_id: $this.attr('data-ranchos_id'),
          };

          html +=
          '<tr>'+
            '<td>'+datos.proveedor+
              '<input type="hidden" class="compGastoGProveedor" value="'+datos.proveedor+'">'+
              '<input type="hidden" class="compGastoGProveedorId" value="'+datos.idproveedor+'">'+
              '<input type="hidden" class="compGastoGAll" value="'+encodeURIComponent(JSON.stringify(datos))+'">'+
            '</td>'+
            '<td>'+datos.folio+
              '<input type="hidden" class="compGastoGFolio" value="'+datos.folio+'">'+
            '</td>'+
            '<td>'+datos.total+
              '<input type="hidden" class="compGastoGMonto" value="'+datos.total+'">'+
            '</td>'+
            '<td><button type="button" class="btn compGastoGFrmRemRem"><i class="icon-remove"></i></button></td>'+
          '</tr>';
        });

        $(html).appendTo($table);
        calculaTotalGastos();

        $('#modal-gastosdirectos').modal('hide');
      } else {
        noty({"text": 'Seleccione al menos un gasto.', "layout":"topRight", "type": 'error'});
      }
    });

    // Eliminar un gasto directo
    $('#tableComGastoGastos').on('click', '.compGastoGFrmRemRem', function(event) {
      $(this).parents('tr').remove();
      calculaTotalGastos();
    });
  };

  var calculaTotalRemisiones = function () {
    var total = 0;
    $('#tableComGastoRemisiones .compGastoMonto').each(function(index, el) {
      total += (parseFloat($(this).val())||0);
    });
    $('#compGastoTotalRemision').text(total);
    calculaTotalComprobacion();
  };

  var calculaTotalGastos = function () {
    var total = 0;
    $('#tableComGastoGastos .compGastoGMonto').each(function(index, el) {
      total += (parseFloat($(this).val())||0);
    });
    $('#compGastoTotalGastos').text(total);
    calculaTotalComprobacion();
  };

  var calculaTotalComprobacion = function () {
    var total = (parseFloat($('#compGastoTotalRemision').text())||0) +
      (parseFloat($('#compGastoTotalGastos').text())||0);
    $('#compGastoMonto').val(total);
  };

});
