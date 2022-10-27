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

  $('#form-search').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          farea: $("#farea").val(),
          flotes: $("#flotes").val(),
        };

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

  $('#frmventasdia').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          dempresa: $("#dempresa").val(),
          did_empresa: $("#did_empresa").val(),
        };

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

  $('#frmListadoCuentas').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          did_empresa: $("#did_empresa").val(),
        };

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

  $('#frmverform').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          farea: $("#farea").val(),
        };

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

  $('#form-search').keyJump({
    'next': 13,
  });

  $("#ffecha1, #farea").on('change', function(){
    $.getJSON(base_url+'panel/rastreabilidad/ajax_get_lotes',
      {'area': $("#farea").val(),
      'fecha': $("#ffecha1").val()
      },
      function(res){
        var lotes = '<option value=""></option>';
        if(res.length > 0){
          for (var i = 0; i < res.length; i++) {
            lotes += '<option value="'+res[i].id_rendimiento+'-'+res[i].lote+'">'+res[i].lote_ext+'</option>';
          };
        }
        $("#flotes").html(lotes);
    });
  });


  if($("#fcalidad").length > 0){
    $("#farea").on('change', function(){
      $.getJSON(base_url+'panel/areas/ajax_get_calidades', {'area': $(this).val()}, function(res){
        var calidades = '';
        if(res.calidades.length > 0){
          for (var i = 0; i < res.calidades.length; i++) {
            calidades += '<option value="'+res.calidades[i].id_calidad+'">'+res.calidades[i].nombre+'</option>';
          };
        }
        $("#fcalidad").html(calidades);
      });
    });
  }
});

