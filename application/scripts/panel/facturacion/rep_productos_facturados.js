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
});