(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $('#form').keyJump();
      $('#totalImporte').on('keyup', function(event) {
        var key = event.which;
        if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
          total();
        }
      });

      $('#totalImpuestosTrasladados').on('keyup', function(event) {
        var key = event.which;
        if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
          total();
        }
      });

      $('#totalRetencion').on('keyup', function(event) {
        var key = event.which;
        if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
          total();
        }
      });

      $('#totalIsr').on('keyup', function(event) {
        var key = event.which;
        if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
          total();
        }
      });

      $('#totalIeps').on('keyup', function(event) {
        var key = event.which;
        if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
          total();
        }
      });

      total();
  });

  var total = function () {
    var $total = $('#totalOrden'),
        $subtotal = $('#totalImporte'),
        $ret_iva = $('#totalRetencion'),
        $ret_isr = $('#totalIsr'),
        $totalIeps = $('#totalIeps'),
        $iva = $('#totalImpuestosTrasladados');

    var total = parseFloat($subtotal.val()||0) +
                 parseFloat($iva.val()||0) -
                 parseFloat($ret_iva.val()||0) -
                 parseFloat($ret_isr.val()||0) +
                 parseFloat($totalIeps.val()||0);

    $total.val( util.trunc2Dec(total));
    $('#totalLetra').val(util.numeroToLetra.covertirNumLetras(total.toString()));
  };
});