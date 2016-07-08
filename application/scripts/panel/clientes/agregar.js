$(function(){
  // Autocomplete Empresas
  $("#fempresa").autocomplete({
    source: base_url + 'panel/empresas/ajax_get_empresas/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#did_empresa").val(ui.item.id);
      $("#fempresa").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#did_empresa').val('');
    }
  });

  autocompleteCatalogos();
});

// Autocomplete para los catalogos.
var autocompleteCatalogos = function () {
  var $dpais =  $("#dpais"), $destado = $("#destado"),
      $dmunicipio = $("#dmunicipio"), $dlocalidad = $("#dlocalidad"),
      $dcp = $("#dcp"), $dcolonia = $("#dcolonia");

  $dpais.autocomplete({
    source: base_url + 'panel/catalogos/cpaises',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dpais.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dpais.val(ui.item.id);
        $("span.dpais").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dpais.css("background-color", "#FFD071");
      $("span.dpais").hide();
    }
  });

  $destado.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/cestados',
        dataType: "json",
        data: {
          'c_pais': $dpais.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $destado.css("background-color", "#A1F57A");
      setTimeout(function(){
        $destado.val(ui.item.id);
        $("span.destado").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $destado.css("background-color", "#FFD071");
      $("span.destado").hide();
    }
  });

  $dmunicipio.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/cmunicipios',
        dataType: "json",
        data: {
          'c_estado': $destado.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dmunicipio.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dmunicipio.val(ui.item.id);
        $("span.dmunicipio").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dmunicipio.css("background-color", "#FFD071");
      $("span.dmunicipio").hide();
    }
  });

  $dlocalidad.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/clocalidades',
        dataType: "json",
        data: {
          'c_estado': $destado.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dlocalidad.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dlocalidad.val(ui.item.id);
        $("span.dlocalidad").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dlocalidad.css("background-color", "#FFD071");
      $("span.dlocalidad").hide();
    }
  });

  $dcp.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/ccps',
        dataType: "json",
        data: {
          'c_estado': $destado.val(),
          'c_municipio': $dmunicipio.val(),
          'c_localidad': $dlocalidad.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dcp.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dcp.val(ui.item.id);
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dcp.css("background-color", "#FFD071");
    }
  });

  $dcolonia.autocomplete({
    source: function( request, response ) {
      $.ajax({
        url: base_url + 'panel/catalogos/ccolonias',
        dataType: "json",
        data: {
          'c_cp': $dcp.val(),
          'term': request.term,
        },
        success: function( data ) {
          response( data );
        }
      });
    },
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $dcolonia.css("background-color", "#A1F57A");
      setTimeout(function(){
        $dcolonia.val(ui.item.id);
        $("span.dcolonia").text(ui.item.value).show();
      }, 100);
    }
  }).on("keydown", function(event) {
    if(event.which == 8 || event.which == 46) {
      $dcolonia.css("background-color", "#FFD071");
      $("span.dcolonia").hide();
    }
  });

};