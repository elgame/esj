(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    eventSave();
  });

  var eventSave = function () {
    $("#saveAjaxExtras").click(function(event) {
      $(this).prop("disabled", true);

      var btnSave = $(this),
      idreceta = $(this).attr('data-idReceta'),
      params = {
        'ar_semana': $("#ar_semana").val(),
        'ar_fecha': $("#ar_fecha").val(),
        'ar_ph': $("#ar_ph").val(),
      };
      $.ajax({
          url: base_url + 'panel/recetas/modificar_ajax?id='+idreceta,
          dataType: "json",
          type: "POST",
          data: params,
          success: function(data) {
            if(data.passes) {
              noty({"text": data.msg, "layout":"topRight", "type":"success"});
            } else {
              noty({"text": data.msg, "layout":"topRight", "type":"error"});
            }

            btnSave.removeAttr('disabled');
          }
      });
    });


    hhtml = '<option value=""></option>';
    if (params.did_empresa > 0) {

    } else {
      $('#sucursalId').html(hhtml).removeAttr('required');
      $('.sucursales').hide();
    }
  };

});
