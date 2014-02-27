(function (fn){
  fn(jQuery, window);
})(function ($, window) {
  $(function () {
    autocompleteEmpleados();
  });

  var autocompleteEmpleados = function () {
    $("#empleado").autocomplete({
      source: base_url+'panel/usuarios/ajax_get_usuarios/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        console.log(ui);
        $("#empleadoId").val(ui.item.id);
        $(this).css("background-color", "#B0FFB0");
      }
    }).on("keydown", function(event){
      if(event.which == 8 || event == 46){
        $(this).css("background-color", "#FFD9B3");
        $("#empleadoId").val("");
      }
    });
  };
});