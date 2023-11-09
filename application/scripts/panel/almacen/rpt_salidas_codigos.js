$(function(){
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

  //Autocomplete productos
  $("#fproducto").autocomplete({
    source: function (request, response) {
      if ($('#did_empresa').val() !== '') {
        $.ajax({
          url: base_url + 'panel/compras_ordenes/ajax_producto/',
          dataType: 'json',
          data: {
            term : request.term,
            ide: $('#did_empresa').val(),
            tipo: 'p'
          },
          success: function (data) {
            response(data);
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
      if($("#fproducto").attr('data-add') === undefined)
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

  $('#form').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
      url = {};

    $("input, select").each(function(index, el) {
      if ($(this).attr('name').indexOf('[]') >= 0) {
        url[$(this).attr('name')] = [];
        $("input[name='"+$(this).attr('name')+"']").each(function(index, el) {
          url[$(this).attr('name')].push($(this).val());
        });
      } else {
        url[$(this).attr('name')] = $(this).val();
      }
    });

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

  autocompleteEmpresasAp();
  autocompleteCultivo();
  autocompleteRanchos();
  autocompleteCentroCosto();
  autocompleteActivos();
  autocompleteUsuarios();
});

// Autocomplete para las empresas.
var autocompleteEmpresasAp = function () {
  $("#empresaAp").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      var $empresaAp =  $(this);

      $empresaAp.val(ui.item.id);
      $("#empresaApId").val(ui.item.id);
      $empresaAp.css("background-color", "#A1F57A");
      $("#area, #areaId, #rancho, #ranchoId, #centroCosto, #centroCostoId, #activos, #activoId").val("").css("background-color", "#A1F57A");
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#empresaAp").css("background-color", "#FFD071");
      $("#empresaApId").val('');
      $("#area, #areaId, #rancho, #ranchoId, #centroCosto, #centroCostoId, #activos, #activoId").val("").css("background-color", "#A1F57A");
    }
  });
};

var autocompleteCultivo = function () {
    $("#area").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#empresaId").val()) > 0)
          params.did_empresa = $("#empresaId").val();
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
      if(parseInt($("#empresaId").val()) > 0)
        params.did_empresa = $("#empresaId").val();
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

var autocompleteCentroCosto = function () {
  $("#centroCosto").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};

      params.tipo = ['gasto', 'melga', 'tabla', 'seccion', 'costosventa', 'servicio'];

      $.ajax({
          url: base_url + 'panel/centro_costo/ajax_get_centro_costo/',
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
      var $centroCosto =  $(this);

      addCCTag(ui.item);
      setTimeout(function () {
        $centroCosto.val('');
      }, 200);
      // $centroCosto.val(ui.item.id);
      // $("#centroCostoId").val(ui.item.id);
      // $centroCosto.css("background-color", "#A1F57A");
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#centroCosto").css("background-color", "#FFD071");
      // $("#centroCostoId").val('');
    }
  });

  function addCCTag(item) {
    if ($('#tagsCCIds .centroCostoId[value="'+item.id+'"]').length === 0) {
      $('#tagsCCIds').append('<li><span class="tag">'+item.value+'</span>'+
        '<input type="hidden" name="centroCostoId[]" class="centroCostoId" value="'+item.id+'">'+
        '<input type="hidden" name="centroCostoText[]" class="centroCostoText" value="'+item.value+'">'+
        '</li>');
    } else {
      noty({"text": 'Ya esta agregada el Centro de costo.', "layout":"topRight", "type": 'error'});
    }
  };

  $('#tagsCCIds').on('click', 'li:not(.disable)', function(event) {
    $(this).remove();
  });
};

var autocompleteActivos = function () {
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

      $activos.val(ui.item.id);
      $("#activoId").val(ui.item.id);
      $activos.css("background-color", "#A1F57A");
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#activos").css("background-color", "#FFD071");
      $("#activoId").val('');
    }
  });
};



function addProducto(event){
  var $this = $(this), fid_producto = $("#fid_producto"), fproductor = $("#fproducto");
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

var autocompleteUsuarios = function () {
    $("#fusuario").autocomplete({
      source: base_url + 'panel/usuarios/ajax_get_usuarios/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var $fusuario =  $(this);

        $fusuario.css("background-color", "#A1F57A");
        $("#fid_usuario").val(ui.item.id);
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {$("#fusuario").css("background-color", "#FFD071");
        $("#fid_usuario").val('');
      }
    });
  };
