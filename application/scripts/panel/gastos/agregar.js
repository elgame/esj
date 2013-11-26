(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {

    $('#subtotal').on('keyup', function(event) {
      var key = event.which;
      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        total();
      }
    });

    $('#iva').on('keyup', function(event) {
      var key = event.which;
      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        total();
      }
    });

  });

  var total = function () {
    var $total = $('#total'),
        $subtotal = $('#subtotal'),
        $iva = $('#iva');

    $total.val(parseFloat($subtotal.val()||0) + parseFloat($iva.val()||0));
  };

});