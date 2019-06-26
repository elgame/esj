(function (fn) {
  fn(jQuery, window);
})(function ($, window) {

  $(function () {
    selectItemXml();
  });

  var selectItemXml = function () {
    $('tr.itemXml').dblclick(function(event) {
      var uuid = $(this).attr('data-uuid');
      var noCertificado = $(this).attr('data-noCertificado');

      console.log(uuid, noCertificado);
      window.parent.$('#buscarUuid').val(uuid);
      window.parent.$('#buscarNoCertificado').val(noCertificado);
      window.parent.supermodal.close();
    });
  }

});