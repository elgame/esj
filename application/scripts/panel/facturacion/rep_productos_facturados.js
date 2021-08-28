(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {
    autocompleteProductos();
    autocompleteCalidadLive();
    autocompleteTamanioLive();

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
            dtipo: $("#dtipo").val(),
            dtipoReporte: $("#dtipoReporte").val(),
            ids_productos: [],
            did_producto: '',
          };
          if ($('#dpagadas').is(':checked')) {
            url.dpagadas = '1';
          }
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

    $("#btnAddProducto").on('click', addProducto);
    $(document).on('click', '.remove_producto', removeProducto);

    $("#txtbuscar").on('keyup', buscarProductos);
    submitProducto();
  });

  function autocompleteProductos () {
   $("#dproducto").autocomplete({
      source: base_url+'panel/facturacion/ajax_get_clasificaciones/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $this = $(this);
        $this.val(ui.item.label).css("background-color", "#B0FFB0");
        $('#did_producto').val(ui.item.id);

        setTimeout(addProducto, 200);
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

  function autocompleteCalidadLive () {
    $('#form').on('focus', 'input#dcalidad:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/areas_otros/ajax_get_calidades/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('#did_calidad').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#did_calidad').val('');
        }
      });
    });
  }

  function autocompleteTamanioLive () {
    $('#form').on('focus', 'input#dtamanio:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/areas_otros/ajax_get_tamano/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          var $this = $(this),
              $tr = $this.parent().parent();

          $this.css("background-color", "#B0FFB0");

          $tr.find('#did_tamanio').val(ui.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          var $tr = $(this).parent().parent();

          $(this).css("background-color", "#FFD9B3");
          $tr.find('#did_tamanio').val('');
        }
      });
    });
  }


  /*********************
  Reporte compras x producto
  *********************/
  function addProducto(event){
    var $this = $(this), did_producto = $("#did_producto"), dproducto = $("#dproducto");
    if (did_producto.val() != '') {
      if ( $('#liprovee'+did_producto.val()).length == 0) {
        $("#lista_proveedores").append('<li id="liprovee'+did_producto.val()+'"><a class="btn btn-link remove_producto" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
                '<input type="hidden" name="ids_productos[]" class="ids_productos" value="'+did_producto.val()+'"> '+dproducto.val()+'</li>');
      }else
        noty({"text":"El Proveedor ya esta seleccionado", "layout":"topRight", "type":"error"});
      did_producto.val("");
      dproducto.val("").css({'background-color': '#fff'}).focus();
    }else
      noty({"text":"Selecciona un Producto", "layout":"topRight", "type":"error"});
  }

  function removeProducto(event){
    $(this).parent('li').remove();
  }

  function buscarProductos (event) {
    var vthis = $(this);
    $.ajax({
      url: base_url + 'panel/facturacion/ajax_get_clasificaciones/',
      method: "GET",
      dataType: "json",
      data: { term: vthis.val() }
    }).done(function( data ) {
      var html = '';
      for (var i in data) {
        html += '<tr>'+
                  '<td><input type="checkbox" value="'+data[i].id+'" data-name="'+data[i].item.nombre+'"></td>'+
                  '<td>'+data[i].item.nombre+'</td>'+
                '</tr>';
      }
      $("#tblProductos tbody").html(html);
    });
  }

  function submitProducto () {
    $("#addProductos").on('click', function(event) {
      var productos = $("#tblProductos tbody input[type=checkbox]:checked");
      if (productos.length > 0) {
        productos.each(function(index, el) {
          var id = $(this).val(), nombre = $(this).attr('data-name');
            if ( $('#liprovee'+id).length == 0) {
              $("#lista_proveedores").append('<li id="liprovee'+id+'"><a class="btn btn-link remove_producto" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
                      '<input type="hidden" name="ids_productos[]" class="ids_productos" value="'+id+'"> '+nombre+'</li>');
            }else
              noty({"text":"El Producto ya esta seleccionado", "layout":"topRight", "type":"error"});
        });
      }else
        noty({"text":"Selecciona un Producto", "layout":"topRight", "type":"error"});

      return false;
    });

    $('#modal-productos').on('show', function () {
      $("#tblProductos tbody").html("");
    });
  }

});
