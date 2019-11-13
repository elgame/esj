(function (closure) {

  closure(window.jQuery, window);

})(function ($, window) {

  $(function(){
    autocompleteEmpresas();
    autocompleteClientes();
  });

  var autocompleteEmpresas = function () {
    $("#dempresa").autocomplete({
        source: base_url+'panel/facturacion/ajax_get_empresas_fac/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $("#did_empresa").val(ui.item.id);
          $("#dempresa").css("background-color", "#B0FFB0");
        }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $("#dempresa").val("").css("background-color", "#FFD9B3");
          $("#did_empresa").val("");
        }
    });
  };

  var autocompleteClientes = function () {
    $("#dproveedor").autocomplete({
      source: base_url+'panel/proveedores_facturacion/ajax_get_proveedor_fac/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#fid_proveedor").val(ui.item.id);
        $("#dproveedor").css("background-color", "#B0FFB0");
      }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $("#dproveedor").val("").css("background-color", "#FFD9B3");
          $("#fid_proveedor").val("");
        }
    });
  };

});