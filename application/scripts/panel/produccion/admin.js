(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    autocompleteClasificaciones();
    autocompleteEmpresas();
  });

  /*
   |------------------------------------------------------------------------
   | Autocompletes
   |------------------------------------------------------------------------
   */

  // Autocomplete para los Clasificaciones.
  var autocompleteClasificaciones = function() {
   $("input#dclasificacion").autocomplete({
      source: base_url+'panel/facturacion/ajax_get_clasificaciones/?inventario=t',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this);

        $this.css("background-color", "#B0FFB0");
        $('#did_clasificacion').val(ui.item.id);
      }
    }).keydown(function(event){
        if(event.which == 8 || event == 46){
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $('#did_clasificacion').val('');
        }
    });
  };

  // Autocomplete para las empresas.
  var autocompleteEmpresas = function () {
    $("#empresa").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $empresa =  $(this);

        $empresa.val(ui.item.id);
        $("#empresaId").val(ui.item.id);
        $empresa.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#empresa").css("background-color", "#FFD071");
        $("#empresaId").val('');
      }
    });
  };

});

function getOrdenesIds ($button, $modal) {
  var idp   = $('#proveedorId').val(),
      ide   = $('#empresaId').val(),
      exist = false,
      ids   = [];

  $('.addToFactura').each(function(index, el) {
    var $check = $(this);

    if ($check.is(':checked')) {
      ids.push($(this).val());
      exist = true;
    }
  });

  if (exist) {
    $button.attr('href', base_url + 'panel/compras_ordenes/ligar/?idp='+idp+'&ide='+ide+'&ids=' + ids.join(','));
    $modal.modal('show');
  } else {
    noty({"text": 'Seleccione una o mas ordenes de compras para ligarlas a una factura!', "layout":"topRight", "type": 'error'});
  }
}