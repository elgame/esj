(function (fn) {
  fn(jQuery, window);
})(function ($, window) {
  $(function () {
    eventClickBtnAddBono();
    eventClickBtnAddOtro();
    eventClickBtnDelItem();
    eventClickBtnGuardarBonosOtros();
    eventClickBtnRefresh();

    eventClickBtnAddPrestamos();
    eventClickBtnDelItemPrestamo();
    eventClickBtnGuardarPrestamos();

    eventClickBtnDelItemVacaciones();

     $('#myTab a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
      });

    eventClickBtnAddIncapacidad();
    eventClickBtnDelItemIncapacidad();

    eventClickBtnAddPermiso();
    eventClickBtnDelItemPermiso();
  });

  var eventClickBtnAddBono = function () {
    $('#btn-add-bono').on('click', function(event) {
      addItem('bono');
    });
  };

  var eventClickBtnAddOtro = function () {
    $('#btn-add-otro').on('click', function(event) {
      addItem('otro');
    });
  };

  var eventClickBtnDelItem = function () {
    $('#table-bonos-otros').on('click', '.btn-del-item', function(event) {
      var $parent = $(this).parents('tr');
      $parent.remove();
    });
  };

  var eventClickBtnGuardarBonosOtros = function () {
    $('#btn-guardar-bonos').on('click', function(event) {
      event.preventDefault();
      var $form = $('#form-bonos'),
          error = false,
          $cantidades = $('#table-bonos-otros').find('.cantidad');

      $cantidades.each(function(index, el) {
        if ($(this).val() === '0' || $(this).val() === '') {
          error = true;
          return false;
        }
      });

      if ($cantidades.length === 0 && $('#existentes').length === 0) {
        noty({"text": 'Agregue algun bono u otro!', "layout":"topRight", "type": 'error'});
      } else if (error) {
        noty({"text": 'Los campos cantidad son requeridos!', "layout":"topRight", "type": 'error'});
      } else {
        $form.submit();
      }
    });
  };

  var eventClickBtnRefresh = function () {
    $('#btn-refresh').on('click', function(event) {
      location.reload();
    });
  };

  var addItem = function (tipo) {
    var htmlTr = '',
        $tableBonosOtros = $('#table-bonos-otros'),
        selectedBono = tipo === 'bono' ? 'selected' : '',
        selectedOtro = tipo === 'otro' ? 'selected' : '',
        $fecha = $('#fecha');

    htmlTr = '<tr>' +
              '<td style="width: 200px;"><input type="text" name="fecha[]" value="'+$fecha.find('option:selected').val()+'" class="span12" readonly> </td>' +
              '<td style="width: 100px;"><input type="text" name="cantidad[]" value="0" class="span12 vpositive cantidad" required></td>' +
              '<td style="width: 200px;">' +
                '<select name="tipo[]" class="span12">' +
                  '<option value="bono" '+selectedBono+'>Bono</option>' +
                  '<option value="otro" '+selectedOtro+'>Otro</option>' +
                  '<option value="domingo">Domingo</option>' +
                '</select>' +
              '</td>' +
              '<td>' +
                '<button type="button" class="btn btn-danger btn-del-item"><i class="icon-trash"></i></button>' +
              '</td>' +
            '</tr>';

    $(htmlTr).appendTo($tableBonosOtros.find('tbody'));

    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  /*
   |------------------------------------------------------------------------
   | Prestamos
   |------------------------------------------------------------------------
   */

  var eventClickBtnAddPrestamos = function () {
    $('#btn-add-prestamo').on('click', function(event) {
       addItemPrestamo();
    });

    $("#table-prestamos").on('change', '.ptipo_efectico', function(event) {
      var $tr = $(this).parent().parent();
      $('#pCuentasBanco').hide();
      if ($(this).val() == 'fi') {
        $('#pCuentasBanco').show();
      }
    });

    $("#pBtnCancelar").on('click', function(event) {
      $('#'+idTrAdd).remove();
      $('#pCuentasBanco').hide();
      $("#pContpaq, #pConcepto").val('');
    });
    $("#pBtnAgregar").on('click', function(event) {
      var $tr = $('#'+idTrAdd);
      if ($('#pCuenta').val() != '' && $('#pMetodoPago').val() != '') {
        $tr.find('.cuentaId').val($('#pCuenta').val());
        $tr.find('.contpaq').val($('#pContpaq').val());
        $tr.find('.concepto').val($('#pConcepto').val());
        $tr.find('.metodoPago').val($('#pMetodoPago').val());
        $('#pCuentasBanco').hide();
        $("#pContpaq, #pConcepto").val('');
      } else {
        noty({"text": 'La cuenta y el metodo de pago son requeridos!', "layout":"topRight", "type": 'error'});
      }
    });
  };

  var idTrAdd = '',
  addItemPrestamo = function () {
    var htmlTr = '',
        $tablePrestamos = $('#table-prestamos'),
        $fecha = $('#fecha-prestamos');
    idTrAdd = (new Date()).getTime();
    htmlTr = '<tr id="'+idTrAdd+'">' +
                '<td style="width: 200px;">'+
                  '<input type="text" name="fecha[]" value="'+$fecha.find('option:selected').val()+'" class="span12" readonly>'+
                  '<input type="hidden" name="cuentaId[]" value="" class="cuentaId">'+
                  '<input type="hidden" name="contpaq[]" value="" class="contpaq">'+
                  '<input type="hidden" name="concepto[]" value="" class="concepto">'+
                  '<input type="hidden" name="metodoPago[]" value="" class="metodoPago">'+
                '</td>' +
                '<td style="width: 100px;"><input type="text" name="cantidad[]" value="0" class="span12 vpositive cantidad" required></td>' +
                '<td style="width: 100px;"><input type="text" name="pago_semana[]" value="0" class="span12 vpositive pago-semana" required></td>' +
                '<td style="width: 200px;"><input type="date" name="fecha_inicia_pagar[]" value="" class="span12 vpositive fecha-inicia-pagar" required></td>' +
                '<td style="width: 100px;">' +
                  '<input type="hidden" name="id_prestamo[]" value="">' +
                  '<select name="pausarp[]" required style="width: 100px;">' +
                    '<option value="f">Activo</option>' +
                    '<option value="t">Pausado</option>' +
                  '</select></td>' +
                '<td style="width: 50px;">'+
                  '<select name="tipo_efectico[]" required style="width: 50px;" class="ptipo_efectico">'+
                    '<option value="efd">Efectivo Fijo</option>'+
                    '<option value="ef">Efectivo</option>'+
                    '<option value="fi">Fiscal</option>'+
                    '<option value="mt">Materiales</option>'+
                  '</select>'+
                '</td>'+
                '<td>' +
                  '<button type="button" class="btn btn-danger btn-del-item-prestamo"><i class="icon-trash"></i></button>' +
                '</td>' +
              '</tr>';

    $(htmlTr).appendTo($tablePrestamos.find('tbody'));

    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };


  var eventClickBtnDelItemPrestamo = function () {
    $('#table-prestamos').on('click', '.btn-del-item-prestamo', function(event) {
      var $parent = $(this).parents('tr');
      $parent.remove();
    });
  };

  var eventClickBtnGuardarPrestamos = function () {
    $('#btn-guardar-prestamos').on('click', function(event) {
      event.preventDefault();
      var $form = $('#form-prestamos'),
          error = false,
          $cantidades = $('#table-prestamos').find('.cantidad'),
          $pagosSemana = $('#table-prestamos').find('.pago-semana'),
          $fechasInicioPagos = $('#table-prestamos').find('.fecha-inicia-pagar');

      $cantidades.each(function(index, el) {
        if ($(this).val() === '0' || $(this).val() === '') {
          error = 1;
          return false;
        }
      });

      if ( ! error) {
        $pagosSemana.each(function(index, el) {
          if ($(this).val() === '0' || $(this).val() === '') {
            error = 2;
            return false;
          }
        });
      }

      if ( ! error) {
        $fechasInicioPagos.each(function(index, el) {
          if ($(this).val() === '') {
            error = 3;
            return false;
          }
        });
      }

      if ($cantidades.length === 0 && $('#prestamos-existentes').length === 0) {
        noty({"text": 'Agregue algun prestamo!', "layout":"topRight", "type": 'error'});
      } else if (error === 1) {
        noty({"text": 'Los campos cantidad son requeridos!', "layout":"topRight", "type": 'error'});
      } else if (error === 2) {
        noty({"text": 'Los campos Pago semana son requeridos!', "layout":"topRight", "type": 'error'});
      } else if (error === 3) {
        noty({"text": 'Los campos Fecha inicio pagos son requeridos!', "layout":"topRight", "type": 'error'});
      } else {
        $form.submit();
      }
    });
  };

  /*
   |------------------------------------------------------------------------
   | Vacaciones
   |------------------------------------------------------------------------
   */

  var eventClickBtnDelItemVacaciones = function () {
    $('#table-vacaciones').on('click', '.btn-del-item-vacacion', function(event) {
      var $parent = $(this).parents('tr'),
      f = new Date($parent.find('#vfechadefault').val());

      $parent.find('.vfecha').val(f.toJSON().substr(0, 10));
      $parent.find('.vfecha1').val(f.toJSON().substr(0, 10));
      $parent.find('.vdias').val('0');
      // $parent.remove();
    });
  };


  /*
   |------------------------------------------------------------------------
   | Incapacidades
   |------------------------------------------------------------------------
   */
  var eventClickBtnAddIncapacidad = function () {
    $('#btn-add-incapacidad').on('click', function(event) {
       addItemIncapacidad();
    });
  };

  var addItemIncapacidad = function () {
    var htmlTr = '',
        $tableIncapacidad = $('#table-incapacidades'),
        $fecha = $('#fecha-prestamos'),
        sat_incapacidades = $.parseJSON($("#sat_incapacidades").text());

    htmlTr = '<tr>'+
              '<td style="width: 60px;"><input type="text" name="ifolio[]" value="" class="span12" required> </td>'+
              '<td style="width: 100px;">'+
                '<input type="hidden" name="iid_asistencia[]" value="">'+
                '<select name="itipo_inciden[]" class="span12">';
                for (var i = 0; i < sat_incapacidades.length; i++) {
                  htmlTr += '<option value="'+sat_incapacidades[i].id_clave+'">'+sat_incapacidades[i].nombre+'</option>';
                }
              htmlTr += '</select>'+
              '</td>'+
              '<td style="width: 80px;"><input type="date" name="ifecha[]" value="" class="span12 ifecha" required> </td>'+
              '<td style="width: 100px;"><input type="number" name="idias[]" value="" class="span12" required> </td>'+
              '<td style="width: 100px;">'+
                '<select name="iramo_seguro[]" class="span12">'+
                  '<option value="Riesgo de Trabajo">Riesgo de Trabajo</option>'+
                  '<option value="Enfermedad General">Enfermedad General</option>'+
                  '<option value="Maternitad Prenatal">Maternitad Prenatal</option>'+
                  '<option value="Maternitad Postnatal">Maternitad Postnatal</option>'+
                '</select>'+
              '</td>'+
              '<td style="width: 100px;">'+
                '<select name="icontrol_incapa[]" class="span12">'+
                  '<option value="Unica">Unica</option>'+
                  '<option value="Inicial">Inicial</option>'+
                  '<option value="Subsecuente">Subsecuente</option>'+
                  '<option value="Alta Medica o ST-2">Alta Medica o ST-2</option>'+
                  '<option value="Prenatal">Prenatal</option>'+
                  '<option value="Postnatal">Postnatal</option>'+
                  '<option value="Valuacion o ST-3">Valuacion o ST-3</option>'+
                '</select>'+
              '</td>'+
              '<td style="width: 100px;">'+
                '<button type="button" class="btn btn-danger btn-del-item-incapacidad"><i class="icon-trash"></i></button>'+
              '</td>'+
            '</tr>';

    $(htmlTr).appendTo($tableIncapacidad.find('tbody'));

    // $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  var eventClickBtnDelItemIncapacidad = function () {
    $('#table-incapacidades').on('click', '.btn-del-item-incapacidad', function(event) {
      var $parent = $(this).parents('tr');
      $parent.remove();
    });
  };


  /*
   |------------------------------------------------------------------------
   | Incapacidades
   |------------------------------------------------------------------------
   */
  var eventClickBtnAddPermiso = function () {
    $('#btn-add-permiso').on('click', function(event) {
       addItemPermiso();
    });

    $('#table-permisos').on('change', '.perFechaIni, .perFechaFin', function(event){
      const $parent = $(this).parents('tr');
      let fecha1 = moment($parent.find('.perFechaIni').val());
      let fecha2 = moment($parent.find('.perFechaFin').val());
      let dias = 0, hrs = 0;

      if(fecha1.isValid() && fecha2.isValid()){
        dias = moment(fecha2.format("YYYY-MM-DD")).diff(fecha1.format("YYYY-MM-DD"), 'days');
        hrs = fecha2.diff(fecha1, 'hours') - (dias*24);

        $parent.find('.perDias').val(dias);
        $parent.find('.perHrs').val(hrs);
      }
    });
  };

  var addItemPermiso = function () {
    var htmlTr = '',
        $tableIncapacidad = $('#table-permisos'),
        $fecha = $('#fecha-prestamos');

    htmlTr = `<tr class="rowpermisos">
                <td>
                  <table>
                    <tr>
                      <td>Fecha Ini
                        <input type="hidden" name="perIdPermiso[]" value="" class="perIdPermiso">
                      </td>
                      <td><input type="datetime-local" name="perFechaIni[]" value="" class="span12 perFechaIni"></td>
                      <td>Fecha Fin</td>
                      <td><input type="datetime-local" name="perFechaFin[]" value="" class="span12 perFechaFin"></td>
                      <td>
                        Dias: <input type="number" name="perDias[]" value="" class="span9 perDias" style="display: inline;"> <br>
                        Hrs: <input type="number" name="perHrs[]" value="" class="span9 perHrs" style="display: inline;">
                      </td>
                    </tr>
                    <tr>
                      <td>Uso Dirección</td>
                      <td>
                        <select name="perUsoDir[]" class="span12 perUsoDir">
                          <option>SIN GOCE DE SUELDO</option>
                          <option>PERMISO PAGADO AL</option>
                          <option>REPOSICION DE TIEMPO</option>
                          <option>A CUENTA DE VACACIONES</option>
                        </select>
                        <input type="text" name="perUsoDirValue[]" placeholder="50%" class="span12 hide perUsoDirValue">
                      </td>
                      <td>Uso RH</td>
                      <td>
                        <select name="perUsoRH[]" class="span12 perUsoRH">
                          <option>ACADEMICO</option>
                          <option>ADMINISTRATIVO</option>
                          <option>ASUNTOS PERSONALES</option>
                        </select>
                        <input type="hidden" name="perUsoRHValue[]" placeholder="50%" class="span12 hide perUsoRHValue">
                      </td>
                      <td>
                        <button type="button" class="btn btn-danger btn-del-item-permisos"><i class="icon-trash"></i></button>
                        <input type="hidden" name="perDelete[]" value="" data class="perDelete">
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>`;

    $(htmlTr).appendTo($tableIncapacidad.find('tbody.body-permisos'));

    // $(".vpositive").numeric({ negative: false }); //Numero positivo
  };

  var eventClickBtnDelItemPermiso = function () {
    $('#table-permisos').on('click', '.btn-del-item-permisos', function(event) {
      var $parent = $(this).parents('tr.rowpermisos');
      if($parent.find('.perIdPermiso').val() == ''){
        $parent.remove();
      } else {
        $parent.find('.perDelete').val('true');
        $parent.hide();
      }
    });
  };

});