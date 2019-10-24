(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {
    selectItemXml();
  });

  var selectItemXml = function () {
    $('tr.itemXml').dblclick(function(event) {
      var $this = $(this), uuid = $this.attr('data-uuid');
      var noCertificado = $this.attr('data-noCertificado'),
      pass = true;

      if ($('#vmetodoPago').val() === 'pue') { // valida que sea ingreso y de tipo PUE o Complemento de pago
        pass = false;
        if (($this.attr('data-metodoPago') === 'PUE' && $this.attr('data-tipoDeComprobante') === 'I') ||
          ($this.attr('data-tipoDeComprobante') === 'P')) {
          pass = true;
        } else {
          alert("Tienes que cargar un CFDI que sea de tipo P o de tipo I con método de pago PUE");
        }
      }

      console.log(uuid, noCertificado);
      if (pass) {
        window.parent.$('#buscarUuid').val(uuid);
        window.parent.$('#buscarNoCertificado').val(noCertificado);
        window.parent.supermodal.close();
      }
    });
  }

});