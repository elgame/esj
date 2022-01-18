(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    $(document).on('keyup', '#totalImporte, #totalImpuestosTrasladados, #totalIeps, #totalRetencion, #totalRetencionIsr', function(event) {
      event.preventDefault();
      calculaTotales();
    });

    getSucursales();
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

   var getSucursales = function () {
    var params = {
      did_empresa: $('#empresaId').val()
    };

    hhtml = '<option value=""></option>';
    if (params.did_empresa > 0) {
      $.ajax({
          url: base_url + 'panel/empresas/ajax_get_sucursales/',
          dataType: "json",
          data: params,
          success: function(data) {
            if(data.length > 0) {
              let idSelected = $('#sucursalId').data('selected'), selected = '';
              for (var i = 0; i < data.length; i++) {
                selected = (idSelected == data[i].id_sucursal? ' selected': '');
                hhtml += '<option value="'+data[i].id_sucursal+'" '+selected+'>'+data[i].nombre_fiscal+'</option>';
              }

              $('#sucursalId').html(hhtml).attr('required', 'required');
              $('.sucursales').show();
            } else {
              $('#sucursalId').html(hhtml).removeAttr('required');
              $('.sucursales').hide();
            }
          }
      });
    } else {
      $('#sucursalId').html(hhtml).removeAttr('required');
      $('.sucursales').hide();
    }
  };

});
