(function (closure) {

  closure(window.jQuery, window);

})(function ($, window) {

  $(function(){
    autocompleteEmpresas();
    autocompleteClientes();

    $('#rptremidetall').on('submit', function(event) {
      var linkDownXls = $("#linkDownXls"),
          url = {
            ffecha1: $("#ffecha1").val(),
            ffecha2: $("#ffecha2").val(),
            dempresa: $("#dempresa").val(),
            did_empresa: $("#did_empresa").val(),
            ffacturadas: $("#ffacturadas:checked").val(),
          };

      linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

      console.log(linkDownXls.attr('href'));
    });
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
          $("#dempresa").css("background-color", "#FFD9B3");
          $("#did_empresa").val("");
        }
    });
  };

  var autocompleteClientes = function () {
    $("#dcliente").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#did_empresa").val()) > 0)
          params.did_empresa = $("#did_empresa").val();
          $.ajax({
              url: base_url+'panel/clientes/ajax_get_proveedores/',
              dataType: "json",
              data: params,
              success: function(data) {
                  response(data);
              }
          });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#fid_cliente").val(ui.item.id);
        $("#dcliente").css("background-color", "#B0FFB0");
      }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $("#dcliente").css("background-color", "#FFD9B3");
          $("#fid_cliente").val("");
        }
    });
  };

});

function callCancelFactura(obj, dataa) {
  console.log('callCancelFactura', obj.href, dataa);
  let u = new URLSearchParams(dataa).toString();
  window.location = obj.href+'&'+u;
}