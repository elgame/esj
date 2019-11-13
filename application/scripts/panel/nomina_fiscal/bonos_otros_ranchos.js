(function (fn) {
  fn(jQuery, window);
})(function ($, window) {
  $(function () {
    eventClickBtnAddPrestamos();
    eventClickBtnDelItemPrestamo();
    eventClickBtnGuardarPrestamos();

     $('#myTab a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
      });
  });

  var eventClickBtnRefresh = function () {
    $('#btn-refresh').on('click', function(event) {
      location.reload();
    });
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
  };

  var addItemPrestamo = function () {
    var htmlTr = '',
        $tablePrestamos = $('#table-prestamos'),
        $fecha = new Date();
    htmlTr = '<tr>' +
                '<td style="width: 200px;"><input type="date" name="fecha[]" value="'+$fecha.toISOString().substr(0, 10)+'" class="span12"> </td>' +
                '<td style="width: 100px;"><input type="text" name="cantidad[]" value="0" class="span12 vpositive cantidad" required></td>' +
                '<td style="width: 100px;"><input type="text" name="pago_semana[]" value="0" class="span12 vpositive pago-semana" required></td>' +
                '<td style="width: 200px;"><input type="date" name="fecha_inicia_pagar[]" value="" class="span12 vpositive fecha-inicia-pagar" required></td>' +
                '<td style="width: 100px;">' +
                  '<input type="hidden" name="id_prestamo[]" value="">' +
                  '<select name="pausarp[]" required style="width: 100px;">' +
                    '<option value="f">Activo</option>' +
                    '<option value="t">Pausado</option>' +
                  '</select></td>' +
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

});