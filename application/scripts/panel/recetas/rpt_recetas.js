$(function(){

  $('#frmrptcproform').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
      url = {
        ffecha1: $("#ffecha1").val(),
        ffecha2: $("#ffecha2").val(),
        dempresa: $("#dempresa").val(),
        did_empresa: $("#did_empresa").val(),
        via: $("#a_via").val(),
        area: $("#area").val(),
        areaId: $("#areaId").val(),
        rancho: $("#rancho").val(),
        ranchoId: $("#ranchoId").val(),
        centroCosto: $("#centroCosto").val(),
        centroCostoId: $("#centroCostoId").val(),
        fproducto: $("#fproducto").val(),
        fid_producto: $("#fid_producto").val(),

        // ids_proveedores: [],
      };

    // $("input.ids_proveedores").each(function(index, el) {
    //   url.ids_proveedores.push($(this).val());
    // });

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

  autocompleteCultivo();
  autocompleteRanchos();
  autocompleteCentroCosto();
});

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

      // addRanchoTag(ui.item);
      // setTimeout(function () {
      //   $rancho.val('');
      // }, 200);

      $("#ranchoId").val(ui.item.id);
      $rancho.css("background-color", "#A1F57A");
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#rancho").css("background-color", "#FFD071");
      $("#ranchoId").val('');
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

      params.tipo = ['melga', 'tabla', 'seccion'];

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

      // addCCTag(ui.item);
      // setTimeout(function () {
      //   $centroCosto.val('');
      // }, 200);

      $("#centroCostoId").val(ui.item.id);
      $centroCosto.css("background-color", "#A1F57A");
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#centroCosto").css("background-color", "#FFD071");
      $("#centroCostoId").val('');
    }
  });

  function addCCTag(item) {
    if ($('#tagsCCIds .centroCostoId[value="'+item.id+'"]').length === 0) {
      $('#tagsCCIds').append('<li><span class="tag">'+item.value+'</span>'+
        '<input type="hidden" name="centroCostoId[]" class="centroCostoId" value="'+item.id+'">'+
        '<input type="hidden" name="centroCostoText[]" class="centroCostoText" value="'+item.value+'">'+
        '<input type="hidden" name="centroCostoHec[]" class="centroCostoHec" value="'+(parseFloat(item.item.hectareas)||0)+'">'+
        '<input type="hidden" name="centroCostoNoplantas[]" class="centroCostoNoplantas" value="'+(parseFloat(item.item.no_plantas)||0)+'">'+
        '</li>');
    } else {
      noty({"text": 'Ya esta agregada el Centro de costo.', "layout":"topRight", "type": 'error'});
    }
  };

  $('#tagsCCIds').on('click', 'li:not(.disable)', function(event) {
    $(this).remove();
    calcTotalHecPlant();
  });
};
