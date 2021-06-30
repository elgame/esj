(function (fn) {
  fn(jQuery, window);
})(function ($, window) {
  $(function () {
    submitForm();
    setTimeout(() => $('#codigo').focus(), 350);
  });

  let timeout = null;
  const submitForm = function () {
    $('#formScan').submit(function(event) {
      $.post(base_url + 'panel/productos_salidas/comprobar_etiquetas_ajax',
        { codigo: $('#codigo').val() },
        function(data, textStatus, xhr) {
          data = $.parseJSON(data);
          console.log('test', data);

          $('#codigo').val('').focus();

          $('#msgg').show();
          $('#msgg div').removeAttr('class').addClass('alert alert-' + data.status).text(data.msg);
          if (data['terminado']) {
            $('#msgg div').append('<br><strong>'+data.terminado+'</strong>');
          }
          clearTimeout(timeout);
          timeout = setTimeout(function() {
            $('#msgg').hide();
          }, 2500);
      });
      return false;
    });
  };

});