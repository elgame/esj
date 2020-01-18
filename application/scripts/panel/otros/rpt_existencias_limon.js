(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    onKeyPressProduccionCosto();

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

});