$(function(){
  $('#form').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
      url = {
        ffecha1: $("#ffecha1").val(),
        ffecha2: $("#ffecha2").val(),
        dempresa: $("#dempresa").val(),
        did_empresa: $("#did_empresa").val(),
        dcon_mov: $("#dcon_mov:checked").val(),
        area: $("#area").val(),
        areaId: $("#areaId").val(),

        familias: [],
        ids_productos: [],
        ranchoId: [],
        ranchoText: [],
      };

    $('#lista_familias input[type=checkbox]:checked').each(function(index, el) {
      url.familias.push($(this).val());
    });
    $("input.ids_productos").each(function(index, el) {
      url.ids_productos.push($(this).val());
    });
    $("input.ranchoId").each(function(index, el) {
      url.ranchoId.push($(this).val());
    });
    $("input.ranchoText").each(function(index, el) {
      url.ranchoText.push($(this).val());
    });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));

    // if (url.dareas.length == 0) {
    //   noty({"text": 'Seleccione una area', "layout":"topRight", "type": 'error'});
    //   return false;
    // }
  });

  $('#frmrptcproform').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
      url = {
        ffecha1: $("#ffecha1").val(),
        ffecha2: $("#ffecha2").val(),
        dempresa: $("#dempresa").val(),
        did_empresa: $("#did_empresa").val(),
        fproducto: $("#fproducto").val(),
        fid_producto: $("#fid_producto").val(),
        dcon_mov: $("#dcon_mov:checked").val(),

        ids_proveedores: [],
      };

    $("input.ids_proveedores").each(function(index, el) {
      url.ids_proveedores.push($(this).val());
    });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));

    // if (url.dareas.length == 0) {
    //   noty({"text": 'Seleccione una area', "layout":"topRight", "type": 'error'});
    //   return false;
    // }
  });

  $('#frmrptcompras').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
      url = {
        ffecha1: $("#ffecha1").val(),
        ffecha2: $("#ffecha2").val(),
        dempresa: $("#dempresa").val(),
        did_empresa: $("#did_empresa").val(),
        tipoOrden: $("#tipoOrden").val(),
      };

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));

    // if (url.dareas.length == 0) {
    //   noty({"text": 'Seleccione una area', "layout":"topRight", "type": 'error'});
    //   return false;
    // }
  });

	// Autocomplete Empresas
  $("#dempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#dempresa").val(ui.item.label).css({'background-color': '#99FF99'});

      if ($('.comprasxproductos').length > 0) {
        getFamilias(ui.item.id);
      }
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

  //Autocomplete productos
  $("#fproducto").autocomplete({
    source: function (request, response) {
      if ($('#did_empresa').val()!='') {
        $.ajax({
          url: base_url + 'panel/compras_ordenes/ajax_producto/',
          dataType: 'json',
          data: {
            term : request.term,
            ide: $('#did_empresa').val(),
            tipo: ''  //p
          },
          success: function (data) {
            response(data)
          }
        });
      } else {
        noty({"text": 'Seleccione una empresa para mostrar sus productos.', "layout":"topRight", "type": 'error'});
      }
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_producto").val(ui.item.id);
      $("#fproducto").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $(this).css("background-color", "#FDFC9A");
      $("#fid_producto").val("");
    }
  });


  // Autocomplete proveedores
  $("#dproveedor").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
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
      $("#did_proveedor").val(ui.item.id);
      $("#dproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
      setTimeout(addProveedor, 200);
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_proveedor').val('');
    }
  });

  $("#btnAddProveedor").on('click', addProveedor);
  $(document).on('click', '.remove_proveedor', removeProveedor);

  $("#frmverform").submit(function(){
    // if ($(".ids_proveedores").length > 0) {

      return true;
    // }else{
    //   noty({"text":"Selecciona al menos un Proveedor", "layout":"topRight", "type":"error"});
    //   return false;
    // }
  });

  /****************
  * Reporte compras x producto
  *****************/
  //Autocomplete productos
  $("#fproductor").autocomplete({
    source: function (request, response) {
      if ($('#did_empresa').val()!='') {
        $.ajax({
          url: base_url + 'panel/compras_ordenes/ajax_producto/',
          dataType: 'json',
          data: {
            term : request.term,
            ide: $('#did_empresa').val(),
            tipo: '' //p
          },
          success: function (data) {
            response(data)
          }
        });
      } else {
        noty({"text": 'Seleccione un empresa para mostrar sus productos.', "layout":"topRight", "type": 'error'});
      }
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_producto").val(ui.item.id);
      $("#fproductor").val(ui.item.label).css({'background-color': '#99FF99'});
      setTimeout(addProducto, 200);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $(this).css("background-color", "#FDFC9A");
      $("#fid_producto").val("");
    }
  });

  $("#btnAddProducto").on('click', addProducto);
  $(document).on('click', '.remove_producto', removeProducto);


  autocompleteCultivo();
  autocompleteRanchos();
});


function addProveedor(event){
  var $this = $(this), did_proveedor = $("#did_proveedor"), dproveedor = $("#dproveedor");
  if (did_proveedor.val() != '') {
    if ( $('#liprovee'+did_proveedor.val()).length == 0) {
      $("#lista_proveedores").append('<li id="liprovee'+did_proveedor.val()+'"><a class="btn btn-link remove_proveedor" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
              '<input type="hidden" name="ids_proveedores[]" class="ids_proveedores" value="'+did_proveedor.val()+'"> '+dproveedor.val()+'</li>');
    }else
      noty({"text":"El Proveedor ya esta seleccionado", "layout":"topRight", "type":"error"});
    did_proveedor.val("");
    dproveedor.val("").css({'background-color': '#fff'}).focus();
  }else
    noty({"text":"Selecciona un Proveedor", "layout":"topRight", "type":"error"});
}

function removeProveedor(event){
  $(this).parent('li').remove();
}


/*********************
Reporte compras x producto
*********************/
function addProducto(event){
  var $this = $(this), fid_producto = $("#fid_producto"), fproductor = $("#fproductor");
  if (fid_producto.val() != '') {
    if ( $('#liprovee'+fid_producto.val()).length == 0) {
      $("#lista_proveedores").append('<li id="liprovee'+fid_producto.val()+'"><a class="btn btn-link remove_producto" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a>'+
              '<input type="hidden" name="ids_productos[]" class="ids_productos" value="'+fid_producto.val()+'"> '+fproductor.val()+'</li>');
    }else
      noty({"text":"El Proveedor ya esta seleccionado", "layout":"topRight", "type":"error"});
    fid_producto.val("");
    fproductor.val("").css({'background-color': '#fff'}).focus();
  }else
    noty({"text":"Selecciona un Producto", "layout":"topRight", "type":"error"});
}

function removeProducto(event){
  $(this).parent('li').remove();
}


function getFamilias(id_empresa, idset = 'lista_familias'){
  $.ajax({
    url: base_url + 'panel/productos/ajax_get_familias2/',
    dataType: 'json',
    data: {
      id_empresa: id_empresa,
    },
    success: function (data) {
      var html = '';
      if (data.length > 0) {
        for (var i = data.length - 1; i >= 0; i--) {
          html += '<li><label> <input type="checkbox" name="familias[]" value="'+data[i].id_familia+'"> '+data[i].nombre+'</label></li>';
        }
      }

      $('#'+idset).html(html);
    }
  });
}

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

        $("#rancho").val('').css("background-color", "#FFD071");
        $('#tagsRanchoIds').html('');
        // $("#ranchoId").val('');
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#area").css("background-color", "#FFD071");
        $("#areaId").val('');
        $('#tagsRanchoIds').html('');
        $("#rancho").val('').css("background-color", "#FFD071");
        // $("#ranchoId").val('');
      }
    });
};

var autocompleteRanchos = function () {
  $("#rancho").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#did_empresa").val()) > 0)
        params.did_empresa = $("#did_empresa").val();
      if(parseInt($("#areaId").val()) > 0)
        params.area = $("#areaId").val();
      $.ajax({
          url: base_url + 'panel/ranchos/ajax_get_ranchos/',
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
      var $rancho =  $(this);

      addRanchoTag(ui.item);
      setTimeout(function () {
        $rancho.val('');
      }, 200);
      // $rancho.val(ui.item.id);
      // $("#ranchoId").val(ui.item.id);
      // $rancho.css("background-color", "#A1F57A");
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#rancho").css("background-color", "#FFD071");
      // $("#ranchoId").val('');
    }
  });

  function addRanchoTag(item) {
    if ($('#tagsRanchoIds .ranchoId[value="'+item.id+'"]').length === 0) {
      $('#tagsRanchoIds').append('<li><span class="tag">'+item.value+'</span>'+
        '<input type="hidden" name="ranchoId[]" class="ranchoId" value="'+item.id+'">'+
        '<input type="hidden" name="ranchoText[]" class="ranchoText" value="'+item.value+'">'+
        '</li>');
    } else {
      noty({"text": 'Ya esta agregada el Areas, Ranchos o Lineas.', "layout":"topRight", "type": 'error'});
    }
  };

  $('#tagsRanchoIds').on('click', 'li:not(.disable)', function(event) {
    $(this).remove();
  });
};