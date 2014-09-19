(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {
    autocompleteEmpresas();

    eventOnChangeHorasExtras();
    eventOnChangeDescuentoPlayeras();
    eventOnChangeDescuentoOtros();
    eventClickCheckVacaciones();
    eventClickCheckAguinaldo();
    eventOnSubmitForm();
    eventOnClickButtonPtu();
    eventOnKeyPressUtilidadEmpresas();

    if (parseFloat($('#totales-ptu-input').val()) > 0) {
      $('#ptu').val($('#totales-ptu-input').val());
      showPtu();
    }

    $('.activa-vacaciones').each(function(index, el) {
      var $this = $(this),
          $tr =  $this.parents('tr');
      if ($this.val() === '1') {
        $tr.find('.check-vacaciones').prop('checked', true).trigger('click').prop('checked', true).prop('disabled', true);
      }
    });

    $('.activa-aguinaldo').each(function(index, el) {
      var $this = $(this);
      if ($this.val() === '1') {
        $('#check-aguinaldo').prop('checked', true).trigger('click').prop('checked', true);
        return false;
      }
    });
  });

  var autocompleteEmpresas = function () {
    $("#empresa").autocomplete({
        source: base_url+'panel/facturacion/ajax_get_empresas_fac/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $("#empresaId").val(ui.item.id);
          $(this).css("background-color", "#B0FFB0");
          cargaSemanas();
        }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).css("background-color", "#FFD9B3");
          $("#empresaId").val("");
        }
    });
  };

  var eventOnChangeHorasExtras = function () {
    $('.horas-extras').on('change', function(event) {
      ajaxGetEmpleado($(this).parents('tr'));
      guardaPreNominaEmpleado($(this).parents('tr'));
    });
  };

  var eventOnChangeDescuentoPlayeras = function () {
    $('.descuento-playeras').on('change', function(event) {
      recalculaEmpleado($(this).parents('tr'));
      guardaPreNominaEmpleado($(this).parents('tr'));
    });
  };

  var eventOnChangeDescuentoOtros = function () {
    $('.descuento-otros').on('change', function(event) {
      recalculaEmpleado($(this).parents('tr'));
      guardaPreNominaEmpleado($(this).parents('tr'));
    });
  };

  var eventClickCheckVacaciones = function () {
    $('.check-vacaciones').on('click', function(event) {
      var $this = $(this),
          $parent = $this.parents('tr'),
          $conVacaciones = $parent.find('.con-vacaciones'),

          $theadPercepciones = $('#head-percepciones'),
          $headVacaciones = $('#head-vacaciones'),
          $headPrimaVacacional = $('#head-prima-vacacional'),
          $tdVacaciones = $('td#td-vacaciones'), // todos los td de vacaciones.
          $tdPrimaVacacional = $('td#td-prima-vacacional'), // todos los td de prima vacacional.
          $tdTotalVacaciones = $('#totales-vacaciones'), // td que muestra el total de las vacacioens.
          $tdTotalPrimaVacacional = $('#totales-prima-vacacional'); // td que muestra el total de las primas vacacionales.

      if ($this.is(':checked')) {
        $conVacaciones.val('1');

        // Si no esta las vacaciones y prima vacaciones visibles entonces incrementa
        // el colspan del head de percepciones.
        if ($headVacaciones.css('display') === 'none') {
          $theadPercepciones.attr('colspan',  parseFloat($theadPercepciones.attr('colspan')) + 2);
        }

        $headVacaciones.css({'display': ''});
        $headPrimaVacacional.css({'display': ''});
        $tdVacaciones.css({display: ''});
        $tdPrimaVacacional.css({display: ''});
        $tdTotalVacaciones.css({display: ''});
        $tdTotalPrimaVacacional.css({display: ''});

        // Pone el total de vacaciones y prima vacacional en el span del td.
        $parent.find('#td-vacaciones span').text(util.darFormatoNum($parent.find('.vacaciones').val()));
        $parent.find('#td-prima-vacacional span').text(util.darFormatoNum($parent.find('.prima-vacacional').val()));
      } else {
        $conVacaciones.val('0');

        $parent.find('#td-vacaciones span').text(util.darFormatoNum(0));
        $parent.find('#td-prima-vacacional span').text(util.darFormatoNum(0));

        var activos = false;
        $('.check-vacaciones').each(function(index, el) {
          if ($(this).is(':checked')) {
            activos = true;
            return false;
          }
        });

        // Si ya no hay ningun checbox checkeado entonces oculta los head de
        // vacaciones y prima vacacional.
        if (activos === false) {
          $theadPercepciones.attr('colspan',  parseFloat($theadPercepciones.attr('colspan')) - 2);
          $headVacaciones.css({'display': 'none'});
          $headPrimaVacacional.css({'display': 'none'});
          $tdVacaciones.css({display: 'none'});
          $tdPrimaVacacional.css({display: 'none'});
          $tdTotalVacaciones.css({display: 'none'});
          $tdTotalPrimaVacacional.css({display: 'none'});
        }
      }

      // metodo que recalcula el isr.
      ajaxGetEmpleado($parent);
      // recalculaEmpleado($parent);
    });
  };

  var eventClickCheckAguinaldo = function () {
    $('#check-aguinaldo').on('click', function(event) {
      var $this = $(this),
          $conAguinaldo = $('#con-aguinaldo'),
          $theadPercepciones = $('#head-percepciones'),
          $headAguinaldo = $('#head-aguinaldo'),
          $tdAguinaldo = $('td#td-aguinaldo'), // todos los td de aguinaldo
          $tdTotalAguinaldo = $('#totales-aguinaldo'); // td que muestra el total de los aguinaldos.

      if ($this.is(':checked')) {
        $theadPercepciones.attr('colspan', parseFloat($theadPercepciones.attr('colspan')) + 1);
        $headAguinaldo.css({'display': ''});
        $tdAguinaldo.css({'display': ''});
        $conAguinaldo.val('1');
        $tdTotalAguinaldo.css({'display': ''});
      } else {
        $theadPercepciones.attr('colspan', parseFloat($theadPercepciones.attr('colspan')) - 1);
        $headAguinaldo.css({'display': 'none'});
        $tdAguinaldo.css({'display': 'none'});
        $conAguinaldo.val('0');
        $tdTotalAguinaldo.css({'display': 'none'});
      }

      $('tbody .tr-empleado').each(function(index, el) {
        ajaxGetEmpleado($(this));
      });
    });
  };

  var eventOnSubmitForm = function () {
    $('#guardarNomina').on('click', function(event) {
      if ($('.sin-curp').length !== 0) {
        event.preventDefault();
        noty({"text": 'No se puede guardar la nomina porque existen empleados que no cuentan con su CURP.', "layout":"topRight", "type": 'error'});
      } else {
        errorTimbrar = false;
        idUltimoError = 0;
        // Ciclo para recorrer todos los <tr> de los empleado.
        $('.tr-empleado').each(function(index, el) {
          console.log($(this).find('.generar-nomina').val());
          if ($(this).find('.generar-nomina').val() === '1') {
            guardaNominaEmpleado($(this));
          }
        });
        // guardaNominaEmpleado($("#empleado6"));
      }
    });
  };

  var eventOnClickButtonPtu = function () {
    $('#btn-ptu').on('click', function(event) {
      var $ptu = $('#ptu');
          // $theadPercepciones = $('#head-percepciones'),
          // $headPtu = $('#head-ptu'),
          // $tdAguinaldo = $('td#td-ptu'), // todos los td de ptu
          // $tdTotalPtu = $('#totales-ptus'); // td que muestra el total de los ptu.

      if ($ptu.val() !== '') {

        showPtu();

        // // Si no se ha mostrado el header del PTU entonces le suma 1 al colspan del header de las percepciones.
        // if ($headPtu.css('display') === 'none') {
        //   $theadPercepciones.attr('colspan', parseFloat($theadPercepciones.attr('colspan')) + 1);
        // }

        // $headPtu.css({'display': ''});
        // $tdAguinaldo.css({'display': ''});
        // $tdTotalPtu.css({'display': ''});

        $('.tr-empleado').each(function(index, el) {
          ajaxGetEmpleado($(this));
        });
      }
    });
  };

  var eventOnKeyPressUtilidadEmpresas = function () {
    $('#ptu').on('keypress', function(event) {
      if (event.which === 13) {
        event.preventDefault();
        $('#btn-ptu').trigger('click');
      }
    });
  };

  var showPtu = function () {
    var $theadPercepciones = $('#head-percepciones'),
        $headPtu = $('#head-ptu'),
        $tdAguinaldo = $('td#td-ptu'), // todos los td de ptu
        $tdTotalPtu = $('#totales-ptus'); // td que muestra el total de los ptu.

    // Si no se ha mostrado el header del PTU entonces le suma 1 al colspan del header de las percepciones.
    if ($headPtu.css('display') === 'none') {
      $theadPercepciones.attr('colspan', parseFloat($theadPercepciones.attr('colspan')) + 1);
    }

    $headPtu.css({'display': ''});
    $tdAguinaldo.css({'display': ''});
    $tdTotalPtu.css({'display': ''});
  };

  var recalculaEmpleado = function ($parent) {
    var $sueldo = $parent.find('.sueldo'),
        $sueldoReal = $parent.find('.sueldo-real'),
        $hExtras    = $parent.find('.horas-extras'),
        $aguinaldo  = $parent.find('.aguinaldo'),
        $subsidio  = $parent.find('.subsidio'),
        $ptu       = $parent.find('.ptu'),

        $infonavit  = $parent.find('.infonavit'),
        $imss       = $parent.find('.imss'),
        $prestamos  = $parent.find('.prestamos'),
        $playeras   = $parent.find('.descuento-playeras'),
        $dotros     = $parent.find('.descuento-otros'),
        $isr        = $parent.find('.isr'),

        $bonos      = $parent.find('.bonos'),
        $otros      = $parent.find('.otros'),
        $domingo    = $parent.find('.domingo'),

        $totalPercepciones = $parent.find('.total-percepciones'),
        $totalPercepcionesSpan = $parent.find('.total-percepciones-span'),

        $totalDeducciones = $parent.find('.total-deducciones'),
        $totalDeduccionesSpan = $parent.find('.total-deducciones-span'),

        $totalNomina = $parent.find('.total-nomina'),
        $totalNominaSpan = $parent.find('.total-nomina-span'),

        $totalComplemento = $parent.find('.total-complemento'),
        $totalComplementoSpan = $parent.find('.total-complemento-span'),

        $esta_asegurado = $parent.find('.empleado-esta_asegurado'),

        $cuenta_banco = $parent.find('.empleado-cuenta_banco'),

        totalPercepciones = 0,
        totalDeducciones = 0,
        totalNomina = 0,
        totalComplemento = 0;

    totalComplemento = $sueldoReal.val();

    if ($hExtras.val() === '') {
      $hExtras.val(0);
    }

    if ($playeras.val() === '') {
      $playeras.val(0);
    }

    if ($dotros.val() === '') {
      $dotros.val(0);
    }

    // Si activa las vacaciones entonces sumas las vacaciones y la prima
    // vacacional a las percepciones.
    if ($parent.find('.check-vacaciones').is(':checked')) {
      totalPercepciones += parseFloat(parseFloat($parent.find('.vacaciones').val()) + parseFloat($parent.find('.prima-vacacional').val()));
    }

    // Si el aguinaldo esta activo entonces se los suma a las percepciones.
    if ($('#check-aguinaldo').is(':checked')) {
      totalPercepciones += parseFloat($aguinaldo.val());
    }

    // Percepciones.
    totalPercepciones += parseFloat($sueldo.val()) + parseFloat($hExtras.val()) + parseFloat($subsidio.val()) + parseFloat($ptu.val());
    if( $esta_asegurado.val() == 't' )
    {
      $totalPercepciones.val(totalPercepciones);
      $totalPercepcionesSpan.text(util.darFormatoNum(totalPercepciones));
    }else{
      $totalPercepciones.val(0);
      $totalPercepcionesSpan.text(util.darFormatoNum(0));
    }

    // Deducciones.
    totalDeducciones =  parseFloat($infonavit.val()) + parseFloat($imss.val()) + parseFloat($prestamos.val()) + parseFloat($isr.val()); // + parseFloat($playeras.val());
    if( $esta_asegurado.val() == 't' )
    {
      $totalDeducciones.val(totalDeducciones);
      $totalDeduccionesSpan.text(util.darFormatoNum(totalDeducciones));
    }else{
      $totalDeducciones.val(0);
      $totalDeduccionesSpan.text(util.darFormatoNum(0));
    }

    // Total de nomina.
    totalNomina = util.round2Dec(parseFloat($totalPercepciones.val()) - parseFloat($totalDeducciones.val()));
    var ttotal_nomina_cheques = 0;
    if($cuenta_banco.val() == ''){
      // ttotal_nomina_cheques = totalNomina;
      totalNomina = 0;
    }
    $totalNomina.val(totalNomina);
    $totalNominaSpan.text(util.darFormatoNum(totalNomina));

    // Total complemento.
    totalComplemento = parseFloat(totalComplemento) +
                       parseFloat(ttotal_nomina_cheques) +
                       parseFloat($bonos.val()) +
                       parseFloat($otros.val()) +
                       parseFloat($domingo.val()) -
                       parseFloat($totalNomina.val()) -
                       parseFloat($infonavit.val())-
                       parseFloat($prestamos.val()) -
                       parseFloat($playeras.val()) -
                       parseFloat($dotros.val());

    $totalComplementoSpan.text(util.darFormatoNum(util.trunc2Dec(totalComplemento)));
    $totalComplemento.val(util.trunc2Dec(totalComplemento));

    recalculaTotales();
  };

  var recalculaTotales = function () {
    var $allTr = $('.tr-empleado'),
        $tr,
        $checkVacaciones,

        $tdTotalesVacaciones = $('#totales-vacaciones'),
        $tdTotalesPrimaVacacional = $('#totales-prima-vacacional'),
        $tdTotalesHorasExtras = $('#totales-horas-extras'),
        $tdTotalesSubsidios = $('#totales-subsidios'),
        $tdTotalesPtus = $('#totales-ptus'),
        $tdTotalesPercepciones = $('#totales-percepciones'),
        $tdTotalesDescuentoPlayeras = $('#totales-descuento-playeras'),
        $tdTotalesDescuentoOtros = $('#totales-descuento-otros'),
        $tdTotalesIsrs = $('#totales-isrs'),
        $tdTotalesDeducciones = $('#totales-deducciones'),
        $tdTotalesTransferencias = $('#totales-transferencias'),
        $tdTotalesComplementos = $('#totales-complementos'),

        totalesVacaciones = 0,
        totalesPrimaVacacional = 0,
        totalesHorasExtras = 0,
        totalesSubsidios = 0,
        totalesPtus = 0,
        totalesPercepciones = 0,
        totalesDescuentoPlayeras = 0,
        totalesDescuentoOtros = 0,
        totalesIsrs = 0,
        totalesDeducciones = 0,
        totalesTransferencias = 0,
        totalesComplementos = 0;

    // Recorre todos los empleados.
    $allTr.each(function(index, el) {
      $tr = $(this);
      $checkVacaciones = $tr.find('.check-vacaciones');

      // Si el empleado tiene activo el check de vacaciones lo suma.
      if ($checkVacaciones.is(':checked')) {
        totalesVacaciones += parseFloat($tr.find('.vacaciones').val());
        totalesPrimaVacacional += parseFloat($tr.find('.prima-vacacional').val());
      }

      totalesHorasExtras += parseFloat($tr.find('.horas-extras').val());
      totalesSubsidios += parseFloat($tr.find('.subsidio').val());
      totalesPtus += parseFloat($tr.find('.ptu').val());
      totalesPercepciones += parseFloat($tr.find('.total-percepciones').val());
      totalesDescuentoPlayeras += parseFloat($tr.find('.descuento-playeras').val());
      totalesDescuentoOtros += parseFloat($tr.find('.descuento-otros').val());
      totalesDeducciones += parseFloat($tr.find('.total-deducciones').val());
      totalesTransferencias += parseFloat($tr.find('.total-nomina').val());
      totalesComplementos += parseFloat($tr.find('.total-complemento').val());
      totalesIsrs += parseFloat($tr.find('.isr').val());
    });

    $tdTotalesVacaciones.text(util.darFormatoNum(totalesVacaciones));
    $tdTotalesPrimaVacacional.text(util.darFormatoNum(totalesPrimaVacacional));
    $tdTotalesHorasExtras.text(util.darFormatoNum(totalesHorasExtras));
    $tdTotalesSubsidios.text(util.darFormatoNum(totalesSubsidios));
    $tdTotalesPtus.text(util.darFormatoNum(totalesPtus));
    $tdTotalesPercepciones.text(util.darFormatoNum(totalesPercepciones));
    $tdTotalesDescuentoPlayeras.text(util.darFormatoNum(totalesDescuentoPlayeras));
    $tdTotalesDescuentoOtros.text(util.darFormatoNum(totalesDescuentoOtros));
    $tdTotalesIsrs.text(util.darFormatoNum(totalesIsrs));
    $tdTotalesDeducciones.text(util.darFormatoNum(totalesDeducciones));
    $tdTotalesTransferencias.text(util.darFormatoNum(totalesTransferencias));
    $tdTotalesComplementos.text(util.darFormatoNum(totalesComplementos));
  };

  var ajaxGetEmpleado = function ($tr) {
    // console.log($('#ptu').val());
    loader.create();
    $.ajax({
      url: base_url + 'panel/nomina_fiscal/ajax_get_empleado/',
      type: 'POST',
      dataType: 'json',
      data: {
        empleado_id: $tr.find('.empleado-id').val(),
        empresa_id: $('#empresaId').val(),
        semana: $('#semanas').find('option:selected').val(),
        con_aguinaldo: $('#con-aguinaldo').val(),
        con_vacaciones: $tr.find('.con-vacaciones').val(),
        horas_extras: $tr.find('.horas-extras').val(),
        ptu: $('#ptu').val() || 0
      },
    })
    .done(function(empleado) {
      $tr.find('.subsidio').val(empleado[0].nomina.percepciones.subsidio.total);
      $tr.find('.subsidio-span').text(util.darFormatoNum(empleado[0].nomina.percepciones.subsidio.total));

      $tr.find('.isr').val(empleado[0].nomina.deducciones.isr.total);
      $tr.find('.isr-span').text(util.darFormatoNum(empleado[0].nomina.deducciones.isr.total));

      $tr.find('.ptu').val(empleado[0].nomina.percepciones.ptu.total);
      $tr.find('.ptu-span').text(util.darFormatoNum(empleado[0].nomina.percepciones.ptu.total));

      recalculaEmpleado($tr);
      loader.close();
    })
    .fail(function() {
      console.log("error");
    });
  };

  var getSemana = function () {
    return $('#semanas').find('option:selected').val();
  };


  var errorTimbrar = false, // Auxiliar para saber si ocurrio algun error al timbrar.
      idUltimoError = 0; // Auxiliar para saber cual id de empleado fue el ultimo que no se timbro.

  var guardaNominaEmpleado = function ($tr) {
    loader.create();
    $.ajax({
      url: base_url + 'panel/nomina_fiscal/ajax_add_nomina_empleado/',
      type: 'POST',
      dataType: 'json',
      data: {
        empresa_id: $('#empresaId').val(),
        anio: $('#anio').val(),
        empleado_id: $tr.find('.empleado-id').val(),
        generar_nomina: $tr.find('.generar-nomina').val(),
        numSemana: $('#semanas').find('option:selected').val(),
        horas_extras: $tr.find('.horas-extras').val(),
        descuento_playeras: $tr.find('.descuento-playeras').val(),
        descuento_otros: $tr.find('.descuento-otros').val(),
        subsidio: $tr.find('.subsidio').val(),
        isr: $tr.find('.isr').val(),
        utilidad_empresa: $('#ptu').val(),
        con_vacaciones: $tr.find('.con-vacaciones').val(),
        con_aguinaldo: $('#con-aguinaldo').val(),
        total_no_fiscal: $tr.find('.total-complemento').val(),
        ultimo_no_generado: $('#ultimo-no-generado').val(),
        esta_asegurado: $tr.find('.empleado-esta_asegurado').val(),
      },
    })
    .done(function(result) {
      // Si se timbro bien entonces pinta verde el tr y
      if (result.errorTimbrar === false) {
        $('#empleado'+result.empleadoId).find('td').css({'background-color': '#97E594'});
        $('#empleado'+result.empleadoId).find('.generar-nomina').val('0');
      } else {
        errorTimbrar = true;
        idUltimoError = result.empleadoId;
      }

      // Si el empleado que se timbro es el ultimo que se tenia que timbrar
      if (result.ultimoNoGenerado == result.empleadoId) {
        if (errorTimbrar === false) {
          // agrega la nomina a terminadas
          $.post(base_url + 'panel/nomina_fiscal/ajax_add_nomina_terminada/', {
            empresa_id: $('#empresaId').val(),
            anio: $('#anio').val(),
            semana: $('#semanas').find('option:selected').val(),
            tipo: 'se'
          }, function(data, textStatus, xhr) {
            alert('Terminado. Las nomina se generaron correctamente. De click en Aceptar!!!');
            location.reload();
          });
        } else {
          $('#ultimo-no-generado').val(idUltimoError);
          alert('Ocurrio un problema con una o más nominas de empleados, vuelva a presionar el botón "Guardar" para generar esas nominas faltantes.');
        }
      }

      loader.close();
    })
    .fail(function() {
      console.log("error");
    });
  };

  var guardaPreNominaEmpleado = function ($tr) {
    loader.create();
    $.ajax({
      url: base_url + 'panel/nomina_fiscal/ajax_add_prenomina_empleado/',
      type: 'POST',
      dataType: 'json',
      data: {
        empresa_id: $('#empresaId').val(),
        empleado_id: $tr.find('.empleado-id').val(),
        anio: $('#anio').val(),
        numSemana: $('#semanas').find('option:selected').val(),
        horas_extras: $tr.find('.horas-extras').val(),
        descuento_playeras: $tr.find('.descuento-playeras').val(),
        descuento_otros: $tr.find('.descuento-otros').val(),
      },
    })
    .done(function(result) {
      // result.status

      loader.close();
    })
    .fail(function() {
      console.log("error");
    });
  };

  var cargaSemanas = function () {
    $.getJSON(base_url+'panel/nomina_fiscal/ajax_get_semana/', {'anio': $("#anio").val(), 'did_empresa': $("#empresaId").val()},
      function(data){
        var html = '', i;
        console.log(data);
        for (i in data) {
          html += '<option value="'+data[i].semana+'">'+data[i].semana+' - Del '+data[i].fecha_inicio+' Al '+data[i].fecha_final+'</option>';
        }
        $('#semanas').html(html);
    });
  };

});