(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $(document).on('keyup', '#totalImporte, #totalImpuestosTrasladados, #totalIeps, #totalRetencion, #totalRetencionIsr', function(event) {
      event.preventDefault();
      calculaTotales();
    });
  });

  var calculaTotales = function() {
    var htotalImporte              = parseFloat(util.quitarFormatoNum($('#totalImporte').val()))||0;
    var htotalImpuestosTrasladados = parseFloat(util.quitarFormatoNum($('#totalImpuestosTrasladados').val()))||0;
    var htotalIeps                 = parseFloat(util.quitarFormatoNum($('#totalIeps').val()))||0;
    var htotalRetencion            = parseFloat(util.quitarFormatoNum($('#totalRetencion').val()))||0;
    var htotalRetencionIsr         = parseFloat(util.quitarFormatoNum($('#totalRetencionIsr').val()))||0;
    var total = htotalImporte + htotalImpuestosTrasladados + htotalIeps - htotalRetencion - htotalRetencionIsr;

    $('#htotalImporte').val(htotalImporte);
    $('#htotalImpuestosTrasladados').val(htotalImpuestosTrasladados);
    $('#htotalIeps').val(htotalIeps);
    $('#htotalRetencion').val(htotalRetencion);
    $('#htotalRetencionIsr').val(htotalRetencionIsr);
    $('#totalOrden').val(total);
    $('#htotalOrden').val(total);
  };

});
