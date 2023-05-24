(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {
    autocompleteEmpresa();
    autocompleteActivos();

    $('#form').on('submit', function(event) {
      var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1     : $("#ffecha1").val(),
          ffecha2     : $("#ffecha2").val(),
          dempresa    : $("#dempresa").val(),
          did_empresa : $("#did_empresa").val(),
          activos     : $("#activos").val(),
          activoId    : $("#activoId").val(),
          ddesglosado : $("#ddesglosado:checked").val(),
          dareas      : [],
          dactivos    : [],
        };

      $(".treeviewcustom input[type=checkbox]:checked").each(function(index, el) {
        url.dareas.push($(this).val());
      });

      $("#dactivos input[type=hidden]").each(function(index, el) {
        url.dactivos.push($(this).val());
      });

      linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

      console.log(linkDownXls.attr('href'));

      if (url.ddesglosado.length == 0) {
        noty({"text": 'Seleccione un Vehiculo', "layout":"topRight", "type": 'error'});
        return false;
      }
    });

    $(".treeviewcustom").treeview({
      collapsed: true,
      persist: "location",
      unique: true
    }).find('input[type=checkbox]').attr('checked', 'checked');
    $(".treeviewcustom").find('li ul').remove();
    $("#form").submit();
  });

  // function autocompleteProductos () {
  //  $("#dproducto").autocomplete({
  //     source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
  //     minLength: 1,
  //     selectFirst: true,
  //     select: function( event, ui ) {
  //       var $this = $(this);
  //       $this.val(ui.item.label).css("background-color", "#B0FFB0");
  //       $('#did_producto').val(ui.item.id);

  //       setTimeout(addProducto, 200);
  //     }
  //   }).keydown(function(event){
  //       if(event.which == 8 || event == 46){
  //         $(this).css("background-color", "#FFD9B3");
  //         $('#did_producto').val('');
  //       }
  //   });
  // }

  function autocompleteEmpresa() {
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

  function autocompleteActivos() {
    $("#activos").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        // if(parseInt($("#empresaId").val()) > 0)
        //   params.did_empresa = $("#empresaId").val();
        params.tipo = 'a'; // activos
        $.ajax({
            url: base_url + 'panel/productos/ajax_aut_productos/',
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
        var $activos =  $(this);

        // $activos.val(ui.item.id);
        // $("#activoId").val(ui.item.id);
        // $activos.css("background-color", "#A1F57A");

        $("#dactivos").append('<li><input type="hidden" name="dactivos[]" value="'+ui.item.id+'"> '+ui.item.value+'</li>');
        setTimeout(function(){
          $('#activos').val('');
        }, 100);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#activos").css("background-color", "#FFD071");
        $("#activoId").val('');
      }
    });

    $("#dactivos").on('click', 'li', function(){
      $(this).remove();
    });
  }


});
