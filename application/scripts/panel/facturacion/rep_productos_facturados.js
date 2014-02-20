(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {
    autocompleteProductos();

    $('#form').on('submit', function(event) {
      if ($('#did_producto').val() === '') {
        noty({"text": 'Seleccione un producto', "layout":"topRight", "type": 'error'});
        return false;
      }
    });
  });

  function autocompleteProductos () {
   $("#dproducto").autocomplete({
      source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this);
        $this.css("background-color", "#B0FFB0");
        $('#did_producto').val(ui.item.id);
      }
    }).keydown(function(event){
        if(event.which == 8 || event == 46){
          $(this).css("background-color", "#FFD9B3");
          $('#did_producto').val('');
        }
    });
  }

  function autocompleteEmpresa () {
    // Autocomplete Empresas
    $("#dempresa").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_empresa").val(ui.item.id);
        $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        $('#did_empresa').val('');
      }
    });
  }
  
});
