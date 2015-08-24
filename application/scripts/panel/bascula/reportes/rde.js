$(function(){
  // Autocomplete Proveedor
  setAutocomplet('en', 1);

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

  // Autocomplete Chofer
  $("#fchofer").autocomplete({
    source: base_url + 'panel/bascula/ajax_get_choferes/',
    minLength: 1,
    selectFirst: true,
    select: function( event, ui ) {
      $("#fid_chofer").val(ui.item.id);
      $("#fchofer").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
      $(this).css({'background-color': '#FFD9B3'});
      $('#fid_chofer').val('');
    }
  });

  $("#ftipo").on("change", function(){
    $(".autocomplet_en").hide();
    $(".autocomplet_sa").hide();

    $(".autocomplet_"+$(this).val()).show();

    setAutocomplet($(this).val());
  });

  $("#farea").on('change', function() {
    jQuery.getJSON(base_url+'panel/bascula/get_calidades', {id_area: $("#farea").val() },
      function(json, textStatus) {
        var html = '<option value=""></option>';
        if (json.length > 0) {
          for (var i = 0; i < json.length; i++) {
            html += '<option value="'+json[i].id_calidad+'">'+json[i].nombre+'</option>';
          }
        }
        $("#fcalidad").html(html);
    });

  });

  $("#linkXls").on('click', function(event) {
    var vthis = $(this), url="";
    $(".getjsval").each(function(){
      url += "&"+$(this).attr("name")+"="+$(this).val();
    });
    if($("#fefectivo:checked").length == 1)
      url += "&"+$("#fefectivo:checked").attr("name")+"="+$("#fefectivo:checked").val();
    vthis.attr("href", vthis.attr("data-href")+"?"+url.substring(1));
  });
  $("#fstatus, #fefectivo").on("change", function(){
    $("#linkXls").hide();
    if ($("#fstatus").val() == '1' && $("#fefectivo:checked").length == 1)
      $("#linkXls").show();
  });


  $('#form').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          farea: $("#farea").val(),
          fcalidad: $("#fcalidad").val(),
          ftipo: $("#ftipo").val(),
          fproveedor: $("#fproveedor").val(),
          fid_proveedor: $("#fid_proveedor").val(),
          fempresa: $("#fempresa").val(),
          fid_empresa: $("#fid_empresa").val(),
          fstatus: $("#fstatus").val(),
          fid_chofer: $("#fid_chofer").val(),
          fefectivo: $("#fefectivo").is(':checked')? 'si': '',
        };

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

  $('#rptbascacumulados').on('submit', function(event) {
    var linkDownXls = $("#linkDownXls"),
        url = {
          ffecha1: $("#ffecha1").val(),
          ffecha2: $("#ffecha2").val(),
          farea: $("#farea").val(),
          ftipo: $("#ftipo").val(),
          fempresa: $("#fempresa").val(),
          fid_empresa: $("#fid_empresa").val(),
          fstatus: $("#fstatus").val(),
        };

    linkDownXls.attr('href', linkDownXls.attr('data-url') +"?"+ $.param(url));

    console.log(linkDownXls.attr('href'));
  });

});

function setAutocomplet(tipo, first){
  if(first != 1){
    $("#fproveedor").autocomplete("destroy").val("");
    $("#fid_proveedor").val("");
  }
  if (tipo == "en") {
    $("#fproveedor").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#fid_empresa").val()) > 0)
          params.did_empresa = $("#fid_empresa").val();
        $.ajax({
            url: base_url + 'panel/bascula/ajax_get_proveedores/',
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
        $("#fid_proveedor").val(ui.item.id);
        $("#fproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
       $(this).css({'background-color': '#FFD9B3'});
        $('#fid_proveedor').val('');
      }
    });
  }else if(tipo == "sa"){
    $("#fproveedor").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        if(parseInt($("#fid_empresa").val()) > 0)
          params.did_empresa = $("#fid_empresa").val();
        $.ajax({
            url: base_url + 'panel/bascula/ajax_get_clientes/',
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
        $("#fid_proveedor").val(ui.item.id);
        $("#fproveedor").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
       $(this).css({'background-color': '#FFD9B3'});
        $('#fid_proveedor').val('');
      }
    });
  }

  $("#fusuario").autocomplete({
    source: function(request, response) {
      var params = {term : request.term};
      if(parseInt($("#fid_empresa").val()) > 0)
        params.did_empresa = $("#fid_empresa").val();
      params.only_usuario = 'true';
      $.ajax({
          url: base_url + 'panel/usuarios/ajax_get_usuarios/',
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
      $("#fid_usuario").val(ui.item.id);
      $("#fusuario").val(ui.item.label).css({'background-color': '#99FF99'});
    }
  }).keydown(function(e){
    if (e.which === 8) {
     $(this).css({'background-color': '#FFD9B3'});
      $('#fid_usuario').val('');
    }
  });
}