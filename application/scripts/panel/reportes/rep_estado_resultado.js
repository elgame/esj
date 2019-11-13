(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {
    autocompleteEmpresas();
    autocompleteCultivo();

    $('#form').on('submit', function(event) {
      var linkDownXls = $("#linkDownXls"),
          url = {
            ffecha1: $("#ffecha1").val(),
            ffecha2: $("#ffecha2").val(),
            dempresa: $("#dempresa").val(),
            did_empresa: $("#did_empresa").val(),
            dcliente: $("#dcliente").val(),
            fid_cliente: $("#fid_cliente").val(),
            did_calidad: $("#did_calidad").val(),
            dcalidad: $("#dcalidad").val(),
            did_tamanio: $("#did_tamanio").val(),
            dtamanio: $("#dtamanio").val(),
            ids_productos: [],
            did_producto: '',
          };
          // url = "?ffecha1="+$("#ffecha1").val()+"&ffecha2="+$("#ffecha2").val()+
          //       "&dempresa="+encodeURIComponent($("#dempresa").val())+
          //       "&did_empresa="+$("#did_empresa").val()+
          //       "&dproducto="+encodeURIComponent($("#dproducto").val())+
          //       "&did_producto="+$("#did_producto").val()+
          //       "&dcliente="+encodeURIComponent($("#dcliente").val())+
          //       "&fid_cliente="+$("#fid_cliente").val();
      $("#lista_proveedores .ids_productos").each(function(index, el) {
        url.ids_productos.push($(this).val());
      });

      linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

      console.log(linkDownXls.attr('href'));

      // if (url.ids_productos.length == 0) {
      //   noty({"text": 'Seleccione un producto', "layout":"topRight", "type": 'error'});
      //   return false;
      // }
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
          $("#dempresa").val("").css("background-color", "#FFD9B3");
          $("#did_empresa").val("");
        }
    });
  };

  var autocompleteCultivo = function () {
    $("#area").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#did_empresa").val()) > 0)
          params.did_empresa = $("#did_empresa").val();
        $.ajax({
            url: base_url + 'panel/areas/ajax_get_areas/',
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
        var $area =  $(this);

        $area.val(ui.item.id);
        $("#areaId").val(ui.item.id);
        $area.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#area").css("background-color", "#FFD071");
        $("#areaId").val('');
      }
    });
  };

});
