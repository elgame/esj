(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    autocompleteProducto();
    autocompleteEmpresas();
  });

  /*
   |------------------------------------------------------------------------
   | Autocompletes
   |------------------------------------------------------------------------
   */

  // Autocomplete para los Clasificaciones.
  var autocompleteProducto = function() {
   $("input#dproducto").autocomplete({
      source: function (request, response) {
        if (isEmpresaSelected()) {
          $.ajax({
            url: base_url + 'panel/compras_ordenes/ajax_producto/',
            dataType: 'json',
            data: {
              term : request.term,
              ide: $('#empresaId').val(),
              // id_almacen: $('#id_almacen').val(),
              tipo: 'p'
            },
            success: function (data) {
              response(data)
            }
          });
        } else {
          noty({"text": 'Seleccione una empresa para mostrar sus productos.', "layout":"topRight", "type": 'error'});
        }
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this);

        $this.css("background-color", "#B0FFB0");
        $('#did_producto').val(ui.item.id);
      }
    }).keydown(function(event){
        if(event.which == 8 || event == 46){
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $('#did_producto').val('');
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

  // Regresa true si esta seleccionada una empresa si no false.
  var isEmpresaSelected = function () {
    return $('#empresaId').val() !== '';
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