(function (closure) {

  closure(window.jQuery, window);

})(function ($, window) {

  $(function(){
    autocompleteEmpresas();

    if($('#did_empresa').val() != '') {
      cargaRegistrosPatronales($('#did_empresa').val(), true);
    }
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

          cargaRegistrosPatronales(ui.item.id);
        }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $("#dempresa").val("").css("background-color", "#FFD9B3");
          $("#did_empresa").val("");
          $('#dregistro_patronal').html('');
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

  var cargaRegistrosPatronales = function ($id_empresa, $verifiData = false) {
    $.getJSON(base_url+'panel/nomina_fiscal/ajax_get_reg_patronales/', {'anio': '', 'did_empresa': $id_empresa},
      function(data){
        var html = '', i;
        let registro = $("#dregistro_patronal").data("registro"),
        selectt = "";
        console.log(registro, data);


        html += '<option value=""></option>';
        for (i in data.registros_patronales) {
          if($verifiData) {
            selectt = data.registros_patronales[i] == registro? 'selected': '';
          }
          html += '<option value="'+data.registros_patronales[i]+'" '+selectt+'>'+data.registros_patronales[i]+'</option>';
        }
        $('#dregistro_patronal').html(html);
    });
  };

});