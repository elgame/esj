(function (fn) {
  fn(jQuery, window);
})(function ($, window) {
  $(function () {
    eventClickBtnAddBono();
    eventClickBtnAddOtro();
    eventClickBtnDelItem();
    eventClickBtnGuardar();
    eventClickBtnRefresh();
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

  var eventClickBtnGuardar = function () {
    $('#btn-guardar').on('click', function(event) {
      event.preventDefault();
      var $form = $('#form'),
          error = false,
          $cantidades = $('.cantidad');

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
              '<td><input type="text" name="fecha[]" value="'+$fecha.find('option:selected').val()+'" readonly> </td>' +
              '<td><input type="text" name="cantidad[]" value="0" class="vpositive cantidad" required></td>' +
              '<td>' +
                '<select name="tipo[]">' +
                  '<option value="bono" '+selectedBono+'>Bono</option>' +
                  '<option value="otro" '+selectedOtro+'>Otro</option>' +
                '</select>' +
              '</td>' +
              '<td>' +
                '<button type="button" class="btn btn-danger btn-del-item"><i class="icon-trash"></i></button>' +
              '</td>' +
            '</tr>';

    $(htmlTr).appendTo($tableBonosOtros.find('tbody'));

    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };
});