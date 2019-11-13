(function (closure) {
  closure($, window);
})(function ($, window) {

  $(function(){
    deleteBoletas();
    autocompleteProveedores();
    autocompleteEmpresas();
    agregarBoleta();
  });

  var agregarBoleta = function(){
    $("#pfolio").on('keydown', function(event) {
      if (event.which == 13){
        $("#btnAgregarBoleta").click();
        event.preventDefault();
      }
    });

    $("#btnAgregarBoleta").on('click', function(event) {
      $.getJSON(base_url+'panel/bascula/get_boleta', {ptipo: $("#ptipo").val(), parea: $("#parea").val(), pfolio: $("#pfolio").val()},
        function(json, textStatus) {
          if (json.info[0].id_bascula) {
            $("#boletasList").append('<tr>'+
                                        ' <td style="">'+json.info[0].fecha_bruto.substr(0, 10)+'<input type="hidden" name="pid_bascula[]" value="'+json.info[0].id_bascula+'" id="pid_bascula" class="span12"></td>'+
                                        ' <td style="">'+json.info[0].folio+'</td>'+
                                        ' <td class="ppimporte" data-importe="'+json.info[0].importe+'">'+util.darFormatoNum(json.info[0].importe)+'</td>'+
                                        ' <td style=""><button class="btn btn-danger removeBoleta"><i class="icon-remove"></i></button></td>'+
                                       '</tr>');
            $("#pfolio").val("").focus();
            calculaTotales();
          }else
            noty({"text": 'No se encontro la boleta', "layout":"topRight", "type": 'error'});
      });
    });
  };

  var deleteBoletas = function(){
    $(document).on('click', '.removeBoleta', function(event) {
      $(this).parents('tr').remove();
      calculaTotales();
    });
  };

  var calculaTotales = function(){
    var total = 0;
    $(".ppimporte").each(function(index, el) {
      total += parseFloat($(this).attr('data-importe'));
    });
    $("#totalImporte").val(total);
    $("#totalOrden").val(total);
  };


  // Autocomplete para los Proveedores.
  var autocompleteProveedores = function () {
    $("#proveedor").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
        $.ajax({
            url: base_url + 'panel/proveedores/ajax_get_proveedores/',
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
        var $proveedor =  $(this);

        $proveedor.val(ui.item.id);
        $("#proveedorId").val(ui.item.id);
        $proveedor.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#proveedor").css("background-color", "#FFD071");
        $("#proveedorId").val('');
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
