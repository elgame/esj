(function (closure) {

  closure(window.jQuery, window);

})(function ($, window) {

  $(function(){
    autocompleteEmpresas();
  });

  var autocompleteEmpresas = function () {
    $("#dempresa").autocomplete({
        source: base_url+'panel/facturacion/ajax_get_empresas_fac/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $("#did_empresa").val(ui.item.id);
          $("#dempresa").css("background-color", "#B0FFB0");

          if($("form#form").length > 0)
            getArbolCuentas(ui.item.id);
        }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $("#dempresa").val("").css("background-color", "#FFD9B3");
          $("#did_empresa").val("");
        }
    });
  };

  var getArbolCuentas = function ($id_empresa) {
    loader.create();
    $.get(base_url+'panel/cuentas_cpi/ajax_get_cuentas', {id_empresa: $id_empresa}, function(data) {
      $("#lista_cuentas").html(data);

      $("#lista_cuentas .treeview").treeview({
        persist: "location",
        unique: true
      });

      loader.close();
    });
  };

});