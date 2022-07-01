$(function(){

  // Autocomplete Empresas
  $("#fempresa").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_empresa").val(ui.item.id);
      $("#fempresa").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#fid_empresa').val('');
    }
  });

  $('#form').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          fempresa: $("#fempresa").val(),
          fid_empresa: $("#fid_empresa").val(),
          rancho: $("#rancho").val(),
          ranchoId: $("#ranchoId").val(),
        };

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

  autocompleteRanchos();
  autocompleteCentroCosto();
});


var autocompleteRanchos = function () {
  $("#rancho").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#fid_empresa").val()) > 0)
        params.did_empresa = $("#fid_empresa").val();
      if(parseInt(window.parent.$("#parea").val()) > 0)
        params.area = window.parent.$("#parea").val();
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

      $rancho.val(ui.item.id);
      $("#ranchoId").val(ui.item.id);
      $rancho.css("background-color", "#A1F57A");
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $("#rancho").css("background-color", "#FFD071");
      $("#ranchoId").val('');
    }
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
